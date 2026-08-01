<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\FormDefinitions\Elements;

class FieldLayoutInput extends InputElement
{
    /**
     * @var list<array{
     *     key: string,
     *     label: string,
     *     value: array<string, mixed>,
     *     multiple: bool,
     * }>
     */
    private array $availableElements = [];

    private bool $withGeneratedFields = false;

    public static function type(): string
    {
        return 'craft:field-layout-input';
    }

    /**
     * @param  list<array{
     *     key: string,
     *     label: string,
     *     value: array<string, mixed>,
     *     multiple: bool,
     * }>  $availableElements
     */
    public function availableElements(array $availableElements): static
    {
        $this->availableElements = $availableElements;

        return $this;
    }

    public function withGeneratedFields(bool $withGeneratedFields = true): static
    {
        $this->withGeneratedFields = $withGeneratedFields;

        return $this;
    }

    #[\Override]
    protected function props(): array
    {
        return [
            'availableElements' => $this->availableElements,
            'withGeneratedFields' => $this->withGeneratedFields,
        ];
    }
}
