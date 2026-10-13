<?php

declare(strict_types=1);

namespace Atoolo\CityGov\Service\Indexer\Enricher\GenAi;

use Atoolo\GenAi\Dto\Indexer\ContentSection;
use Atoolo\GenAi\Dto\Indexer\Link;
use Atoolo\GenAi\Dto\Indexer\LinkSection;
use Atoolo\GenAi\Dto\Indexer\TextSection;

/**
 * Turns the content blocks of a citygov service or organisation into
 * sections of the GenAI index.
 *
 * The text of a citygov resource is not part of its content tree, which
 * holds only a placeholder, but of `metadata.<objectType>.content`: one
 * block per topic - description, requirements, fees - each with a list of
 * texts and link lists. A block becomes a section of its own under the
 * headline the website shows, in the order the website shows them; the
 * headline the editor gave a block replaces the default.
 *
 * The text for the service center (`citygov.serviceCenterText`) is not
 * shown on the website and is left out. The application does not embed the
 * links of a link section, so the labels are written into a text section as
 * well, followed by the links.
 */
final class ContentBlockSections
{
    private const TEXT_MODEL_TYPES = ['content.text', 'citygov.regionalText'];

    private const LINK_LIST_MODEL_TYPES = [
        'content.linkList',
        'citygov.regionalLinkList',
    ];

    /**
     * @param array<mixed> $blocks the content blocks by their id
     * @param array<string,string> $headlines the blocks to map, in the order
     *     of the website, with their headlines
     * @return list<ContentSection>
     */
    public static function of(array $blocks, array $headlines): array
    {
        $sections = [];
        foreach ($headlines as $id => $headline) {
            $block = $blocks[$id] ?? null;
            if (!is_array($block)) {
                continue;
            }
            $own = $block['headline'] ?? null;
            if (is_string($own) && trim($own) !== '') {
                $headline = trim($own);
            }
            foreach (self::sections($block, $headline) as $section) {
                $sections[] = $section;
            }
        }
        return $sections;
    }

    /**
     * @param array<mixed> $block
     * @return list<ContentSection>
     */
    private static function sections(array $block, string $headline): array
    {
        $html = '';
        $links = [];
        $contentList = $block['contentList'] ?? null;
        foreach (is_array($contentList) ? $contentList : [] as $content) {
            if (!is_array($content)) {
                continue;
            }
            $modelType = $content['modelType'] ?? null;
            if (in_array($modelType, self::TEXT_MODEL_TYPES, true)) {
                $html .= self::html($content);
            } elseif (in_array($modelType, self::LINK_LIST_MODEL_TYPES, true)) {
                $links = array_merge($links, self::links($content));
            }
        }

        if (!empty($links)) {
            $html .= '<ul>';
            foreach ($links as $link) {
                $html .= '<li>' . htmlspecialchars(
                    $link->label,
                    ENT_QUOTES | ENT_SUBSTITUTE,
                    'UTF-8',
                ) . '</li>';
            }
            $html .= '</ul>';
        }

        $sections = [];
        if (trim(strip_tags($html)) !== '') {
            $sections[] = new TextSection($headline, $html);
        }
        if (!empty($links)) {
            $sections[] = new LinkSection($headline, $links);
        }
        return $sections;
    }

    /**
     * @param array<mixed> $text
     */
    private static function html(array $text): string
    {
        $richText = $text['richText'] ?? null;
        $html = is_array($richText) ? ($richText['text'] ?? null) : null;
        return is_string($html) ? trim($html) : '';
    }

    /**
     * @param array<mixed> $linkList
     * @return list<Link>
     */
    private static function links(array $linkList): array
    {
        $links = [];
        $items = $linkList['items'] ?? null;
        foreach (is_array($items) ? $items : [] as $item) {
            if (!is_array($item)) {
                continue;
            }
            $url = $item['url'] ?? null;
            $label = $item['label'] ?? null;
            if (!is_string($url) || trim($url) === '') {
                continue;
            }
            $label = is_string($label)
                ? trim((string) preg_replace('/\s+/u', ' ', $label))
                : '';
            $links[] = new Link(trim($url), $label !== '' ? $label : trim($url));
        }
        return $links;
    }
}
