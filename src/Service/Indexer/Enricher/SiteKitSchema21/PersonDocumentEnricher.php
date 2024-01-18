<?php

declare(strict_types=1);

namespace Atoolo\CityGov\Service\Indexer\Enricher\SiteKitSchema21;

use Atoolo\Resource\Resource;
use Atoolo\Resource\ResourceLoader;
use Atoolo\Search\Service\Indexer\DocumentEnricher;
use Atoolo\Search\Service\Indexer\IndexDocument;
use Atoolo\Search\Service\Indexer\IndexSchema2xDocument;

/**
 * TODO
 * - person.google.noIndex -> https://gitlab.sitepark.com/sitekit/citygov-php/-/blob/develop/php/SP/CityGov/Component/Initialization.php?ref_type=heads#L218
 * @phpstan-type Membership array{
 *      primary?: bool,
 *      organisation?:array{
 *          url?:string
 *      }
 *  }
 * @phpstan-type Competence array{
 *       primary?: bool,
 *       product?:array{
 *           url?:string
 *       }
 *   }
 *
 * @implements DocumentEnricher<IndexSchema2xDocument>
 */
class PersonDocumentEnricher implements DocumentEnricher
{
    public function __construct(
        private readonly ResourceLoader $resourceLoader,
        private readonly OrganisationDocumentEnricher $organisationEnricher
    ) {
    }

    public function isIndexable(Resource $resource): bool
    {
        return true;
    }

    public function enrichDocument(
        Resource $resource,
        IndexDocument $doc,
        string $processId
    ): IndexDocument {

        if ($resource->getObjectType() !== 'citygovPerson') {
            return $doc;
        }

        return $this->enrichDocumentForPerson($resource, $doc);
    }

    private function enrichDocumentForPerson(
        Resource $resource,
        IndexSchema2xDocument $doc
    ): IndexSchema2xDocument {

        $firstname = $resource->getData()->getString(
            'metadata.citygovPerson.firstname'
        );
        $lastname = $resource->getData()->getString(
            'metadata.citygovPerson.lastname'
        );

        $doc->sp_citygov_firstname = $firstname;
        $doc->sp_citygov_lastname = $lastname;
        $doc->sp_sortvalue = $lastname . 'aaa' . $firstname;
        if ($lastname !== '') {
            $doc->sp_citygov_startletter = mb_substr($lastname, 0, 1);
        }

        /** @var Membership[] $membershipList */
        $membershipList = $resource->getData()->getArray(
            'metadata.citygovPerson.membershipList.items'
        );
        /** @var array<array<string>> $organisationNameMergeList */
        $organisationNameMergeList = [];
        /** @var string[] $organisationTokenList */
        $organisationTokenList = [];

        foreach ($membershipList as $membership) {
            $organisationLocation = $membership['organisation']['url'] ?? null;
            if ($organisationLocation === null) {
                continue;
            }

            $organisationResource = $this->resourceLoader->load(
                $organisationLocation
            );

            $organisationNameMergeList[] = [
                $organisationResource->getData()->getString(
                    'metadata.citygovOrganisation.name'
                )
            ];

            $token = $organisationResource->getData()->getString(
                'metadata.citygovOrganisation.token'
            );
            if (!empty($token)) {
                $organisationTokenList[] = str_replace('.', ' ', $token);
            }

            $synonymList = $organisationResource->getData()->getArray(
                'metadata.citygovOrganisation.synonymList'
            );
            $organisationNameMergeList[] = $synonymList;

            if (($membership['primary'] ?? false) === true) {
                $doc = $this->organisationEnricher->enrichOrganisationPath(
                    $organisationResource,
                    $doc
                );
            }
        }

        /** @var string[] $organisationNameList */
        $organisationNameList = array_merge([], ...$organisationNameMergeList);

        $doc->sp_citygov_organisation = $organisationNameList;
        $doc->sp_citygov_organisationtoken = $organisationTokenList;

        /** @var Competence[] $competenceList */
        $competenceList = $resource->getData()->getArray(
            'metadata.citygovPerson.competenceList.items'
        );
        $productNameMergeList = [];

        foreach ($competenceList as $competence) {
            $productLocation = $competence['product']['url'] ?? null;
            if ($productLocation === null) {
                continue;
            }
            $productResource = $this->resourceLoader->load($productLocation);

            $productNameMergeList[] = [$productResource->getData()->getString(
                'metadata.citygovProduct.name'
            )];

            $synonymList = $productResource->getData()->getArray(
                'metadata.citygovProduct.synonymList'
            );
            $productNameMergeList[] = $synonymList;
        }

        /** @var string[] $productNameList */
        $productNameList = array_merge([], ...$productNameMergeList);

        $doc->sp_citygov_product = $productNameList;

        $functionName = $resource->getData()->getString(
            'metadata.citygovPerson.function.name'
        );
        $functionAppendix = $resource->getData()->getString(
            'metadata.citygovPerson.function.appendix'
        );

        $doc->sp_citygov_function = trim(
            $functionName . ' ' . $functionAppendix
        );

        return $doc;
    }
}
