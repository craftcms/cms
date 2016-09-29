<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\models;

use craft\app\base\Model;
use craft\app\validators\UrlValidator;

/**
 * Validates the required Site attributes for the installer.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class SiteSettings extends Model
{
    // Properties
    // =========================================================================

    /**
     * @var string Site name
     */
    public $siteName;

    /**
     * @var string Site URL
     */
    public $siteUrl;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['siteName', 'siteUrl'], 'required'],
            [
                ['siteUrl'],
                UrlValidator::class,
                'defaultScheme' => 'http'
            ],
            [['siteName', 'siteUrl'], 'string', 'max' => 255],
            [['siteName', 'siteUrl'], 'safe', 'on' => 'search'],
        ];
    }
}
