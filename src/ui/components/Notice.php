<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\ui\components;

use craft\base\BaseUiComponent;
use craft\ui\attributes\AsTwigComponent;

#[AsTwigComponent('notice')]
class Notice extends BaseUiComponent
{
    /**
     * @var string|null HTML ID for the component
     */
    public ?string $id = null;

    /**
     * @var string|null Type of component
     */
    public ?string $type = 'notice';

    /**
     * @var string|null Content of the component. Will be parsed with the [[md]] filter with inlineOnly
     */
    public ?string $content = null;

    /**
     * @var string|null Label of the component.
     */
    public ?string $label = null;
}
