<?php

declare(strict_types=1);

namespace craft\models;

/**
 * FieldLayoutTab model class.
 *
 * @property \CraftCms\Cms\FieldLayout\FieldLayoutElement[]|null $elements The tab’s layout elements
 * @property FieldLayout|null $layout The tab’s layout
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\FieldLayout\FieldLayoutTab} instead.
 */
class FieldLayoutTab extends \CraftCms\Cms\FieldLayout\FieldLayoutTab
{
    use \craft\base\LegacyEventConstants;
}
