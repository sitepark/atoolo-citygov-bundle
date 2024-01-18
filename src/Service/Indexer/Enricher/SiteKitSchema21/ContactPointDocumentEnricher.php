<?php

declare(strict_types=1);

namespace Atoolo\CityGov\Service\Indexer\Enricher\SiteKitSchema21;

use Atoolo\Resource\Resource;
use Atoolo\Search\Service\Indexer\DocumentEnricher;
use Atoolo\Search\Service\Indexer\IndexDocument;
use Atoolo\Search\Service\Indexer\IndexSchema2xDocument;

/**
 * @phpstan-type Phone array{nationalNumber:string}
 * @phpstan-type PhoneData array{phone:Phone}
 * @phpstan-type PhoneList array<PhoneData>
 * @phpstan-type Email array{email:string}
 * @phpstan-type EmailList array<Email>
 * @phpstan-type ContactData array{
 *     phoneList?:PhoneList,
 *     emailList:EmailList
 * }
 * @phpstan-type AddressData array{
 *     buildingName?:string,
 *     street?:string,
 *     housenumber?:string
 * }
 * @phpstan-type ContactPoint array{
 *     contactData?:ContactData,
 *     addressData?:AddressData
 * }
 *
 * @implements DocumentEnricher<IndexSchema2xDocument>
 */
class ContactPointDocumentEnricher implements DocumentEnricher
{
    public function isIndexable(Resource $resource): bool
    {
        return true;
    }

    public function enrichDocument(
        Resource $resource,
        IndexDocument $doc,
        string $processId
    ): IndexDocument {

        if (
            $resource->getObjectType() !== 'citygovOrganisation'
            && $resource->getObjectType() !== 'citygovPerson'
        ) {
            return $doc;
        }

        /** @var ContactPoint $contactPoint */
        $contactPoint = $resource->getData()->getAssociativeArray(
            'metadata.contactPoint'
        );
        return $this->enrichDocumentForContactPoint(
            $contactPoint,
            $doc
        );
    }

    /**
     * @param ContactPoint $contactPoint
     * @param IndexSchema2xDocument $doc
     * @return IndexSchema2xDocument
     */
    private function enrichDocumentForContactPoint(
        array $contactPoint,
        IndexSchema2xDocument $doc
    ): IndexSchema2xDocument {

        $phoneList = [];
        $phoneListData = $contactPoint['contactData']['phoneList'] ?? [];
        foreach ($phoneListData as $phoneData) {
            $phoneList[] = $phoneData['phone']['nationalNumber'];
        }
        $doc->sp_citygov_phone = $phoneList;

        $emailList = [];
        $emailListData = $contactPoint['contactData']['emailList'] ?? [];
        foreach ($emailListData as $emailData) {
            $emailList[] = $emailData['email'];
        }
        $doc->sp_citygov_email = $emailList;

        $addressSearchValue = $this->toAddressSearchValue(
            $contactPoint['addressData'] ?? []
        );

        $doc->sp_citygov_address = $addressSearchValue;

        return $doc;
    }

    /**
     * @param AddressData $address
     * @return string
     */
    private function toAddressSearchValue(array $address): string
    {
        return trim(($address['buildingName'] ?? '') . ' '
            .  ($address['street'] ?? '') . ' '
            .  ($address['housenumber'] ?? ''));
    }
}
