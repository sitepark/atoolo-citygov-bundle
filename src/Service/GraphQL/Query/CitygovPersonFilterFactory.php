<?php

declare(strict_types=1);

namespace Atoolo\CityGov\Service\GraphQL\Query;

use Atoolo\CityGov\Service\GraphQL\Input\CitygovPerson;
use Atoolo\CityGov\Service\GraphQL\Input\CitygovPersonCompetence;
use Atoolo\CityGov\Service\GraphQL\Input\CitygovPersonField;
use Atoolo\CityGov\Service\GraphQL\Input\SearchCitygovPersonInput as SearchInput;
use Atoolo\CityGov\Service\GraphQL\Input\SuggestCitygovPersonInput as SuggestInput;
use Atoolo\Search\Dto\Search\Query\Filter\ContentTypeFilter;
use Atoolo\Search\Dto\Search\Query\Filter\Filter;
use Atoolo\Search\Dto\Search\Query\Filter\NotFilter;
use Atoolo\Search\Dto\Search\Query\Filter\QueryFilter;

class CitygovPersonFilterFactory
{
    /**
     * @return Filter[]
     */
    public function createForCitygovPersonSuggest(SuggestInput $input): array
    {
        $filters = [
            new QueryFilter('sp_contenttype:citygovPerson'),
            new NotFilter(new ContentTypeFilter(['pdf'])),
        ];
        if ($input->person !== null) {
            array_push($filters, ...$this->createForCitygovPerson($input->person));
        }
        if ($input->personCompetence !== null) {
            array_push($filters, ...$this->createForCitygovPersonCompetence($input->personCompetence));
        }
        return $filters;
    }

    /**
     * @return Filter[]
     */
    public function createForCitygovPersonSearch(SearchInput $input): array
    {
        $filters = [
            new QueryFilter('sp_contenttype:citygovPerson'),
            new NotFilter(new ContentTypeFilter(['pdf'])),
        ];
        if ($input->person !== null) {
            array_push($filters, ...$this->createForCitygovPerson($input->person));
        }
        if ($input->personCompetence !== null) {
            array_push($filters, ...$this->createForCitygovPersonCompetence($input->personCompetence));
        }
        return $filters;
    }

    /**
     * Creates a list of filters for every non-empty field of a CitygovPerson input type
     * @return Filter[]
     */
    public function createForCitygovPerson(CitygovPerson $personInput): array
    {
        $filters = [];
        if (!empty($personInput->firstname)) {
            $filters[] = $this->createFilterFor(CitygovPersonField::FIRSTNAME, $personInput->firstname);
        }
        if (!empty($personInput->lastname)) {
            $filters[] = $this->createFilterFor(CitygovPersonField::LASTNAME, $personInput->lastname);
        }
        if (!empty($personInput->product)) {
            $filters[] = $this->createFilterFor(CitygovPersonField::PRODUCT, $personInput->product);
        }
        if (!empty($personInput->function)) {
            $filters[] = $this->createFilterFor(CitygovPersonField::FUNCTION, $personInput->function);
        }
        if (!empty($personInput->organisation)) {
            $filters[] = $this->createFilterFor(CitygovPersonField::ORGANISATION, $personInput->organisation);
        }
        if (!empty($personInput->address)) {
            $filters[] = $this->createFilterFor(CitygovPersonField::ADDRESS, $personInput->address);
        }
        if (!empty($personInput->phonenumber)) {
            $filters[] = $this->createFilterFor(CitygovPersonField::PHONENUMBER, $personInput->phonenumber);
        }
        return $filters;
    }

    protected function createFilterFor(CitygovPersonField $field, string $value): Filter
    {
        return match ($field) {
            CitygovPersonField::FIRSTNAME => new QueryFilter("sp_citygov_firstname:($value* OR $value)"),
            CitygovPersonField::LASTNAME => new QueryFilter("sp_citygov_lastname:($value* OR $value)"),
            CitygovPersonField::PRODUCT => new QueryFilter("sp_citygov_product:($value* OR $value)"),
            CitygovPersonField::FUNCTION => new QueryFilter("sp_citygov_function:($value* OR $value)"),
            CitygovPersonField::ORGANISATION => new QueryFilter("sp_citygov_organisation:($value* OR $value)"
                . " OR sp_citygov_organisationtoken:$value"),
            CitygovPersonField::ADDRESS => new QueryFilter("sp_citygov_address:($value* OR $value)"),
            CitygovPersonField::PHONENUMBER => new QueryFilter("sp_citygov_phone:($value* OR $value)"),
        };
    }

    /**
     * @return Filter[]
     */
    public function createForCitygovPersonCompetence(CitygovPersonCompetence $personCompetenceInput): array
    {
        return $this->getIdRangeFilter($personCompetenceInput);
    }

    /**
     * @todo Create id range filter based on the person competence `$input->personCompetence`
     * similar to `\SP\CityGov\Controller\Search\PersonSearcher::setRangesFilter`
     * @return Filter[]
     */
    protected function getIdRangeFilter(CitygovPersonCompetence $personCompetenceInput): array
    {
        return [];
    }
}
