<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\models;

use Craft;
use craft\app\base\Model;
use craft\app\validators\SiteIdValidator;

Craft::$app->requireEdition(Craft::Client);

/**
 * Email message model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class RebrandEmail extends Model
{
    // Properties
    // =========================================================================

    /**
     * @var string Key
     */
    public $key;

    /**
     * @var integer Site ID
     */
    public $siteId;

    /**
     * @var string Subject
     */
    public $subject;

    /**
     * @var string Body
     */
    public $body;

    /**
     * @var string Html body
     */
    public $htmlBody;

    /**
     * @var string Heading
     */
    public $heading;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['siteId'], SiteIdValidator::class],
            [
                ['key', 'siteId', 'subject', 'body', 'htmlBody'],
                'safe',
                'on' => 'search'
            ],
        ];
    }
}
