<?php

declare(strict_types=1);

namespace craft\models;

/**
 * FieldLayoutFormTab model class.
 *
 * @property-read string $name The tab’s name
 * @property-read string $id The tab’s HTML ID
 * @property-read string $content The tab’s HTML content
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\FieldLayout\FieldLayoutFormTab} instead.
 */
class FieldLayoutFormTab extends \CraftCms\Cms\FieldLayout\FieldLayoutFormTab
{
    use \craft\base\LegacyEventConstants;
}
