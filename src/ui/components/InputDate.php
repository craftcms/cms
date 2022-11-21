<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\ui\components;

use Craft;
use craft\base\BaseUiComponent;
use craft\base\Component;
use craft\helpers\DateTimeHelper;
use craft\ui\attributes\AsTwigComponent;
use craft\ui\ComponentAttributes;
use DateTime;

#[AsTwigComponent('input:date')]
class InputDate extends BaseUiComponent
{
    /**
     * ID of the component
     *
     * @var string|null
     */
    public ?string $id = null;

    /**
     * Type of the date input. Defaults to `date` on mobile and `text` elsewhere.
     *
     * @var string|null
     */
    public ?string $type = null;

    /**
     * Name of the input.
     *
     * @var string|null
     */
    public ?string $name = null;

    /**
     * Wrap input in a div.datetimewrapper element
     *
     * @var bool
     */
    public bool $hasOuterContainer = false;

    /**
     * Output the timzezone parameters
     *
     * @var bool
     */
    public bool $outputTzParam = true;

    /**
     * Is the current request a mobile request?
     *
     * @var bool|null
     */
    public ?bool $isMobile = null;

    /**
     * Value of the field, normalized to a DateTime object
     *
     * @var DateTime|null
     */
    public ?DateTime $value = null;

    /**
     * Attributes specifically for the container.
     *
     * @var ComponentAttributes|null
     */
    public ?ComponentAttributes $containerAttributes = null;

    /**
     * Is this a date and time field?
     *
     * @var bool
     */
    public bool $isDateTime = false;

    public function mount(bool $isMobile = null, mixed $value = null, string $id = null, string $name = null, array $containerAttributes = [])
    {
        $this->isMobile = $isMobile ?? Craft::$app->getRequest()->isMobileBrowser();
        $this->type = $this->isMobile ? 'date' : 'text';
        $this->id = ($id ?? 'date' . mt_rand()) . '-date';

        $this->name = $name ? $name . '[date]' : null;

        $this->containerAttributes = new ComponentAttributes($containerAttributes);

        if ($value) {
            $value = DateTimeHelper::toDateTime($value);
            if ($value === false) {
                $value = null;
            }

            $this->value = $value;
        }
    }
}
