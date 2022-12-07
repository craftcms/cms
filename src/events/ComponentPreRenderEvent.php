<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

class ComponentPreRenderEvent extends Event
{
    /**
     * Variables to be supplied to the template.
     *
     * @var array|null
     */
    public ?array $variables = [];

    /**
     * Template path to be rendered.
     *
     * @var string
     */
    public string $template;
}
