<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Controls;

use CraftCms\Cms\Cp\Components\Button;
use CraftCms\Cms\Form\ControlPayload;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Form\NestedFormPayload;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Json;
use Illuminate\Support\Arr;
use InvalidArgumentException;

use function CraftCms\Cms\t;

class Matrix extends Control
{
    #[\Override]
    protected mixed $value = ['entries' => [], 'sortOrder' => []];

    /** @var array<string, string> */
    private array $entryTypes = [];

    /** @var array<string, Form> */
    private array $forms = [];

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
        $types = collect($entryTypes)->keyBy('value');
        $items = '';

        foreach ($order as $index => $uid) {
            $uid = (string) $uid;
            $entry = is_array($entries[$uid] ?? null) ? $entries[$uid] : [];
            $type = (string) ($entry['type'] ?? '');
            $label = (string) ($types[$type]['label'] ?? $type ?: $uid);
            $form = $forms->get($uid);
            $actions = '';

            if ($editable) {
                $actions = Html::tag('craft-reorder-button', '', [
                    'class' => 'move-btn',
                    'position' => match (true) {
                        count($order) === 1 => 'first',
                        $index === 0 => 'first',
                        $index === array_key_last($order) => 'last',
                        default => 'middle',
                    },
                ]).Button::make()
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
            $titlebar = Html::tag('div',
                Html::tag('div', Html::encode($label), ['class' => ['blocktype', 'cp:flex', 'cp:flex-nowrap', 'flex-gap-xs']])
                .Html::tag('div', '', ['class' => 'preview']),
                ['class' => 'titlebar'],
            );
            $items .= Html::tag('div', $hidden.$titlebar
                .($editable ? Html::tag('div', $actions, ['class' => 'actions']) : '')
                .Html::tag('div', $content, ['class' => 'fields']), [
                    'class' => ['matrixblock', 'js-deletable'],
                    'data-id' => $uid,
                    'data-type' => $type,
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
            Html::tag('span', '', ['role' => 'status', 'class' => 'visually-hidden', 'data-status-message' => true])
            .Html::tag('div', $items, ['class' => 'blocks', 'role' => 'list'])
            .($buttons === '' ? '' : Html::tag('div', $buttons, ['class' => 'buttons'])), [
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

    /** @return array{entries: array<string, array<string, mixed>>, sortOrder: list<string>} */
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
