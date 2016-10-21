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
use craft\app\validators\UriFormatValidator;
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
     * @var integer ID
     */
    public $id;

    /**
     * @var integer Group ID
     */
    public $groupId;

    /**
     * @var integer Site ID
     */
    public $siteId;

    /**
     * @var boolean Has URLs?
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
     * @throws InvalidConfigException if the section is invalid
     */
    public function getGroup()
    {
        if (!isset($this->_group) && $this->groupId) {
            $this->_group = Craft::$app->getCategories()->getGroupById($this->groupId);
        }

        if (!$this->_group) {
            throw new InvalidConfigException('Invalid group');
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
