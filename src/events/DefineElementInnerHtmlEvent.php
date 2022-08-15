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
 * DefineElementInnerHtmlEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class DefineElementInnerHtmlEvent extends Event
{
    /**
     * @var ElementInterface The element being rendered via `\craft\helpers\Cp::elementHtml()`.
     */
    public ElementInterface $element;

    /**
     * @var string The context the element is going to be shown in (`index`, `field`, etc.).
     */
    public string $context;

    /**
     * @var string The size of the element (`small` or `large`).
     */
    public string $size;

    /**
     * @var bool Whether the element status should be shown (if the element type has statuses).
     */
    public bool $showStatus;

    /**
     * @var bool Whether the element thumb should be shown (if the element has one).
     */
    public bool $showThumb;

    /**
     * @var bool Whether the element label should be shown.
     */
    public bool $showLabel;

    /**
     * @var bool Whether to show the draft name beside the label if the element is a draft of a published element.
     */
    public bool $showDraftName;

    /**
     * @var string The element’s pre-rendered inner HTML.
     */
    public string $innerHtml;
}
