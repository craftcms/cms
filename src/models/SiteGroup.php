<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use Craft;
use craft\base\Model;
use craft\helpers\ArrayHelper;
use craft\records\SiteGroup as SiteGroupRecord;
use craft\validators\UniqueValidator;

/**
 * SiteGroup model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class SiteGroup extends Model
{
    // Properties
    // =========================================================================

    /**
     * @var int|null ID
     */
    public $id;

    /**
     * @var string|null Name
     */
    public $name;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'name' => Craft::t('app', 'Name'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id'], 'number', 'integerOnly' => true],
            [['name'], 'string', 'max' => 255],
            [['name'], UniqueValidator::class, 'targetClass' => SiteGroupRecord::class],
            [['name'], 'required'],
        ];
    }

    /**
     * Use the group name as the string representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->name;
    }

    /**
     * Returns the group's sites.
     *
     * @return Site[]
     */
    public function getSites(): array
    {
        return Craft::$app->getSites()->getSitesByGroupId($this->id);
    }

    /**
     * Returns the group’s site IDs.
     *
     * @return int[]
     */
    public function getSiteIds(): array
    {
        return ArrayHelper::getColumn($this->getSites(), 'id');
    }
}
