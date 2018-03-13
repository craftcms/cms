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
 * @since 3.0
 */
class StringValidator extends \yii\validators\StringValidator
{
    // Properties
    // =========================================================================

    /**
     * @var bool whether the string should be checked for 4+ byte characters (like emoji)
     */
    public $disallowMb4 = false;

    /**
     * @var string user-defined error message used when the value contains 4+ byte characters
     * (like emoji) and the database doesn’t support it.
     */
    public $containsMb4;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if ($this->containsMb4 === null) {
            $this->containsMb4 = Craft::t('app', '{attribute} cannot contain emoji.');
        }
    }

    /**
     * @inheritdoc
     */
    public function validateAttribute($model, $attribute)
    {
        parent::validateAttribute($model, $attribute);

        $value = $model->$attribute;
        if (!is_string($value)) {
            return;
        }

        if ($this->disallowMb4 && !Craft::$app->getDb()->getSupportsMb4() && StringHelper::containsMb4($value)) {
            $this->addError($model, $attribute, $this->containsMb4);
        }
    }

    /**
     * @inheritdoc
     */
    public function validateValue($value)
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
