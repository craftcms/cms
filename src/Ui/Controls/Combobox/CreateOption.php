<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ui\Controls\Combobox;

use JsonSerializable;

/**
 * An option that opens a resource's create screen and selects the saved record.
 *
 * @since 6.0.0
 */
readonly class CreateOption implements JsonSerializable
{
    public function __construct(
        public string $label,
        public string $url,
        public string $resultKey,
        public string $value = '__add__',
        public string $labelField = 'name',
        public string $valueField = 'id',
    ) {}

    /** @return array{label: string, value: string, data: array{create: array{url: string, resultKey: string, labelField: string, valueField: string}}} */
    public function jsonSerialize(): array
    {
        return [
            'label' => $this->label,
            'value' => $this->value,
            'data' => [
                'create' => [
                    'url' => $this->url,
                    'resultKey' => $this->resultKey,
                    'labelField' => $this->labelField,
                    'valueField' => $this->valueField,
                ],
            ],
        ];
    }
}
