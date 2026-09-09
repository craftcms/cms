<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Controls;

use CraftCms\Cms\Cp\Components\ActionMenu;
use CraftCms\Cms\Cp\Components\Button;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Form\ControlPayload;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Form\NestedFormPayload;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Json;
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
 * Identities are bare UUIDs here and in the {@see NestedFormPayload} scopes the browser
 * matches blocks against. On the wire they may carry a `uid:` prefix — the browser puts
 * it on blocks it minted itself, and `_components/fieldtypes/Matrix/block.twig` puts it
 * on `entries` keys but not `sortOrder` values. Fields normalize both halves through
 * {@see ElementHelper::nestedElementDelta()} on the way in.
 *
 * @phpstan-type MatrixControlValue array{
 *     entries: array<string, array<string, mixed>>,
 *     sortOrder: list<string>,
 * }
 */
class Matrix extends Control
{
    /** @var MatrixControlValue */
    #[\Override]
    protected mixed $value = ['entries' => [], 'sortOrder' => []];

    /** @var array<string, string> */
    private array $entryTypes = [];

    /** @var array<string, Form> */
    private array $forms = [];

    /** @var array<string, array{label?: string, icon?: array<string, string>|null, color?: string|null, actions: list<array<string, mixed>>}> */
    private array $blocks = [];

    /** @var array<string, mixed>|null */
    private ?array $create = null;

    private ?string $addLabel = null;

    private ?int $minEntries = null;

    private ?int $maxEntries = null;

    public static function renderHtml(ControlPayload $control, mixed $value, array $attributes, FormHtmlRenderer $renderer): string
    {
        $entries = is_array($value) && is_array($value['entries'] ?? null) ? $value['entries'] : [];
        $order = is_array($value) && is_array($value['sortOrder'] ?? null) ? array_values($value['sortOrder']) : [];
        $editable = $attributes['name'] !== null;
        $forms = collect($control->forms)->keyBy(fn (NestedFormPayload $form): string => array_last($form->scope));
        $entryTypes = self::resolvedEntryTypes($control->props['entryTypes'] ?? null);
        $blocks = is_array($control->props['blocks'] ?? null) ? $control->props['blocks'] : [];
        $types = collect($entryTypes)->keyBy('value');
        $items = '';

        foreach ($order as $index => $uid) {
            $uid = (string) $uid;
            $entry = is_array($entries[$uid] ?? null) ? $entries[$uid] : [];
            $type = (string) ($entry['type'] ?? '');
            $label = (string) ($types[$type]['label'] ?? $type ?: $uid);
            $form = $forms->get($uid);
            $collapsed = (bool) ($entry['collapsed'] ?? false);
            $enabled = (bool) ($entry['enabled'] ?? true);
            $icon = $blocks[$uid]['icon'] ?? null;
            $actions = $enabled ? '' : Html::tag('craft-status', '', [
                'status' => 'disabled',
                'label' => t('Disabled'),
            ]);

            if ($editable) {
                $blockActions = $blocks[$uid]['actions'] ?? [];
                $actions .= ($blockActions === [] ? '' : ActionMenu::make()
                    ->items($blockActions)
                    ->label(t('{type} actions', ['type' => $label]))
                    ->toHtml()
                ).Html::tag('span', Html::tag('craft-reorder-button', '', [
                    'class' => 'move-btn',
                    'position' => match (true) {
                        count($order) === 1 => 'first',
                        $index === 0 => 'first',
                        $index === array_key_last($order) => 'last',
                        default => 'middle',
                    },
                ]), ['class' => 'drag-handle'])
                    // The browser stack deletes through the menu, which posts the
                    // change back; without a Vue control listening, this button is
                    // what `craft-matrix-input` acts on.
                    .Button::make()
                        ->icon('trash')
                        ->accessibleName(t('Remove {type}', ['type' => $label]))
                        ->attributes(['data-form-matrix-remove' => true])
                        ->toHtml();
            }

            $hidden = $editable
                ? Html::hiddenInput("{$attributes['name']}[sortOrder][]", $uid)
                    .Html::hiddenInput("{$attributes['name']}[entries][{$uid}][type]", $type)
                : '';
            $content = $form instanceof NestedFormPayload
                ? $renderer->renderNestedForm($form)
                : Html::tag('craft-spinner', '', ['label' => t('Loading')]);
            $header = Html::tag('div',
                Html::tag('div',
                    Html::tag(
                        'div',
                        (is_array($icon) ? Html::tag('craft-icon', '', $icon) : '').Html::encode($label)
                        // Folded up, the block's own fields aren't there to
                        // identify it, so its UI label stands in for them.
                        .($collapsed
                            ? Html::tag('div', Html::encode((string) ($blocks[$uid]['label'] ?? '')), ['class' => 'preview'])
                            : ''),
                        ['class' => ['blocktype', 'flex', 'flex-nowrap', 'gap-1', 'items-center']],
                    ),
                    ['class' => ['titlebar', 'flex', 'gap-2', 'items-center']],
                ).Html::tag('div', $actions, ['class' => ['actions', 'flex', 'gap-1', 'items-center']]),
                [
                    'slot' => 'header',
                    'class' => ['flex', 'gap-2', 'items-center', 'justify-between', 'w-full'],
                ],
            );
            $items .= Html::tag('div',
                Html::tag('craft-card',
                    $header.$hidden.Html::tag('div', $content, ['class' => 'fields']),
                    ['collapsed' => $collapsed],
                ), [
                    'class' => array_filter([
                        'matrixblock',
                        'js-deletable',
                        $enabled ? null : 'disabled-entry',
                        $collapsed ? 'collapsed' : null,
                    ]),
                    'data-id' => $uid,
                    'data-type' => $type,
                    // The CP's generated colorable rules turn this into the whole
                    // `--c-color-*` alias set, which the card paints from.
                    'data-color' => $blocks[$uid]['color'] ?? null,
                    'role' => 'listitem',
                ]);
        }

        $buttons = '';

        if ($editable) {
            foreach ($entryTypes as $type) {
                $buttons .= Button::make()
                    ->label(count($entryTypes) === 1
                        ? $control->props['addLabel']
                        : t('Add {type}', ['type' => $type['label']]))
                    ->icon('plus')
                    ->attributes([
                        'class' => ['btn', 'add', 'icon', 'dashed', 'wrap'],
                        'data-form-matrix-add' => $type['value'],
                    ])
                    ->toHtml();
            }
        }

        $clear = $editable ? (string) Html::hiddenInput((string) $attributes['name'], '') : '';
        $matrix = Html::tag('div',
            Html::tag('span', '', ['role' => 'status', 'class' => 'sr-only', 'data-status-message' => true])
            .Html::tag('div', $items, [
                'class' => ['blocks', 'grid', 'gap-1'],
                'data-matrix-blocks' => true,
                'role' => 'list',
            ])
            .($buttons === '' ? '' : Html::tag('div', $buttons, [
                'class' => ['buttons', 'flex', 'flex-wrap', 'gap-1', 'items-center', 'mt-3'],
            ])), [
                'id' => $attributes['id'],
                'class' => ['matrix', 'matrix-field'],
            ]);
        $entryTypes = array_map(
            fn (array $type, int $index): array => [
                'id' => $index + 1,
                'handle' => $type['value'],
                'name' => $type['label'],
            ],
            $entryTypes,
            array_keys($entryTypes),
        );

        return Html::tag('craft-matrix-input', $clear.$matrix, [
            'form-control' => true,
            'entry-types' => Json::encode($entryTypes),
            'input-name-prefix' => $attributes['name'],
            'settings' => Json::encode([
                'formControl' => true,
                'maxEntries' => $control->props['maxEntries'],
            ]),
            'min-entries' => $control->props['minEntries'],
        ]);
    }

    public function component(): string
    {
        return 'craft:matrix';
    }

    /** @param array<string, string> $entryTypes */
    public function entryTypes(array $entryTypes): static
    {
        foreach ($entryTypes as $handle => $label) {
            if (! is_string($handle) || $handle === '' || ! is_string($label) || $label === '') {
                throw new InvalidArgumentException('Matrix entry types require non-empty string handles and labels.');
            }
        }

        $this->entryTypes = $entryTypes;

        return $this;
    }

    /** @param array<string, Form> $forms */
    public function forms(array $forms): static
    {
        foreach ($forms as $uid => $form) {
            if (! is_string($uid) || $uid === '' || ! $form instanceof Form) {
                throw new InvalidArgumentException('Matrix Forms require non-empty string identities and Form values.');
            }
        }

        $this->forms = $forms;

        return $this;
    }

    /**
     * Per-block presentation, keyed by identity — the "⋮" menu each block gets.
     *
     * Kept out of the value so it never posts back; blocks the browser minted
     * itself simply have no entry here until the next save materializes them.
     *
     * @param  array<string, array{label?: string, icon?: array<string, string>|null, color?: string|null, actions: list<array<string, mixed>>}>  $blocks
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
                ->map(fn (string $label, string $value): array => compact('value', 'label'))
                ->values()
                ->all(),
            'addLabel' => $this->addLabel ?? t('Add an entry'),
            'minEntries' => $this->minEntries,
            'maxEntries' => $this->maxEntries,
            'blocks' => $this->blocks,
            'create' => $this->create,
        ];
    }

    #[\Override]
    public function nestedForms(mixed $value = null): array
    {
        $value = $this->validatedValue($value);
        $forms = [];

        foreach ($value['sortOrder'] as $uid) {
            $forms[] = [
                'scope' => ['entries', $uid],
                'form' => $this->forms[$uid],
                'refreshable' => true,
            ];
        }

        return $forms;
    }

    /** @return MatrixControlValue */
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

            if (! isset($this->forms[$uid])) {
                throw new InvalidArgumentException("Matrix entry [{$uid}] requires a nested Form.");
            }
        }

        return $value;
    }

    /** @return list<array{value: string, label: string}> */
    private static function resolvedEntryTypes(mixed $entryTypes): array
    {
        if (! is_array($entryTypes)) {
            throw new InvalidArgumentException('Resolved Matrix entry types must be an array.');
        }

        return array_map(function (mixed $entryType): array {
            if (! is_array($entryType) || ! is_string($entryType['value'] ?? null) || ! is_string($entryType['label'] ?? null)) {
                throw new InvalidArgumentException('Resolved Matrix entry types require string values and labels.');
            }

            return ['value' => $entryType['value'], 'label' => $entryType['label']];
        }, array_values($entryTypes));
    }
}
