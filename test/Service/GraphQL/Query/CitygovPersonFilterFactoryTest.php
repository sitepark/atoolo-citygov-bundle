<?php

declare(strict_types=1);

namespace Atoolo\CityGov\Test\Service\GraphQL\Query;

use Atoolo\CityGov\Service\GraphQL\Factory\CompetenceFilterFactory;
use Atoolo\CityGov\Service\GraphQL\Input\CitygovPerson;
use Atoolo\CityGov\Service\GraphQL\Input\CitygovPersonCompetence;
use Atoolo\CityGov\Service\GraphQL\Input\SearchCitygovPersonInput;
use Atoolo\CityGov\Service\GraphQL\Input\SuggestCitygovPersonInput;
use Atoolo\CityGov\Service\GraphQL\Query\CitygovPersonFilterFactory;
use Atoolo\Search\Dto\Search\Query\Filter\ContentTypeFilter;
use Atoolo\Search\Dto\Search\Query\Filter\Filter;
use Atoolo\Search\Dto\Search\Query\Filter\NotFilter;
use Atoolo\Search\Dto\Search\Query\Filter\QueryFilter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function PHPUnit\Framework\assertEquals;

#[CoversClass(CitygovPersonFilterFactory::class)]
class CitygovPersonFilterFactoryTest extends TestCase
{
    private CitygovPersonFilterFactory $filterFactory;
    private CitygovPersonFilterFactory $filterFactoryEmptyComponentFilter;
    private CitygovPersonFilterFactory $filterFactoryFilledComponentFilter;

    public function setUp(): void
    {
        $componentFilterFactory = $this->createMock(CompetenceFilterFactory::class);
        $componentFilterFactoryEmpty = $this->createMock(CompetenceFilterFactory::class);
        $componentFilterFactoryEmpty->method('getfilteredPersonIdList')->willReturn([]);
        $componentFilterFactoryIdList = $this->createMock(CompetenceFilterFactory::class);
        $componentFilterFactoryIdList->method('getfilteredPersonIdList')->willReturn([815, 518]);

        $this->filterFactory = new CitygovPersonFilterFactory($componentFilterFactory);
        $this->filterFactoryEmptyComponentFilter = new CitygovPersonFilterFactory($componentFilterFactoryEmpty);
        $this->filterFactoryFilledComponentFilter = new CitygovPersonFilterFactory($componentFilterFactoryIdList);
    }

    public function testCreateForCitygovPersonSearch(): void
    {
        $fitlers = $this->filterFactory->createForCitygovPersonSearch(
            $this->createSearchInput(),
        );
        assertEquals($this->createExpectedFilters(), $fitlers);
    }

    public function testCreateForCitygovPersonSuggest(): void
    {
        $fitlers = $this->filterFactory->createForCitygovPersonSuggest(
            $this->createSuggestInput(),
        );
        assertEquals($this->createExpectedFilters(), $fitlers);
    }

    public function testCitygovPersonSearchEmptyCompetence(): void
    {
        $fitlers = $this->filterFactoryEmptyComponentFilter->createForCitygovPersonSearch(
            $this->createSearchInput(),
        );
        $expectedFilters = $this->createExpectedFilters();
        $expectedFilters[] = new QueryFilter('sp_id:(0)');
        assertEquals($expectedFilters, $fitlers);
    }

    public function testCitygovPersonSearchRilledCompetence(): void
    {
        $fitlers = $this->filterFactoryFilledComponentFilter->createForCitygovPersonSearch(
            $this->createSearchInput(),
        );
        $expectedFilters = $this->createExpectedFilters();
        $expectedFilters[] = new QueryFilter('sp_id:(815 OR 518)');
        assertEquals($expectedFilters, $fitlers);
    }

    private function createSearchInput(): SearchCitygovPersonInput
    {
        $input = new SearchCitygovPersonInput();
        $input->person = $this->createCitygovPerson();
        $input->personCompetence = $this->createCitygovPersonCompetence();
        $input->lang = 'en';
        $input->offset = 15;
        $input->limit = 5;
        return $input;
    }

    private function createSuggestInput(): SuggestCitygovPersonInput
    {
        $input = new SuggestCitygovPersonInput();
        $input->person = $this->createCitygovPerson();
        $input->personCompetence = $this->createCitygovPersonCompetence();
        $input->lang = 'en';
        $input->limit = 5;
        return $input;
    }

    private function createCitygovPerson(): CitygovPerson
    {
        $person = new CitygovPerson();
        $person->firstname = 'Max';
        $person->lastname = 'Mustermann';
        $person->product = 'Musterprodukt';
        $person->function = 'Musterfunktion';
        $person->organisation = 'Musterorganisation';
        $person->address = 'Musteraddresse';
        $person->phonenumber = 'Mustertelefonnummer';
        return $person;
    }

    private function createCitygovPersonCompetence(): CitygovPersonCompetence
    {
        $personCompetence = new CitygovPersonCompetence();
        $personCompetence->prefix = 'A';
        $personCompetence->tin = 'Mustersteuernummer';
        $personCompetence->file = 'Musterktenzeichen';
        $personCompetence->licensePlateLetter = 'MAX';
        $personCompetence->licensePlateRegion = 'XX';
        $personCompetence->licensePlateNumber = '100';
        return $personCompetence;
    }

    /**
     * @return Filter[]
     */
    private function createExpectedFilters(): array
    {
        return [
            new QueryFilter('sp_contenttype:citygovPerson'),
            new NotFilter(new ContentTypeFilter(['pdf'])),
            new QueryFilter("sp_citygov_firstname:(Max* OR Max)"),
            new QueryFilter("sp_citygov_lastname:(Mustermann* OR Mustermann)"),
            new QueryFilter("sp_citygov_product:(Musterprodukt* OR Musterprodukt)"),
            new QueryFilter("sp_citygov_function:(Musterfunktion* OR Musterfunktion)"),
            new QueryFilter("sp_citygov_organisation:(Musterorganisation* OR Musterorganisation)"
                . " OR sp_citygov_organisationtoken:Musterorganisation"),
            new QueryFilter("sp_citygov_address:(Musteraddresse* OR Musteraddresse)"),
            new QueryFilter("sp_citygov_phone:(Mustertelefonnummer* OR Mustertelefonnummer)"),
        ];
    }
}
