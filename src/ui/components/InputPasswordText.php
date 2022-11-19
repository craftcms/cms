<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\ui\components;

use craft\ui\attributes\AsTwigComponent;

#[AsTwigComponent('input:password')]
class InputPasswordText extends InputText
{
    public string $type = 'password';
}
