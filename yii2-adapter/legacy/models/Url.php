<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use craft\base\Model;
use function CraftCms\Cms\t;

/**
 * URL model.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Url extends Model
{
    /**
     * @var string|null URL
     */
    public ?string $url = null;

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'url' => t('URL'),
        ];
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['url'], 'required'];
        $rules[] = [['url'], 'url'];
        return $rules;
    }
}
