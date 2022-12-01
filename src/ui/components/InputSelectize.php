<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\ui\components;

use craft\helpers\ArrayHelper;
use craft\ui\attributes\AsTwigComponent;

#[AsTwigComponent('input:selectize')]
class InputSelectize extends InputSelect
{
    /**
     * Include environment variables in the options
     *
     * @var bool
     */
    public bool $includeEnvVars = false;

    /**
     * Env vars that should be allowed in the selection.
     *
     * @var array|null
     */
    public ?array $allowedEnvValues = null;

    public array $selectizeOptions = [
        'dropdownParent' => 'body',
    ];

    public function mount(
        array $containerAttributes = [],
        bool $autofocus = false,
        string $id = null,
        array $selectizeOptions = [],
        bool $includeEnvVars = false,
        array $allowedEnvValues = null,
    ) {
        parent::mount($containerAttributes, $autofocus);

        $this->id = $id ?? 'selectize' . mt_rand();
        $this->selectizeOptions = ArrayHelper::merge($this->selectizeOptions, $selectizeOptions);

        $this->allowedEnvValues = $allowedEnvValues ?? $this->optionValues();

        if ($includeEnvVars) {
            $this->options = $this->addHints($this->getOptions());
        }
    }

    private function optionValues(): array
    {
        $withoutGroups = array_filter($this->getOptions(), function($option) {
            return !isset($option['optgroup']);
        });

        return ArrayHelper::getColumn($withoutGroups, 'value');
    }

    private function addHints(array $options): array
    {
        return array_map(function($option) {
            if (isset($option['data']['data']['hint'])) {
                return $option;
            }

            return ArrayHelper::merge($option, [
                'data' => [
                    'data' => [
                        'hint' => $option['value'],
                    ],
                ],
            ]);
        }, $options);
    }
}
