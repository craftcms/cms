<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\ui\components;

use craft\base\BaseUiComponent;
use craft\ui\attributes\AsTwigComponent;

#[AsTwigComponent('errorList')]
class ErrorList extends BaseUiComponent
{
    /**
     * @var string|null ID HTML attribute
     */
    public ?string $id = null;

    /**
     * @var array|null Array of errors
     */
    public ?array $errors = null;
}
