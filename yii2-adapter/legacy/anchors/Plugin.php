<?php

/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\anchors;

use CraftCms\Yii2Adapter\LooseEnds\LegacyUsage;

/**
 * Stands in for the Anchors plugin, which is part of Craft now.
 *
 * @property-read Parser $parser
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 2.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Support\Facades\Anchors} instead.
 */
class Plugin
{
    private static ?self $instance = null;

    public readonly Parser $parser;

    private function __construct()
    {
        $this->parser = new Parser();
    }

    public static function getInstance(): self
    {
        LegacyUsage::record(__METHOD__, '`craft\anchors\Plugin` has been deprecated. The Anchors plugin is part of Craft now, so use `CraftCms\Cms\Support\Facades\Anchors` instead.');

        return self::$instance ??= new self();
    }

    public function getParser(): Parser
    {
        return $this->parser;
    }
}
