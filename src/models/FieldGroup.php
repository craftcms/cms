<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use Craft;
use craft\base\FieldInterface;
use craft\base\Model;
use craft\records\FieldGroup as FieldGroupRecord;
use craft\validators\UniqueValidator;

/**
 * FieldGroup model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class FieldGroup extends Model
{
    /**
     * @var int|null ID
     */
    public ?int $id = null;

    /**
     * @var string|null Name
     */
    public ?string $name = null;

    /**
     * @var string|null UID
     */
    public ?string $uid = null;

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
        $rules[] = [['name'], UniqueValidator::class, 'targetClass' => FieldGroupRecord::class];
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
        return (string)$this->name ?: static::class;
    }

    /**
     * Returns the group's fields.
     *
     * @return FieldInterface[]
     */
    public function getFields(): array
    {
        return Craft::$app->getFields()->getFieldsByGroupId($this->id);
    }

    /**
     * Returns the field group’s config.
     *
     * @return array
     * @since 3.5.0
     */
    public function getConfig(): array
    {
        return [
            'name' => $this->name,
        ];
    }
}
