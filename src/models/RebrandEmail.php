<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\models;

use Craft;
use craft\base\Model;
use craft\validators\LanguageValidator;

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
     * @var string Language
     */
    public $language;

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
            [['language'], LanguageValidator::class],
        ];
    }
}
