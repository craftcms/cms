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
use craft\validators\UriFormatValidator;
use yii\base\InvalidConfigException;

/**
 * CategoryGroup_SiteSettings model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class CategoryGroup_SiteSettings extends Model
{
    /**
     * @var int|null ID
     */
    public ?int $id = null;

    /**
     * @var int|null Group ID
     */
    public ?int $groupId = null;

    /**
     * @var int|null Site ID
     */
    public ?int $siteId = null;

    /**
     * @var bool|null Has URLs?
     */
    public ?bool $hasUrls = null;

    /**
     * @var string|null URI format
     */
    public ?string $uriFormat = null;

    /**
     * @var string|null Entry template
     */
    public ?string $template = null;

    /**
     * @var CategoryGroup|null
     */
    private ?CategoryGroup $_group = null;

    /**
     * Returns the group.
     *
     * @return CategoryGroup
     * @throws InvalidConfigException if [[groupId]] is missing or invalid
     */
    public function getGroup(): CategoryGroup
    {
        if (isset($this->_group)) {
            return $this->_group;
        }

        if (!$this->groupId) {
            throw new InvalidConfigException('Category is missing its group ID');
        }

        if (($this->_group = Craft::$app->getCategories()->getGroupById($this->groupId)) === null) {
            throw new InvalidConfigException('Invalid group ID: ' . $this->groupId);
        }

        return $this->_group;
    }

    /**
     * Sets the group.
     *
     * @param CategoryGroup $group
     */
    public function setGroup(CategoryGroup $group): void
    {
        $this->_group = $group;
    }

    /**
     * Returns the site.
     *
     * @return Site
     * @throws InvalidConfigException if [[siteId]] is missing or invalid
     */
    public function getSite(): Site
    {
        if (!$this->siteId) {
            throw new InvalidConfigException('Category group site settings model is missing its site ID');
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
            'template' => Craft::t('app', 'Template'),
            'uriFormat' => Craft::t('app', 'URI Format'),
        ];
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['id', 'groupId', 'siteId'], 'number', 'integerOnly' => true];
        $rules[] = [['siteId'], SiteIdValidator::class];
        $rules[] = [['template'], 'string', 'max' => 500];
        $rules[] = [['uriFormat'], UriFormatValidator::class];

        if ($this->hasUrls) {
            $rules[] = [['uriFormat'], 'required'];
        }

        return $rules;
    }
}
