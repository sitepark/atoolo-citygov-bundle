<?php

declare(strict_types=1);

namespace Atoolo\CityGov\Service\Indexer\Enricher\GenAi;

use Atoolo\GenAi\Dto\Indexer\Link;
use Atoolo\GenAi\Dto\Indexer\TextSection;
use Atoolo\GenAi\Service\Indexer\GenAiDocument;
use Atoolo\Index\Service\Indexer\DocumentEnricher;
use Atoolo\Index\Service\Indexer\IndexDocument;
use Atoolo\Resource\Resource;
use Atoolo\Resource\ResourceLoader;

/**
 * Adds the function and the responsibilities of a person to the GenAI
 * index: the function the person holds, the organisations the person
 * belongs to, with the function there, the services the person is
 * responsible for and the persons who stand in.
 *
 * The person refers to all of them by reference only, see
 * {@see ReferenceListTrait}.
 *
 * @implements DocumentEnricher<GenAiDocument>
 */
class CityGovPersonGenAiDocumentEnricher implements DocumentEnricher
{
    use ReferenceListTrait;

    /**
     * The labels of the website.
     */
    private const CRITERIA = [
        'prefix' => 'Buchstabenbereich',
        'file' => 'Aktenzeichen',
        'licensePlate' => 'KFZ-Kennzeichen',
        'tin' => 'Steuernummer',
    ];

    public function __construct(
        private readonly ResourceLoader $resourceLoader,
    ) {}

    public function cleanup(): void {}

    public function enrichDocument(
        Resource $resource,
        IndexDocument $doc,
        string $processId,
    ): IndexDocument {
        if ($resource->objectType !== 'citygovPerson' || $doc->isMedia()) {
            return $doc;
        }

        $function = $this->function(
            $resource->data->getArray('metadata.citygovPerson.function'),
        );
        if ($function !== '') {
            $doc->content[] = new TextSection(
                'Funktion',
                '<p>' . $this->escape($function) . '</p>',
            );
        }

        /** @var array<mixed> $memberships */
        $memberships = $resource->data->getArray(
            'metadata.citygovPerson.membershipList.items',
        );
        $this->addSections(
            $doc,
            'Organisationseinheiten',
            $this->memberships($resource, $memberships),
        );

        /** @var array<mixed> $competences */
        $competences = $resource->data->getArray(
            'metadata.citygovPerson.competenceList.items',
        );
        $this->addSections(
            $doc,
            'Zuständigkeiten',
            $this->competences($resource, $competences),
        );

        /** @var array<mixed> $substitutes */
        $substitutes = $resource->data->getArray(
            'metadata.citygovPerson.substituteList.items',
        );
        $this->addSections(
            $doc,
            'Vertretung',
            $this->substitutes($resource, $substitutes),
        );

        return $doc;
    }

    /**
     * The primary organisation comes first.
     *
     * @param array<mixed> $memberships
     * @return list<array{text:string, link:?Link}>
     */
    private function memberships(Resource $person, array $memberships): array
    {
        $primary = [];
        $others = [];
        foreach ($memberships as $membership) {
            if (!is_array($membership)) {
                continue;
            }
            $organisation = $this->load(
                $person,
                $membership['organisation'] ?? null,
            );
            if ($organisation === null) {
                continue;
            }
            $name = $this->name($organisation, 'citygovOrganisation');
            if ($name === '') {
                continue;
            }
            $function = $this->function($membership['function'] ?? null);
            $entry = [
                'text' => $function === '' ? $name : $name . ' – ' . $function,
                'link' => $this->link($membership['organisation'] ?? null, $name),
            ];
            if (($membership['primary'] ?? false) === true) {
                $primary[] = $entry;
            } else {
                $others[] = $entry;
            }
        }
        return array_merge($primary, $others);
    }

    /**
     * A competence names a service or, without one, a text of its own.
     *
     * @param array<mixed> $competences
     * @return list<array{text:string, link:?Link}>
     */
    private function competences(Resource $person, array $competences): array
    {
        $entries = [];
        foreach ($competences as $competence) {
            if (!is_array($competence)) {
                continue;
            }
            $link = null;
            $name = '';
            if (is_array($competence['product'] ?? null)) {
                $product = $this->load($person, $competence['product']);
                if ($product !== null) {
                    $name = $this->name($product, 'citygovProduct');
                    $link = $this->link($competence['product'], $name);
                }
            } else {
                $name = $this->text($competence['productName'] ?? null);
            }
            if ($name === '') {
                continue;
            }

            $details = [];
            $description = $this->text($competence['description'] ?? null);
            if ($description !== '') {
                $details[] = $description;
            }
            $criterias = $competence['criterias'] ?? null;
            foreach (is_array($criterias) ? $criterias : [] as $criteria) {
                $text = is_array($criteria) ? $this->criteria($criteria) : '';
                if ($text !== '') {
                    $details[] = $text;
                }
            }
            $substitute = is_array($competence['substitute'] ?? null)
                ? $this->text($competence['substitute']['name'] ?? null)
                : '';
            if ($substitute !== '') {
                $details[] = 'Vertretung: ' . $substitute;
            }

            $entries[] = [
                'text' => empty($details)
                    ? $name
                    : $name . ' (' . implode('; ', $details) . ')',
                'link' => $link,
            ];
        }
        return $entries;
    }

    /**
     * @param array<mixed> $criteria
     */
    private function criteria(array $criteria): string
    {
        $type = $criteria['type'] ?? null;
        $label = is_string($type) ? (self::CRITERIA[$type] ?? '') : '';
        if ($label === '') {
            return '';
        }
        $ranges = [];
        $values = $criteria['values'] ?? null;
        foreach (is_array($values) ? $values : [] as $value) {
            if (!is_array($value)) {
                continue;
            }
            $from = $this->text($value['from'] ?? null);
            $to = $this->text($value['to'] ?? null);
            $range = $from !== '' && $to !== '' && $from !== $to
                ? $from . ' – ' . $to
                : ($from !== '' ? $from : $to);
            if ($range !== '') {
                $ranges[] = $range;
            }
        }
        return empty($ranges) ? '' : $label . ' ' . implode(', ', $ranges);
    }

    /**
     * @param array<mixed> $substitutes
     * @return list<array{text:string, link:?Link}>
     */
    private function substitutes(Resource $person, array $substitutes): array
    {
        $entries = [];
        foreach ($substitutes as $reference) {
            $substitute = $this->load($person, $reference);
            if ($substitute === null) {
                continue;
            }
            $name = $this->personName($substitute);
            if ($name !== '') {
                $entries[] = [
                    'text' => $name,
                    'link' => $this->link($reference, $name),
                ];
            }
        }
        return $entries;
    }

    private function personName(Resource $resource): string
    {
        $name = $this->text(
            $resource->data->getString('metadata.citygovPerson.firstname')
            . ' '
            . $resource->data->getString('metadata.citygovPerson.lastname'),
        );
        return $name !== '' ? $name : $this->text($resource->name);
    }

    private function function(mixed $function): string
    {
        if (!is_array($function)) {
            return '';
        }
        return $this->text(
            $this->text($function['name'] ?? null)
            . ' '
            . $this->text($function['appendix'] ?? null),
        );
    }
}
