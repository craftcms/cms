<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use Craft;
use craft\base\Model;
use craft\validators\SiteIdValidator;
use yii\base\InvalidConfigException;

/**
 * Asset_SiteSettings model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
class Asset_SiteSettings extends Model
{
    /**
     * @var int|null ID
     */
    public ?int $id = null;

    /**
     * @var int|null Asset ID
     */
    public ?int $assetId = null;

    /**
     * @var int|null Site ID
     */
    public ?int $siteId = null;

    /**
     * @var string|null Alt
     */
    public ?string $alt = null;


    /**
     * Returns the site.
     *
     * @return Site
     * @throws InvalidConfigException if [[siteId]] is missing or invalid
     */
    public function getSite(): Site
    {
        if (!$this->siteId) {
            throw new InvalidConfigException('Asset site settings model is missing its site ID');
        }

        if (($site = Craft::$app->getSites()->getSiteById($this->siteId)) === null) {
            throw new InvalidConfigException('Invalid site ID: ' . $this->siteId);
        }

        return $site;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'alt' => Craft::t('app', 'Alt'),
        ];
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['id', 'assetId', 'siteId'], 'number', 'integerOnly' => true];
        $rules[] = [['siteId'], SiteIdValidator::class];
        $rules[] = [['alt'], 'text'];

        return $rules;
    }
}
