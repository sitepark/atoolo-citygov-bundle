<?php

declare(strict_types=1);

namespace Atoolo\CityGov\Test\Service\Search;

use Atoolo\CityGov\Service\Search\SolrSuggestFactory;
use Atoolo\Search\Service\IndexName;
use Atoolo\Search\Service\Search\QueryTemplateResolver;
use Atoolo\Search\Service\Search\Schema2xFieldMapper;
use Atoolo\Search\Service\SolrClientFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SolrSuggestFactory::class)]
class SolrSuggestFactoryTest extends TestCase
{
    public function testSolrSuggestFactory(): void
    {
        $factory = new SolrSuggestFactory(
            $this->createMock(IndexName::class),
            $this->createMock(SolrClientFactory::class),
            $this->createMock(Schema2xFieldMapper::class),
            $this->createMock(QueryTemplateResolver::class),
        );
        self::assertNotNull($factory);

        $suggestField =  $factory->createForSolrField('sp_id');
        self::assertNotNull($suggestField);
    }
}
