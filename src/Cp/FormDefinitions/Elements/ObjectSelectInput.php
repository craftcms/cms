<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\FormDefinitions\Elements;

class ObjectSelectInput extends InputElement
{
    /**
     * @var list<array{
     *     key: string,
     *     label: string,
     *     value: mixed,
     * }>
     */
    private array $options = [];

    private string $identityKey;

    public static function type(): string
    {
        return 'craft:object-select-input';
    }

    /**
     * @param  list<array{
     *     key: string,
     *     label: string,
     *     value: mixed,
     * }>  $options
     */
    public function options(array $options): static
    {
        $this->options = $options;

        return $this;
    }

    public function identityKey(string $identityKey): static
    {
        $this->identityKey = $identityKey;

        return $this;
    }

    #[\Override]
    protected function props(): array
    {
        return [
            'options' => $this->options,
            'identityKey' => $this->identityKey,
        ];
    }
}
