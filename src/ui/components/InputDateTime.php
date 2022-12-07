<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\ui\components;

use craft\base\BaseUiComponent;
use craft\helpers\ArrayHelper;
use craft\ui\attributes\AsTwigComponent;
use craft\ui\ComponentAttributes;
use DateTime;

#[AsTwigComponent('input:datetime')]
class InputDateTime extends BaseUiComponent
{
    /**
     * Name of the input.
     *
     * @var string|null
     */
    public ?string $name = null;

    /**
     * Value of the field, normalized to a DateTime object
     *
     * @var DateTime|null
     */
    public ?DateTime $value = null;

    /**
     * @var array Date component
     */
    public array $date = [];

    /**
     * @var array Time component
     */
    public array $time = [];

    public ?string $dateHtml = null;
    public ?string $timeHtml = null;

    public ?ComponentAttributes $componentAttributes = null;

    public function prepare(): void
    {
        $defaults = [
            'value' => $this->value,
            'name' => $this->name,
            'hasOuterContainer' => true,
            'isDateTime' => true,
        ];

        $this->dateHtml = InputDate::create(ArrayHelper::merge($defaults, $this->date))->render();
        $this->timeHtml = InputTime::create(ArrayHelper::merge($defaults, $this->date))->render();
    }
}
