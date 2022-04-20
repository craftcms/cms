<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use Craft;
use craft\base\Model;
use craft\web\UploadedFile;

/**
 * Class CraftSupport model.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class CraftSupport extends Model
{
    /**
     * @var string|null From email
     */
    public ?string $fromEmail = null;

    /**
     * @var string|null Message
     */
    public ?string $message = null;

    /**
     * @var bool Attach logs
     */
    public bool $attachLogs = false;

    /**
     * @var bool Attach db backup
     */
    public bool $attachDbBackup = false;

    /**
     * @var bool Attach templates
     */
    public bool $attachTemplates = false;

    /**
     * @var UploadedFile|null Attachment
     */
    public ?UploadedFile $attachment = null;

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'attachment' => Craft::t('app', 'Attachment'),
            'fromEmail' => Craft::t('app', 'Your Email'),
        ];
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['fromEmail', 'message'], 'required'];
        $rules[] = [['fromEmail'], 'email'];
        $rules[] = [['fromEmail'], 'string', 'min' => 5, 'max' => 255];
        $rules[] = [['attachment'], 'file', 'maxSize' => 3145728];
        return $rules;
    }
}
