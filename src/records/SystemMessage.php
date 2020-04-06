<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\records;

use craft\db\ActiveRecord;
use craft\db\Table;
use craft\validators\LanguageValidator;

/**
 * Class SystemMessage record.
 *
 * @property int $id ID
 * @property string $language Language
 * @property string $key Key
 * @property string $subject Subject
 * @property string $body Body
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class SystemMessage extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['key'], 'unique', 'targetAttribute' => ['key', 'language']],
            [['key', 'language', 'subject', 'body'], 'required'],
            [['key'], 'string', 'max' => 150],
            [['language'], LanguageValidator::class],
            [['subject'], 'string', 'max' => 1000],
        ];
    }

    /**
     * @inheritdoc
     * @return string
     */
    public static function tableName(): string
    {
        return Table::SYSTEMMESSAGES;
    }
}
