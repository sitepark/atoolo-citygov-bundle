<?php

declare(strict_types=1);

namespace Atoolo\CityGov\Test\Service\GraphQL\Factory;

use Atoolo\CityGov\Service\GraphQL\Factory\CompetenceFilterFactory;
use Atoolo\CityGov\Service\GraphQL\Input\CitygovPersonCompetence;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PDO;

#[CoversClass(CompetenceFilterFactory::class)]
class CompetenceFilterFactoryTest extends TestCase
{
    private string $resourcePath = __DIR__ . '/../../../..';
    private string $databasePath = '/test';
    private string $databaseName = 'competence.sqlite.db';
    private ?CompetenceFilterFactory $factory = null;

    public function testCreate()
    {
        self::assertNotNull($this->factory);
    }

    public function testGetDatabase()
    {
        $dbPdo = $this->factory->getDatabase();
        self::assertNotNull($dbPdo);
    }

    public function testNoFilter()
    {
        $filter = new CitygovPersonCompetence();
        $returnValue = $this->factory->getfilteredPersonIdList($filter);
        self::assertNull($returnValue);
    }

    public function testPrefixFilter()
    {
        $filter = new CitygovPersonCompetence();
        $filter->prefix = 'b';
        $returnValue = $this->factory->getfilteredPersonIdList($filter);
        self::assertEquals([1001], $returnValue);
    }

    public function testPrefixNoMatchFilter()
    {
        $filter = new CitygovPersonCompetence();
        $filter->prefix = 'xyz';
        $returnValue = $this->factory->getfilteredPersonIdList($filter);
        self::assertEquals([], $returnValue);
    }
    public function testTinFilter()
    {
        $factory = $this->getFactory();
        $filter = new CitygovPersonCompetence();
        $filter->tin = 'ST25';
        $returnValue = $factory->getfilteredPersonIdList($filter);
        self::assertEquals([1001], $returnValue);
    }
    public function testFileFilter()
    {
        $filter = new CitygovPersonCompetence();
        $filter->file = 'f-i';
        $returnValue = $this->factory->getfilteredPersonIdList($filter);
        self::assertEquals([1001], $returnValue);
    }

    public function testLicensePlateFilter()
    {
        $factory = $this->getFactory();
        $filter = new CitygovPersonCompetence();
        $filter->licensePlateRegion = 'ms';
        $filter->licensePlateLetter = 'h';
        $filter->licensePlateNumber = '25';
        $returnValue = $factory->getfilteredPersonIdList($filter);
        self::assertEquals([1001], $returnValue);
    }
    public function testLicensePlateFilterNoMatch()
    {
        $factory = $this->getFactory();
        $filter = new CitygovPersonCompetence();
        $filter->licensePlateRegion = 'ms';
        $filter->licensePlateLetter = 'aa';
        $returnValue = $factory->getfilteredPersonIdList($filter);
        self::assertEquals([], $returnValue);
    }
    public function testLicensePlateFilterLongStrings()
    {
        $factory = $this->getFactory();
        $filter = new CitygovPersonCompetence();
        $filter->licensePlateRegion = 'msabc';
        $filter->licensePlateLetter = 'hijk';
        $filter->licensePlateNumber = '25678';
        $returnValue = $factory->getfilteredPersonIdList($filter);
        self::assertEquals([1001], $returnValue);
    }

    public function setUp(): void
    {
        $this->getFactory();
        $test = $this->resourcePath . '/' . $this->databasePath . '/' . $this->databaseName;
        $db = new PDO('sqlite:' . $this->resourcePath . '/' . $this->databasePath . '/' . $this->databaseName);
        $db->exec("CREATE TABLE " . CompetenceFilterFactory::TABLE_NAME . " ('person','product','group','type','rangeFrom','rangeTo')");
        $db->exec("insert into " . CompetenceFilterFactory::TABLE_NAME . " values (1001,2001,5,'prefix','A', 'H')");
        $db->exec("insert into " . CompetenceFilterFactory::TABLE_NAME . " values (1001,2001,5,'tin','ST20', 'ST90')");
        $db->exec("insert into " . CompetenceFilterFactory::TABLE_NAME . " values (1001,2001,5,'file','F G', 'F K')");
        $db->exec("insert into " . CompetenceFilterFactory::TABLE_NAME . " values (1001,2001,5,'licensePlate','MSA BB 0010', 'MZZ HH, 0050')");
    }

    public function tearDown(): void
    {
        $file = $this->resourcePath . '/' . $this->databasePath . '/' . $this->databaseName;
        if (file_exists($file)) {
            unlink($file);
        }
    }

    /**
     * @return CompetenceFilterFactory
     */
    private function getFactory(): CompetenceFilterFactory
    {
        if ($this->factory === null) {
            $this->factory = new CompetenceFilterFactory(
                $this->resourcePath,
                $this->databasePath,
                $this->databaseName,
            );
        }
        return $this->factory;
    }
}
