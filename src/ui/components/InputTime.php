<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\ui\components;

use Craft;
use craft\base\BaseUiComponent;
use craft\ui\attributes\AsTwigComponent;
use craft\ui\ComponentAttributes;
use DateTime;

#[AsTwigComponent('input:time')]
class InputTime extends BaseUiComponent
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
     * Is a mobile request
     *
     * @var bool|null
     */
    public ?bool $isMobile = null;

    /**
     * Component already has an outer container (part of date and time field)
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

    public bool $isDateTime = false;

    /**
     * Value of the field, normalized to a DateTime object
     *
     * @var DateTime|null
     */
    public ?DateTime $value = null;

    /**
     * @var string|null The minimum allowed time
     */
    public ?string $minTime = null;

    /**
     * @var string|null The maximum allowed time
     */
    public ?string $maxTime = null;

    /**
     * Disable time ranges
     *
     * @var bool|null
     */
    public ?bool $disableTimeRanges = null;

    /**
     * @var int The number of minutes that the timepicker options should increment by
     */
    public int $minuteIncrement = 30;


    /**
     * @var bool Force time to be rounded.
     */
    public bool $forceRoundTime = false;

    /**
     * Attributes specifically for the container.
     *
     * @var array|ComponentAttributes
     */
    public ComponentAttributes|array $containerAttributes = [];

    public function prepare(): void
    {
        $this->isMobile = $this->isMobile ?? Craft::$app->getRequest()->isMobileBrowser();
        $this->type = $this->isMobile ? 'time' : 'text';
        $this->id = ($this->id ?? 'time' . mt_rand()) . '-time';

        $this->name = $this->name ? $this->name . '[time]' : null;

        if (!($this->containerAttributes instanceof ComponentAttributes)) {
            $this->containerAttributes = new ComponentAttributes($this->containerAttributes);
        }
    }
}
