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
     * @var string|null Key
     */
    public $key;

    /**
     * @var string|null Language
     */
    public $language;

    /**
     * @var string|null Subject
     */
    public $subject;

    /**
     * @var string|null Body
     */
    public $body;

    /**
     * @var string|null Html body
     */
    public $htmlBody;

    /**
     * @var string|null Heading
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
