<?php

declare(strict_types=1);

namespace Atoolo\CityGov\Service\Indexer\Enricher\GenAi;

use Atoolo\GenAi\Dto\Indexer\Link;
use Atoolo\GenAi\Dto\Indexer\LinkSection;
use Atoolo\GenAi\Dto\Indexer\TextSection;
use Atoolo\GenAi\Service\Indexer\GenAiDocument;
use Atoolo\Resource\Resource;
use Atoolo\Resource\ResourceLocation;
use Exception;

/**
 * Turns the references of a citygov resource - to organisations, services
 * and persons - into sections of the GenAI index.
 *
 * A reference carries no name, so each one is loaded. A reference that
 * cannot be loaded - unpublished or deleted - is left out, as the website
 * does.
 *
 * The application does not embed the links of a link section, so every name
 * is written into a text section as well, followed by the links.
 *
 * The class using it holds the `ResourceLoader` as `$resourceLoader`.
 */
trait ReferenceListTrait
{
    /**
     * @param list<array{text:string, link:?Link}> $entries
     */
    private function addSections(
        GenAiDocument $doc,
        string $headline,
        array $entries,
    ): void {
        if (empty($entries)) {
            return;
        }
        $html = '<ul>';
        $links = [];
        foreach ($entries as $entry) {
            $html .= '<li>' . $this->escape($entry['text']) . '</li>';
            if ($entry['link'] !== null) {
                $links[] = $entry['link'];
            }
        }
        $doc->content[] = new TextSection($headline, $html . '</ul>');
        if (!empty($links)) {
            $doc->content[] = new LinkSection($headline, $links);
        }
    }

    private function load(Resource $from, mixed $reference): ?Resource
    {
        $url = $this->url($reference);
        if ($url === '') {
            return null;
        }
        try {
            return $this->resourceLoader->load(
                ResourceLocation::of($url, $from->lang),
            );
        } catch (Exception) {
            return null;
        }
    }

    private function name(Resource $resource, string $objectType): string
    {
        $name = $this->text(
            $resource->data->getString('metadata.' . $objectType . '.name'),
        );
        return $name !== '' ? $name : $this->text($resource->name);
    }

    private function url(mixed $reference): string
    {
        $url = is_array($reference) ? ($reference['url'] ?? null) : null;
        return is_string($url) ? $url : '';
    }

    private function link(mixed $reference, string $label): ?Link
    {
        $url = $this->url($reference);
        return $url === '' || $label === '' ? null : new Link($url, $label);
    }

    /**
     * Trimmed, with the runs of white space the CMS leaves collapsed.
     */
    private function text(mixed $text): string
    {
        if (!is_string($text)) {
            return '';
        }
        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    private function escape(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
