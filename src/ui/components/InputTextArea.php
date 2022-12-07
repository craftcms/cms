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

#[AsTwigComponent('input:textarea')]
class InputTextArea extends BaseUiComponent
{
    /**
     * @var bool|null
     */
    public ?bool $disabled = null;

    /**
     * @var int|null
     */
    public ?int $cols = 50;

    /**
     * @var int|null
     */
    public ?int $rows = 2;

    /**
     * If characters left should be shown
     *
     * @var bool
     */
    public bool $showCharsLeft = false;

    /**
     * Value of the textarea
     *
     * @var string|null
     */
    public ?string $value = null;

    /**
     * Autofocus input. Only applies for desktop browsers.
     *
     * @var bool
     */
    public bool $autofocus = false;

    public function prepare(): void
    {
        $this->autofocus = $this->autofocus && !Craft::$app->getRequest()->isMobileBrowser(true);
    }
}
