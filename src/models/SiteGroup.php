<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use Craft;
use craft\base\Model;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\records\SiteGroup as SiteGroupRecord;
use craft\validators\UniqueValidator;

/**
 * SiteGroup model class.
 *
 * @property string $name The site group’s name
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class SiteGroup extends Model
{
    /**
     * @var int|null ID
     */
    public ?int $id = null;

    /**
     * @var string|null UID
     */
    public ?string $uid = null;

    /**
     * @var string|null Name
     */
    private ?string $_name = null;

    /**
     * Returns the site group’s name.
     *
     * @param bool $parse Whether to parse the name for an environment variable
     * @return string
     * @since 3.7.0
     */
    public function getName(bool $parse = true): string
    {
        return ($parse ? App::parseEnv($this->_name) : $this->_name) ?? '';
    }

    /**
     * Sets the site’s name.
     *
     * @param string $name
     * @since 3.7.0
     */
    public function setName(string $name): void
    {
        $this->_name = $name;
    }

    /**
     * @inheritdoc
     */
    public function attributes(): array
    {
        $attributes = parent::attributes();
        $attributes[] = 'name';
        return $attributes;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'name' => Craft::t('app', 'Name'),
        ];
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['id'], 'number', 'integerOnly' => true];
        $rules[] = [['name'], 'string', 'max' => 255];
        $rules[] = [['name'], UniqueValidator::class, 'targetClass' => SiteGroupRecord::class];
        $rules[] = [['name'], 'required'];
        return $rules;
    }

    /**
     * Use the group name as the string representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->_name ? $this->getName() : static::class;
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

    /**
     * Returns the site group’s config.
     *
     * @return array
     * @since 3.5.0
     */
    public function getConfig(): array
    {
        return [
            'name' => $this->_name,
        ];
    }
}
