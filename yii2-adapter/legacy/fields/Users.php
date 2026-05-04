<?php

/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields;

/**
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Field\Users} instead.
 */
class Users extends \CraftCms\Cms\Field\Users
{
    use \craft\base\FieldEventConstants;
    use \craft\base\LegacyEventConstants;
}
