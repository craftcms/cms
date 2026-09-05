<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

declare(strict_types=1);

namespace craft\base;

/**
 * WidgetTrait implements the common methods and properties for dashboard widget classes.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated in 6.0.0.
 */
trait WidgetTrait
{
    /**
     * @var int|null The user’s chosen colspan for the widget
     */
    public ?int $colspan = null;

    public function component(): ?string
    {
        return 'craft:html-widget';
    }

    /** @return array<string, mixed>|null */
    public function props(): ?array
    {
        $html = $this->getBodyHtml();

        return $html === null ? null : ['html' => $html];
    }
}
