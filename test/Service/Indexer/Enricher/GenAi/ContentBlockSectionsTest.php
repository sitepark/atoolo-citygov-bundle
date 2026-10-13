<?php

declare(strict_types=1);

namespace Atoolo\CityGov\Test\Service\Indexer\Enricher\GenAi;

use Atoolo\CityGov\Service\Indexer\Enricher\GenAi\ContentBlockSections;
use Atoolo\GenAi\Dto\Indexer\Link;
use Atoolo\GenAi\Dto\Indexer\LinkSection;
use Atoolo\GenAi\Dto\Indexer\TextSection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ContentBlockSections::class)]
class ContentBlockSectionsTest extends TestCase
{
    public function testBlocksFollowTheOrderOfTheHeadlines(): void
    {
        $sections = ContentBlockSections::of(
            [
                'rates' => $this->block([$this->text('<p>46 Euro</p>')]),
                'info' => $this->block([
                    $this->text('<p>Eins</p>'),
                    $this->text('<p>Zwei</p>', 'citygov.regionalText'),
                ]),
            ],
            ['info' => 'Beschreibung', 'rates' => 'Gebühren'],
        );

        $this->assertEquals(
            [
                new TextSection('Beschreibung', '<p>Eins</p><p>Zwei</p>'),
                new TextSection('Gebühren', '<p>46 Euro</p>'),
            ],
            $sections,
            'unexpected sections',
        );
    }

    public function testBlockWithoutHeadlineIsLeftOut(): void
    {
        $sections = ContentBlockSections::of(
            ['notice' => $this->block([$this->text('<p>Hinweis</p>')])],
            ['info' => 'Beschreibung'],
        );

        $this->assertEmpty($sections, 'a block without headline is unknown');
    }

    public function testHeadlineOfTheEditorWins(): void
    {
        $block = $this->block([$this->text('<p>Text</p>')]);
        $block['headline'] = ' Was kostet das? ';

        $sections = ContentBlockSections::of(
            ['rates' => $block],
            ['rates' => 'Gebühren'],
        );

        $this->assertEquals(
            [new TextSection('Was kostet das?', '<p>Text</p>')],
            $sections,
            'the headline of the block should be used',
        );
    }

    public function testLinkListBecomesTextAndLinks(): void
    {
        $sections = ContentBlockSections::of(
            ['forms' => $this->block([[
                'modelType' => 'citygov.regionalLinkList',
                'items' => [
                    [
                        'label' => 'Vollmacht <zur> Abholung',
                        'url' => '/medien/vollmacht.pdf',
                    ],
                    ['url' => '/medien/antrag.pdf'],
                    ['label' => 'ohne Adresse'],
                ],
            ]])],
            ['forms' => 'Formulare'],
        );

        $this->assertEquals(
            [
                new TextSection(
                    'Formulare',
                    '<ul><li>Vollmacht &lt;zur&gt; Abholung</li>'
                    . '<li>/medien/antrag.pdf</li></ul>',
                ),
                new LinkSection('Formulare', [
                    new Link('/medien/vollmacht.pdf', 'Vollmacht <zur> Abholung'),
                    new Link('/medien/antrag.pdf', '/medien/antrag.pdf'),
                ]),
            ],
            $sections,
            'unexpected link sections',
        );
    }

    public function testServiceCenterTextAndEmptyTextAreLeftOut(): void
    {
        $sections = ContentBlockSections::of(
            ['info' => $this->block([
                $this->text('<p>Nur intern</p>', 'citygov.serviceCenterText'),
                $this->text('<p> </p>'),
                'kein Model',
            ])],
            ['info' => 'Beschreibung'],
        );

        $this->assertEmpty($sections, 'no text should be left');
    }

    /**
     * @param list<mixed> $contentList
     * @return array<string,mixed>
     */
    private function block(array $contentList): array
    {
        return [
            'modelType' => 'citygov.contentBlock',
            'contentList' => $contentList,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function text(string $html, string $modelType = 'content.text'): array
    {
        return [
            'modelType' => $modelType,
            'richText' => ['modelType' => 'html.richText', 'text' => $html],
        ];
    }
}
