<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Controls;

use Illuminate\Support\Arr;

/**
 * A {@see Combobox} with an extra "create a new one" option that opens a slideout at
 * {@see self::createUrl()} instead of selecting a value directly — the modern equivalent of
 * legacy's `componentSelect` "Choose ▾ + Create" pairing, for a resource with its own real CP
 * edit screen (reached at that URL) but no Craft Element identity of its own (so
 * {@see ElementSelect} doesn't apply).
 *
 * The caller builds `options()` as usual, including the trigger option itself (value
 * {@see self::createValue()}, default `__add__`) — this control only carries the wiring the
 * client needs to act on it once chosen; it doesn't inject the option itself, the same way
 * every other Combobox/Choice leaves option-building entirely to the caller.
 *
 * On save, the created record is read from the response's {@see self::resultKey()} — matching
 * the `$modelName` the create screen's own save action passes to
 * `RespondsWithFlash::asModelSuccess()` — and appended to the option list as the new selection,
 * using {@see self::labelField()}/{@see self::valueField()} (default `name`/`id`) to build its
 * label/value.
 */
class ComboboxCreate extends Combobox
{
    private ?string $createUrl = null;

    private string $createValue = '__add__';

    private string $resultKey = '';

    private string $labelField = 'name';

    private string $valueField = 'id';

    public function component(): string
    {
        return 'craft:combobox-create';
    }

    /** The URL of the resource's own create screen, opened in a slideout. */
    public function createUrl(?string $createUrl): static
    {
        $this->createUrl = $createUrl;

        return $this;
    }

    /** The option value that opens the create slideout instead of being selected directly. */
    public function createValue(string $createValue): static
    {
        $this->createValue = $createValue;

        return $this;
    }

    /**
     * The key the created record is nested under in the save response — the same `$modelName`
     * the create screen's save action passes to `RespondsWithFlash::asModelSuccess()`.
     */
    public function resultKey(string $resultKey): static
    {
        $this->resultKey = $resultKey;

        return $this;
    }

    /** The created record's field to use as the new option's label. Defaults to `name`. */
    public function labelField(string $labelField): static
    {
        $this->labelField = $labelField;

        return $this;
    }

    /** The created record's field to use as the new option's value. Defaults to `id`. */
    public function valueField(string $valueField): static
    {
        $this->valueField = $valueField;

        return $this;
    }

    #[\Override]
    public function props(mixed $value = null): array
    {
        return [
            ...parent::props($value),
            ...Arr::whereNotNull([
                'createUrl' => $this->createUrl,
                'createValue' => $this->createValue,
                'resultKey' => $this->resultKey ?: null,
                'labelField' => $this->labelField,
                'valueField' => $this->valueField,
            ]),
        ];
    }
}
