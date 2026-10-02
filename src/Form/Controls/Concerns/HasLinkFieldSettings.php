<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Controls\Concerns;

/**
 * @phpstan-type LinkType array{
 *     id: string,
 *     label: string,
 *     kind: 'custom'|'element'|'text',
 *     prefixes?: list<string>,
 *     pattern?: string,
 *     inputAttributes?: array<string, string>,
 *     elementType?: string,
 *     refHandle?: string,
 *     elementSelectConfig?: array<string, mixed>,
 * }
 */
trait HasLinkFieldSettings
{
    /** @var list<LinkType> */
    private array $types = [];

    private bool $showLabelField = false;

    /** @var list<'urlSuffix'|'title'> */
    private array $advancedFields = [];

    /** @param list<LinkType> $types */
    public function types(array $types): static
    {
        $this->types = $types;

        return $this;
    }

    public function showLabelField(bool $showLabelField = true): static
    {
        $this->showLabelField = $showLabelField;

        return $this;
    }

    /** @param list<'urlSuffix'|'title'> $advancedFields */
    public function advancedFields(array $advancedFields): static
    {
        $this->advancedFields = $advancedFields;

        return $this;
    }

    /** @return array{types: list<LinkType>, showLabelField: bool, advancedFields: list<'urlSuffix'|'title'>} */
    protected function linkFieldSettingsProps(): array
    {
        return [
            'types' => $this->types,
            'showLabelField' => $this->showLabelField,
            'advancedFields' => $this->advancedFields,
        ];
    }
}
