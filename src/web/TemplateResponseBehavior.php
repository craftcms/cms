<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web;

use yii\base\Behavior;

/**
 * Control panel screen response behavior.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class TemplateResponseBehavior extends Behavior
{
    public const NAME = 'template';

    /**
     * @var string The template to render.
     */
    public string $template;

    /**
     * @var array Template variables.
     */
    public array $variables = [];

    /**
     * @var string|null The template mode to use (`site` or `cp`).
     */
    public ?string $templateMode = null;
}
