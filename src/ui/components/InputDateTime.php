<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\ui\components;

use Craft;
use craft\base\BaseUiComponent;
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\ui\attributes\AsTwigComponent;
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
     * @var string|null Date component
     */
    public ?string $date = null;

    /**
     * @var string|null Time component
     */
    public ?string $time = null;

    public function mount(mixed $value = null, string $name = null, array $date = [], array $time = ['outputTzParam' => false])
    {
        if ($value) {
            $value = DateTimeHelper::toDateTime($value);
            if ($value === false) {
                $value = null;
            }

            $this->value = $value;
        }

        $defaults = [
            'value' => $value,
            'name' => $name,
            'hasOuterContainer' => true,
            'isDateTime' => true,
        ];

        $this->date = Craft::$app->getUi()->createAndRender('input:date', ArrayHelper::merge($defaults, $date));
        $this->time = Craft::$app->getUi()->createAndRender('input:time', ArrayHelper::merge($defaults, $time));
    }
}
