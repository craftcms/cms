<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ui\Controls;

use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Ui\ControlPayload;
use CraftCms\Cms\Ui\NestedUiPayload;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiHtmlRenderer;
use Illuminate\Support\Arr;
use InvalidArgumentException;

use function CraftCms\Cms\t;

/**
 * The repeater Control behind nested element fields (Matrix, Addresses).
 *
 * Its value is a delta envelope keyed by nested element identity:
 *
 * ```
 * [
 *     'entries' => ['<uuid>' => ['type' => '<entry type handle>', ...]],
 *     'sortOrder' => ['<uuid>', ...],
 * ]
 * ```
 *
 * Identities are bare UUIDs here and in the {@see NestedUiPayload} scopes the browser
 * matches blocks against. On the wire they may carry a `uid:` prefix — the browser puts
 * it on blocks it minted itself, and `_components/fieldtypes/Matrix/block.twig` puts it
 * on `entries` keys but not `sortOrder` values. Fields normalize both halves through
 * {@see ElementHelper::nestedElementDelta()} on the way in.
 *
 * @phpstan-type EntryTypeDescriptor array{label: string, icon?: array<string, string>|null, color?: string|null, group?: string|null}
 * @phpstan-type NestedElementBlocksValue array{
 *     entries: array<string, array<string, mixed>>,
 *     sortOrder: list<string>,
 * }
 *
 * @since 6.0.0
 */
class NestedElementBlocks extends Control
{
    /** @var NestedElementBlocksValue */
    #[\Override]
    protected mixed $value = ['entries' => [], 'sortOrder' => []];

    /** @var array<string, EntryTypeDescriptor> */
    private array $entryTypes = [];

    /** @var list<string>|null */
    private ?array $createEntryTypes = null;

    /** @var array<string, Ui> */
    private array $uis = [];

    /** @var array<string, array{label?: string, icon?: array<string, string>|null, color?: string|null, actions: list<array<string, mixed>>, data?: array<string, int|string>, error?: bool}> */
    private array $blocks = [];

    /** @var array<string, mixed>|null */
    private ?array $create = null;

    private ?string $elementType = null;

    private ?string $siteName = null;

    private ?string $addLabel = null;

    private ?int $minEntries = null;

    private ?int $maxEntries = null;

    public static function renderHtml(ControlPayload $control, mixed $value, array $attributes, UiHtmlRenderer $renderer): string
    {
        $path = $control->path;
        $name = $attributes['anchorName'] ?? $attributes['name']
            ?? array_shift($path).implode('', array_map(fn (string $segment): string => "[{$segment}]", $path));
        $create = $control->props['create'] ?? null;

        return Html::tag('craft-entry-field-layout-form', Html::hiddenInput($name, '', [
            'disabled' => true,
            'data-form-field-name' => true,
        ]), [
            'id' => $attributes['id'],
            'data-payload' => Json::encode($renderer->controlUi($control, $value), JSON_HEX_AMP | JSON_THROW_ON_ERROR),
            'data-field-path' => Json::encode($control->path),
            'data-owner' => $create === null ? null : Json::encode([
                'elementType' => $create['ownerElementType'],
                'elementId' => $create['ownerId'],
                'siteId' => $create['siteId'],
            ]),
        ]);
    }

    public function component(): string
    {
        return 'craft:nested-element-blocks';
    }

    /**
     * The types a block may be, keyed by handle.
     *
     * A bare label is enough for a field with one kind of block (Addresses). A
     * Matrix passes the descriptor, so the add buttons and menu can show each
     * type's own icon and colour and file it under its group.
     *
     * @param  array<string, string|EntryTypeDescriptor>  $entryTypes
     */
    public function entryTypes(array $entryTypes): static
    {
        $resolved = [];

        foreach ($entryTypes as $handle => $entryType) {
            $label = is_array($entryType) ? ($entryType['label'] ?? null) : $entryType;

            if (! is_string($handle) || $handle === '' || ! is_string($label) || $label === '') {
                throw new InvalidArgumentException('Matrix entry types require non-empty string handles and labels.');
            }

            $resolved[$handle] = is_array($entryType)
                ? ['label' => $label] + $entryType
                : ['label' => $label];
        }

        $this->entryTypes = $resolved;

        return $this;
    }

    /** @param list<string> $handles */
    public function createEntryTypes(array $handles): static
    {
        $this->createEntryTypes = $handles;

        return $this;
    }

    /** @param array<string, Ui> $uis */
    public function uis(array $uis): static
    {
        foreach ($uis as $uid => $form) {
            if (! is_string($uid) || $uid === '' || ! $form instanceof Ui) {
                throw new InvalidArgumentException('Matrix UI definitions require non-empty string identities and Ui values.');
            }
        }

        $this->uis = $uis;

        return $this;
    }

    /**
     * Per-block presentation, keyed by identity — the "⋮" menu each block gets.
     *
     * Kept out of the value so it never posts back; blocks the browser minted
     * itself simply have no entry here until the next save materializes them.
     *
     * @param  array<string, array{label?: string, icon?: array<string, string>|null, color?: string|null, actions: list<array<string, mixed>>, data?: array<string, int|string>, error?: bool}>  $blocks
     */
    public function blocks(array $blocks): static
    {
        $this->blocks = $blocks;

        return $this;
    }

    /**
     * What the browser needs to have the server mint a new block, or null when it
     * can't — an unsaved owner, or a nested element field that isn't Matrix-backed
     * (Addresses uses this Control too). Without it the browser mints the block
     * itself and the next save materializes it.
     *
     * @param  array<string, mixed>|null  $create
     */
    public function create(?array $create): static
    {
        $this->create = $create;

        return $this;
    }

    /**
     * The element class the blocks are, for the CP's element clipboard — copy and
     * paste both address elements by type. Null for a nested element field that
     * doesn't know or doesn't offer them.
     */
    public function elementType(?string $elementType): static
    {
        $this->elementType = $elementType;

        return $this;
    }

    /** The localized site name, or null when the nested elements have one site. */
    public function siteName(?string $siteName): static
    {
        $this->siteName = $siteName;

        return $this;
    }

    public function addLabel(string $addLabel): static
    {
        $this->addLabel = $addLabel;

        return $this;
    }

    public function minEntries(?int $minEntries): static
    {
        if ($minEntries !== null && $minEntries < 0) {
            throw new InvalidArgumentException('Matrix minimum entries cannot be negative.');
        }

        $this->minEntries = $minEntries;

        return $this;
    }

    public function maxEntries(?int $maxEntries): static
    {
        if ($maxEntries !== null && $maxEntries < 1) {
            throw new InvalidArgumentException('Matrix maximum entries must be at least 1.');
        }

        $this->maxEntries = $maxEntries;

        return $this;
    }

    /** @return array{entries: array<string, mixed>, sortOrder: list<string>} */
    #[\Override]
    public function emptyValue(): mixed
    {
        return ['entries' => [], 'sortOrder' => []];
    }

    #[\Override]
    public function nestsUis(): bool
    {
        return true;
    }

    #[\Override]
    public function props(mixed $value = null): array
    {
        if ($this->entryTypes === []) {
            throw new InvalidArgumentException('Matrix Controls require at least one entry type.');
        }

        if ($this->minEntries !== null && $this->maxEntries !== null && $this->minEntries > $this->maxEntries) {
            throw new InvalidArgumentException('Matrix minimum entries cannot exceed the maximum.');
        }

        $this->validatedValue($value);

        return [
            'entryTypes' => collect($this->entryTypes)
                ->map(fn (array $entryType, string $value): array => ['value' => $value] + $entryType)
                ->values()
                ->all(),
            'createEntryTypes' => $this->createEntryTypes,
            'addLabel' => $this->addLabel ?? t('Add an entry'),
            'minEntries' => $this->minEntries,
            'maxEntries' => $this->maxEntries,
            'blocks' => $this->blocks,
            'create' => $this->create,
            'elementType' => $this->elementType,
            'siteName' => $this->siteName,
        ];
    }

    #[\Override]
    public function nestedUis(mixed $value = null): array
    {
        $value = $this->validatedValue($value);
        $uis = [];

        foreach ($value['sortOrder'] as $uid) {
            $uis[] = [
                'scope' => ['entries', $uid],
                'form' => $this->uis[$uid],
                'refreshable' => true,
            ];
        }

        return $uis;
    }

    /** @return NestedElementBlocksValue */
    private function validatedValue(mixed $value): array
    {
        if (! is_array($value) || ! is_array(Arr::get($value, 'entries')) || ! is_array(Arr::get($value, 'sortOrder'))) {
            throw new InvalidArgumentException('Matrix values must contain entries and sortOrder arrays.');
        }

        if (count($value['sortOrder']) !== count(array_unique($value['sortOrder']))) {
            throw new InvalidArgumentException('Matrix sortOrder identities must be unique.');
        }

        foreach ($value['sortOrder'] as $uid) {
            $entry = is_string($uid) ? ($value['entries'][$uid] ?? null) : null;

            if (! is_array($entry) || ! is_string($entry['type'] ?? null) || ! isset($this->entryTypes[$entry['type']])) {
                throw new InvalidArgumentException('Matrix entries require ordered identities and registered types.');
            }

            if (! isset($this->uis[$uid])) {
                throw new InvalidArgumentException("Matrix entry [{$uid}] requires a nested Ui.");
            }
        }

        return $value;
    }
}
