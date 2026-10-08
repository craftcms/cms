<?php

declare(strict_types=1);

namespace Workbench\App\Http\Controllers;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Cp\Enums\Appearance;
use CraftCms\Cms\Cp\Html\ContentHtml;
use CraftCms\Cms\Cp\Html\ElementHtml;
use CraftCms\Cms\Cp\Html\PreviewHtml;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Form\Controls\ElementSelect;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\User\Elements\User;

/**
 * Chips as the control panel renders them, in the containers they render in,
 * built from whatever elements the install has.
 *
 * @phpstan-type ChipSection array{title: string, description: string, html: string}
 */
class ChipsController
{
    public function __construct(
        private readonly ElementHtml $elementHtml,
        private readonly PreviewHtml $previewHtml,
        private readonly ContentHtml $contentHtml,
    ) {}

    public function __invoke(): CpScreenResponse
    {
        $entries = Entry::find()->section('*')->status(null)->limit(6)->all();
        $users = User::find()->status(null)->limit(4)->all();
        $assets = Asset::find()->kind('image')->limit(4)->all();
        $entryList = $this->listPayload(Entry::class, $entries);

        return new CpScreenResponse()
            ->title('Chips')
            ->addCrumb('Workbench', 'workbench/chips')
            ->inertiaPage('workbench/Chips', [
                'lists' => array_filter([
                    'Entries' => $entryList,
                    'Entries with thumbnails' => $this->withFakeThumbs($entryList),
                    'Users' => $this->listPayload(User::class, $users),
                    'Assets' => $this->listPayload(Asset::class, $assets),
                ]),
                'sections' => array_values(array_filter([
                    $this->indexTitleCells($entries),
                    $this->tableCells([...$entries, ...$assets]),
                    $this->legacyLists($entries),
                    $this->sizes([...$entries, ...$assets]),
                    $this->thumbsView($assets ?: $entries),
                    $this->structure($entries),
                    $this->metadata($entries[0] ?? null),
                    $this->path($entries),
                    $this->thumbnails($entries),
                    $this->componentChips($entries),
                ])),
            ]);
    }

    /**
     * What a relation field hands `ElementChips`.
     *
     * @param  class-string<ElementInterface>  $elementType
     * @param  ElementInterface[]  $elements
     * @return list<array<string, mixed>>
     */
    private function listPayload(string $elementType, array $elements): array
    {
        if ($elements === []) {
            return [];
        }

        return ElementSelect::make('chips')
            ->elementType($elementType)
            ->props(array_map(fn (ElementInterface $element) => $element->id, $elements))['elements'];
    }

    /**
     * @param  Entry[]  $entries
     * @return ChipSection|null
     */
    private function indexTitleCells(array $entries): ?array
    {
        return $this->section('Element index title cells', 'A plain chip, as the first column of the element index.',
            $this->table(array_map(fn (Entry $entry): array => [
                $this->elementHtml->elementChipHtml($entry, ['context' => 'index', 'appearance' => 'plain']),
                Html::encode($entry->getSection()->name ?? ''),
            ], $entries)),
        );
    }

    /**
     * @param  ElementInterface[]  $elements
     * @return ChipSection|null
     */
    private function tableCells(array $elements): ?array
    {
        return $this->section('Table view cells', 'The legacy table view: plain, with a thumbnail and status.',
            $this->table(array_map(fn (ElementInterface $element): array => [
                $this->elementHtml->elementChipHtml($element, [
                    'context' => 'index',
                    'showThumb' => true,
                    'showStatus' => true,
                    'showProvisionalDraftLabel' => true,
                    'appearance' => 'plain',
                    'hyperlink' => true,
                ]),
            ], $elements)),
        );
    }

    /**
     * @param  Entry[]  $entries
     * @return ChipSection|null
     */
    private function legacyLists(array $entries): ?array
    {
        $chips = fn (array $config): string => implode('', array_map(
            fn (Entry $entry): string => Html::tag('li', $this->elementHtml->elementChipHtml($entry, $config)),
            $entries,
        ));

        return $this->section('Legacy chip lists', 'Server-rendered relation lists, stacked and inline, with action menus.',
            Html::tag('ul', $chips(['context' => 'field', 'showActionMenu' => true]), ['class' => 'elements chips']).
            Html::tag('h3', 'Inline', ['class' => 'mt-lg']).
            Html::tag('ul', $chips(['context' => 'field', 'showActionMenu' => true]), ['class' => 'elements chips inline-chips']).
            Html::tag('h3', 'Selectable', ['class' => 'mt-lg']).
            Html::tag('ul', $chips(['context' => 'index', 'checkbox' => true, 'selectable' => true]), ['class' => 'elements chips']),
        );
    }

    /**
     * @param  ElementInterface[]  $elements
     * @return ChipSection|null
     */
    private function sizes(array $elements): ?array
    {
        $html = '';

        foreach ([ElementHtml::CHIP_SIZE_SMALL, ElementHtml::CHIP_SIZE_LARGE] as $size) {
            $html .= Html::tag('h3', ucfirst($size), ['class' => 'mt-lg']).
                Html::tag('ul', implode('', array_map(
                    fn (ElementInterface $element): string => Html::tag('li', $this->elementHtml->elementChipHtml($element, [
                        'context' => 'field',
                        'size' => $size,
                        'showThumb' => true,
                        'showActionMenu' => true,
                    ])),
                    $elements,
                )), ['class' => 'elements chips']);
        }

        return $this->section('Sizes', 'Each server-rendered size, with thumbnails and action menus.', $html);
    }

    /**
     * @param  ElementInterface[]  $elements
     * @return ChipSection|null
     */
    private function thumbsView(array $elements): ?array
    {
        return $this->section('Thumbs view', 'Large, selectable chips, as the thumbs view renders them.',
            Html::tag('ul', implode('', array_map(
                fn (ElementInterface $element): string => Html::tag('li', $this->elementHtml->elementChipHtml($element, [
                    'context' => 'index',
                    'size' => 'large',
                    'selectable' => true,
                    'sortable' => true,
                    'hyperlink' => true,
                ])),
                $elements,
            )), ['class' => 'elements chips']),
        );
    }

    /**
     * @param  Entry[]  $entries
     * @return ChipSection|null
     */
    private function structure(array $entries): ?array
    {
        return $this->section('Structure view', 'Index chips in structure rows.',
            Html::tag('ul', implode('', array_map(
                fn (Entry $entry): string => Html::tag('li', Html::tag('div',
                    $this->elementHtml->elementChipHtml($entry, ['context' => 'index']),
                    ['class' => 'row'],
                )),
                $entries,
            )), ['class' => 'structure']),
        );
    }

    /** @return ChipSection|null */
    private function metadata(?Entry $entry): ?array
    {
        if ($entry === null) {
            return null;
        }

        $authors = $entry->getAuthors();

        return $this->section('Sidebar metadata', 'Chips as values in an element’s sidebar metadata.',
            Html::tag('div', $this->contentHtml->metadataHtml([
                'Section' => fn () => ($section = $entry->getSection())
                    ? $this->elementHtml->chipHtml($section, ['appearance' => Appearance::Plain->value, 'showThumb' => false])
                    : false,
                'Entry type' => fn () => $this->elementHtml->chipHtml($entry->getType(), ['appearance' => Appearance::Plain->value]),
                'Authors' => fn () => $authors ? $this->previewHtml->elementPreviewHtml($authors) : false,
                'Status' => Html::encode($entry->getStatus() ?? ''),
            ]), ['style' => 'max-width: 20rem']),
        );
    }

    /**
     * @param  Entry[]  $entries
     * @return ChipSection|null
     */
    private function path(array $entries): ?array
    {
        return $this->section('Ancestor path', 'The ancestors attribute: a path of chips.',
            Html::tag('ul', implode('', array_map(
                fn (Entry $entry): string => Html::tag('li', $this->elementHtml->elementChipHtml($entry)),
                array_slice($entries, 0, 3),
            )), ['class' => 'path']),
        );
    }

    /**
     * @param  Entry[]  $entries
     * @return ChipSection|null
     */
    private function componentChips(array $entries): ?array
    {
        $types = [];

        foreach ($entries as $entry) {
            $type = $entry->getType();
            $types[$type->id] ??= $type;
        }

        return $this->section('Entry types', 'Non-element chips, as the entry types index renders them.',
            implode('', array_map(
                fn ($type): string => Html::tag('div',
                    $this->elementHtml->chipHtml($type, [
                        'labelHtml' => Html::a(Html::encode($type->getUiLabel()), $type->getCpEditUrl()),
                    ]),
                    ['class' => 'flex gap-sm items-center row-wrap mb-sm'],
                ),
                $types,
            )),
        );
    }

    /**
     * Entries rarely have thumbnails, so these chips get generated ones, in
     * each context a thumbnail can appear in.
     *
     * @param  Entry[]  $entries
     * @return ChipSection|null
     */
    private function thumbnails(array $entries): ?array
    {
        $chip = fn (Entry $entry, int $i, array $config): string => $this->withFakeThumb(
            $this->elementHtml->elementChipHtml($entry, $config),
            $i,
        );
        $list = fn (array $config, string $class = 'elements chips'): string => Html::tag('ul', implode('', array_map(
            fn (Entry $entry, int $i): string => Html::tag('li', $chip($entry, $i, $config)),
            $entries,
            array_keys($entries),
        )), ['class' => $class]);

        return $this->section('Thumbnails', 'Generated thumbnails, including wide and tall images, at each size and in a table.',
            Html::tag('h3', 'Small').
            $list(['context' => 'field', 'showActionMenu' => true]).
            Html::tag('h3', 'Large', ['class' => 'mt-lg']).
            $list(['context' => 'field', 'size' => 'large', 'showActionMenu' => true]).
            Html::tag('h3', 'Inline', ['class' => 'mt-lg']).
            $list(['context' => 'field', 'showActionMenu' => true], 'elements chips inline-chips').
            Html::tag('h3', 'Table cells', ['class' => 'mt-lg']).
            $this->table(array_map(
                fn (Entry $entry, int $i): array => [$chip($entry, $i, ['context' => 'index', 'appearance' => 'plain'])],
                $entries,
                array_keys($entries),
            )),
        );
    }

    /**
     * @param  list<array<string, mixed>>  $list
     * @return list<array<string, mixed>>
     */
    private function withFakeThumbs(array $list): array
    {
        return array_map(
            fn (array $element, int $i): array => ['thumbHtml' => $this->fakeThumbHtml($i)] + $element,
            $list,
            array_keys($list),
        );
    }

    /** Adds a generated thumbnail to a chip, as `chipHtml()` would for an element with one. */
    private function withFakeThumb(string $chipHtml, int $index): string
    {
        $chipHtml = Html::modifyTagAttributes($chipHtml, ['show-thumb' => true]);

        return Html::prependToTag($chipHtml, Html::tag('div', $this->fakeThumbHtml($index), ['slot' => 'thumbnail']));
    }

    /**
     * A gradient image, cycling through square, wide, and tall so each fit is
     * covered. Inline, so it renders without any network access.
     */
    private function fakeThumbHtml(int $index): string
    {
        $colors = [['#6366f1', '#ec4899'], ['#0ea5e9', '#22c55e'], ['#f59e0b', '#ef4444'], ['#14b8a6', '#8b5cf6']];
        [$from, $to] = $colors[$index % count($colors)];
        [$width, $height] = [[120, 120], [240, 120], [120, 240]][$index % 3];

        $svg = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%1$d" height="%2$d"><defs><linearGradient id="g" x2="1" y2="1"><stop stop-color="%3$s"/><stop offset="1" stop-color="%4$s"/></linearGradient></defs><rect width="%1$d" height="%2$d" fill="url(#g)"/></svg>',
            $width, $height, $from, $to,
        );

        return Html::tag('craft-thumbnail', '', [
            'src' => 'data:image/svg+xml,'.rawurlencode($svg),
            'mode' => 'fit',
            'alt' => '',
        ]);
    }

    /** @param list<list<string>> $rows */
    private function table(array $rows): string
    {
        return Html::tag('table', Html::tag('tbody', implode('', array_map(
            fn (array $cells): string => Html::tag('tr', implode('', array_map(
                fn (string $cell): string => Html::tag('td', $cell),
                $cells,
            ))),
            $rows,
        ))), ['class' => 'data fullwidth']);
    }

    /** @return ChipSection|null */
    private function section(string $title, string $description, string $html): ?array
    {
        // Without elements to render, a section has no chips to show.
        return str_contains($html, '<craft-chip')
            ? ['title' => $title, 'description' => $description, 'html' => $html]
            : null;
    }
}
