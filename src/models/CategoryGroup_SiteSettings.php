<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
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
 * @since  3.0
 */
class CategoryGroup_SiteSettings extends Model
{
    // Properties
    // =========================================================================

    /**
     * @var int ID
     */
    public $id;

    /**
     * @var int Group ID
     */
    public $groupId;

    /**
     * @var int Site ID
     */
    public $siteId;

    /**
     * @var bool Has URLs?
     */
    public $hasUrls;

    /**
     * @var string URI format
     */
    public $uriFormat;

    /**
     * @var string Entry template
     */
    public $template;

    /**
     * @var CategoryGroup
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
            throw new InvalidConfigException('Invalid group ID: '.$this->groupId);
        }

        return $this->_group;
    }

    /**
     * Sets the group.
     *
     * @param CategoryGroup $group
     *
     * @return void
     */
    public function setGroup(CategoryGroup $group)
    {
        $this->_group = $group;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'uriFormat' => Craft::t('app', 'URI Format'),
            'template' => Craft::t('app', 'Template'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = [
            [['id', 'groupId', 'siteId'], 'number', 'integerOnly' => true],
            [['siteId'], SiteIdValidator::class],
            [['template'], 'string', 'max' => 500],
            [['uriFormat'], UriFormatValidator::class]
        ];

        if ($this->hasUrls) {
            $rules[] = [['uriFormat'], 'required'];
        }

        return $rules;
    }
}
