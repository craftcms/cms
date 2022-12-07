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
use craft\ui\attributes\AsTwigComponent;
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
     * @var array
     */
    public array $containerAttributes = [];

    /**
     * Is this a date and time field?
     *
     * @var bool
     */
    public bool $isDateTime = false;

    protected function prepare(): void
    {
        $this->isMobile = $this->isMobile ?? Craft::$app->getRequest()->isMobileBrowser();
        $this->type = $this->isMobile ? 'date' : 'text';
        $this->id = ($this->id ?? 'date' . mt_rand()) . '-date';
        $this->name = $this->name ? $this->name . '[date]' : null;
    }
}
