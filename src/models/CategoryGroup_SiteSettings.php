<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use Craft;
use craft\validators\SiteIdValidator;
use craft\validators\UriFormatValidator;
use yii\base\InvalidConfigException;

/**
 * CategoryGroup_SiteSettings model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class CategoryGroup_SiteSettings extends BaseSiteSettings
{
    // Properties
    // =========================================================================

    /**
     * @var int|null Group ID
     */
    public $groupId;

    /**
     * @var CategoryGroup|null
     */
    private $_group;

    // Public Methods
    // =========================================================================

    /**
     * Returns the group.
     *
     * @return CategoryGroup
     * @throws InvalidConfigException if [[groupId]] is missing or invalid
     */
    public function getGroup(): CategoryGroup
    {
        if ($this->_group !== null) {
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
    public function setGroup(CategoryGroup $group)
    {
        $this->_group = $group;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();
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
