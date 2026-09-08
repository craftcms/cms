<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

declare(strict_types=1);

namespace craft\base;

use function CraftCms\Cms\craftAsset;

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

    private string $widgetBodyHtml = '';

    public function component(): ?string
    {
        $html = $this->getBodyHtml();
        $this->widgetBodyHtml = $html ?? '';

        return $html === null ? null : 'craft:html-widget';
    }

    /** @return array<string, mixed> */
    public function props(): array
    {
        return ['html' => $this->widgetBodyHtml];
    }

    public function getBodyHtml(): ?string
    {
        $url = craftAsset('legacy/cp/dist/images/prg.jpg');

        return <<<EOD
<div style="margin: 0 -24px -24px;">
    <img style="display: block; width: 100%; border-radius: 0 0 4px 4px" src="$url">
</div>
EOD;
    }
}
