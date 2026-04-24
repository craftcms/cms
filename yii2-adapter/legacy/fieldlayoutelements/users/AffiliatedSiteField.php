<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fieldlayoutelements\users;

/**
 * AffiliatedSiteField represents the Affiliated Site field that can be included in the user field layout.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 5.6.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\FieldLayout\LayoutElements\Users\AffiliatedSiteField} instead.
 */
class AffiliatedSiteField extends \CraftCms\Cms\FieldLayout\LayoutElements\Users\AffiliatedSiteField
{
    use \craft\base\LegacyEventConstants;
}
