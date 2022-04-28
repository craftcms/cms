<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\ElementInterface;

use yii\base\Event;

/**
 * RegisterCpElementHtmlEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class RegisterCpElementHtmlEvent extends Event
{
    /**
     * @var ElementInterface The element for the HTML we're rendering
     */
    public ElementInterface $element;

    /**
     * @var string The context for the element being rendered
     */
    public string $context;

    /**
     * @var string The default inner-html
     */
    public string $innerHtml;
}
