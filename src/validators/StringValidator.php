<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\validators;

use Craft;
use craft\helpers\StringHelper;

/**
 * Class StringValidator.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class StringValidator extends \yii\validators\StringValidator
{
    /**
     * @var bool whether the string should be checked for 4+ byte characters (like emoji)
     */
    public bool $disallowMb4 = false;

    /**
     * @var string|null user-defined error message used when the value contains 4+ byte characters
     * (like emoji) and the database doesn’t support it.
     */
    public ?string $containsMb4 = null;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        if (!isset($this->containsMb4)) {
            $this->containsMb4 = Craft::t('app', '{attribute} cannot contain emoji.');
        }
    }

    /**
     * @inheritdoc
     */
    public function validateAttribute($model, $attribute): void
    {
        $value = $model->$attribute;

        parent::validateAttribute($model, $attribute);

        if (is_string($value) && $this->disallowMb4 && !Craft::$app->getDb()->getSupportsMb4() && StringHelper::containsMb4($value)) {
            $this->addError($model, $attribute, $this->containsMb4);
        }
    }

    /**
     * @inheritdoc
     */
    public function validateValue($value): ?array
    {
        if (!empty($result = parent::validateValue($value))) {
            return $result;
        }

        if ($this->disallowMb4 && !Craft::$app->getDb()->getSupportsMb4() && StringHelper::containsMb4($value)) {
            return [$this->containsMb4, []];
        }

        return null;
    }
}
