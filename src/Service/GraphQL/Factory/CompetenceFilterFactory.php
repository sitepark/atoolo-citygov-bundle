<?php

declare(strict_types=1);

namespace Atoolo\CityGov\Service\GraphQL\Factory;

use Atoolo\CityGov\Service\GraphQL\Input\CitygovPersonCompetence;
use PDO;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class CompetenceFilterFactory implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public const TABLE_NAME = 'CompetenceRange';
    public const COLUMN_PERSON = 'person';
    public const COLUMN_PRODUCT = 'product';
    public const COLUMN_GROUP = 'group';
    public const COLUMN_TYPE = 'type';
    public const COLUMN_TYPE_PREFIX = 'prefix';
    public const COLUMN_TYPE_TIN = 'tin';
    public const COLUMN_TYPE_FILE = 'file';
    public const COLUMN_TYPE_LICENSE_PLATE = 'licensePlate';
    public const COLUMN_RANGE_FROM = 'rangeFrom';
    public const COLUMN_RANGE_TO = 'rangeTo';

    private ?PDO $database = null;

    public function __construct(
        protected string $resourceDir,
        protected string $databasePath,
        protected string $databaseName,
    ) {
        $this->resourceDir = str_ends_with($this->resourceDir, '/') ? $this->resourceDir : $this->resourceDir . '/';
        $this->databasePath = str_ends_with($this->databasePath, '/') ? $this->databasePath : $this->databasePath . '/';
    }

    /**
     * @return PDO
     */
    public function getDatabase(): PDO
    {
        if ($this->database == null) {
            $this->database = new PDO('sqlite:' . $this->resourceDir . $this->databasePath . $this->databaseName);
        }
        return $this->database;
    }

    /**
     * @return int[]|null id list of persons that match the filter, or null if no filter was set!
     */
    public function getfilteredPersonIdList(CitygovPersonCompetence $personCompetenceInput): ?array
    {
        $returnValue = null;
        $sqlFilter = $this->getPersonSqlFilter($personCompetenceInput);
        if (empty($sqlFilter)) {
            return null;
        }
        $sql = 'SELECT DISTINCT person FROM ' . self::TABLE_NAME . ' WHERE ' . implode(' AND ', $sqlFilter);
        $stmt = $this->getDatabase()->prepare($sql);
        if ($stmt && $stmt->execute()) {
            $returnValue = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
        return $returnValue;
    }

    /**
     * @return string[]
     */
    public function getPersonSqlFilter(CitygovPersonCompetence $personCompetenceInput): array
    {
        if ($personCompetenceInput->hasFilter() === false) {
            return [];
        }
        $sqlConditions = [];

        if ($personCompetenceInput->prefix !== null) {
            $sqlConditions [] = $this->getConditionRangeBy(
                self::COLUMN_TYPE_PREFIX,
                $personCompetenceInput->prefix,
            );
        }
        if ($personCompetenceInput->tin !== null) {
            $sqlConditions [] = $this->getConditionRangeBy(
                self::COLUMN_TYPE_TIN,
                $personCompetenceInput->tin,
            );
        }
        if ($personCompetenceInput->file !== null) {
            $sqlConditions [] = $this->getConditionRangeBy(
                self::COLUMN_TYPE_FILE,
                $personCompetenceInput->file,
            );
        }
        if (
            $personCompetenceInput->licensePlateRegion !== null ||
            $personCompetenceInput->licensePlateNumber !== null ||
            $personCompetenceInput->licensePlateLetter !== null
        ) {
            $sqlConditions [] = $this->getConditionByLicensePlate(
                $personCompetenceInput->licensePlateRegion,
                $personCompetenceInput->licensePlateLetter,
                $personCompetenceInput->licensePlateNumber,
            );
        }
        return $sqlConditions;
    }

    private function getConditionByLicensePlate(
        ?string $region = '',
        ?string $letter = '',
        ?string $number = '',
    ): string {
        $regionFrom = 'AAA';
        $regionTo = 'ZZZ';
        if (!empty($region)) {
            $regionFrom = $region;
            $regionTo = $region;
            if (mb_strlen($regionFrom) > 3) {
                $regionFrom = mb_substr($regionFrom, 0, 3);
            } elseif (mb_strlen($regionFrom) < 3) {
                $regionFrom = str_pad($regionFrom, 3, 'A', STR_PAD_RIGHT);
            }
            if (mb_strlen($regionTo) > 3) {
                $regionTo = mb_substr($regionTo, 0, 3);
            } elseif (mb_strlen($regionTo) < 3) {
                $regionTo = str_pad($regionTo, 3, 'Z', STR_PAD_RIGHT);
            }
        }

        $letterFrom = 'AA';
        $letterTo = 'ZZ';
        if (!empty($letter)) {
            $letterFrom = $letter;
            $letterTo = $letter;
            if (mb_strlen($letterFrom) > 2) {
                $letterFrom = mb_substr($letterFrom, 0, 2);
            } elseif (mb_strlen($letterFrom) < 2) {
                $letterFrom = str_pad($letterFrom, 2, 'A', STR_PAD_RIGHT);
            }
            if (mb_strlen($letterTo) > 2) {
                $letterTo = mb_substr($letterTo, 0, 2);
            } elseif (mb_strlen($letterTo) < 2) {
                $letterTo = str_pad($letterTo, 2, 'Z', STR_PAD_RIGHT);
            }
        }

        $numberFrom = '0000';
        $numberTo = '9999';
        if (!empty($number)) {
            $numberFrom = $number;
            $numberTo = $number;
            if (mb_strlen($numberFrom) > 4) {
                $numberFrom = mb_substr($numberFrom, 0, 4);
            } elseif (mb_strlen($numberFrom) < 4) {
                $numberFrom = str_pad($numberFrom, 4, '0', STR_PAD_LEFT);
            }
            if (mb_strlen($numberTo) > 4) {
                $numberTo = mb_substr($numberTo, 0, 4);
            } elseif (mb_strlen($numberTo) < 4) {
                $numberTo = str_pad($numberTo, 4, '0', STR_PAD_LEFT);
            }
        }
        $searchFrom = $regionFrom . ' ' . $letterFrom . ' ' . $numberFrom;
        $searchTo   = $regionTo . ' ' . $letterTo . ' ' . $numberTo;

        return $this->getConditionRangeBy(self::COLUMN_TYPE_LICENSE_PLATE, $searchFrom, $searchTo);
    }

    private function normaliseToAlpanumericString(string $str): string
    {
        mb_regex_encoding('UTF-8');
        $s = '';
        $str = mb_strtoupper(trim($str));
        for ($i = 0; $i < mb_strlen($str); $i++) {
            $c = mb_substr($str, $i, 1);
            if (mb_ereg_match('[[:alnum:]]', $c)) {
                $s .= $c;
            } else {
                $s .= ' ';
            }
        }
        return $s;
    }

    private function getConditionRangeBy(string $type, string $rangeFrom, ?string $rangeTo = null): string
    {
        $rangeTo = $rangeTo ?? $rangeFrom;
        $rangeFrom = $this->normaliseToAlpanumericString($rangeFrom);
        $rangeFrom = strtoupper($rangeFrom);
        $rangeFrom = $this->getDatabase()->quote($rangeFrom);

        $rangeTo = $this->normaliseToAlpanumericString($rangeTo);
        $rangeTo = strtoupper($rangeTo);
        $rangeTo = $this->getDatabase()->quote($rangeTo);

        $type = $this->getDatabase()->quote($type);
        return '(' . self::COLUMN_TYPE . ' = ' . $type . ' AND ' . self::COLUMN_RANGE_FROM . ' <= ' . $rangeFrom . ' AND ' . self::COLUMN_RANGE_TO . ' >= ' . $rangeTo . ')';
    }
}
