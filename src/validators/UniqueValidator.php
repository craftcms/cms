<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\validators;

use Craft;
use craft\helpers\StringHelper;
use yii\base\Model;
use yii\db\ActiveRecord;
use yii\validators\UniqueValidator as YiiUniqueValidator;

/**
 * Class UniqueValidator.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class UniqueValidator extends YiiUniqueValidator
{
    /**
     * @var string|string[] If [[targetClass]] is set, this defines the model
     * attributes that represent the record's primary key(s). Can be set to a
     * string or array of strings of model attributes in the same respective
     * order as the primary keys defined by the record's primaryKey() method, or
     * can be set to an array of attribute/PK pairs, which explicitly maps model
     * attributes to record primary keys. Defaults to whatever the record's
     * primaryKey() method returns.
     */
    public string|array $pk;

    /**
     * @var Model|null The model that is being validated
     */
    protected ?Model $originalModel = null;

    /**
     * @var bool Whether a case-insensitive check should be performed.
     */
    public bool $caseInsensitive = false;

    /**
     * @inheritdoc
     */
    public function validateAttribute($model, $attribute): void
    {
        if ($targetClass = $this->targetClass) {
            // Exclude this model's row using the filter
            /** @var ActiveRecord|string $targetClass */
            $pks = $targetClass::primaryKey();
            if (isset($this->pk)) {
                $pkMap = is_string($this->pk) ? StringHelper::split($this->pk) : $this->pk;
            } else {
                $pkMap = $pks;
            }

            $exists = false;
            $filter = ['and'];
            $tableName = Craft::$app->getDb()->getSchema()->getRawTableName($targetClass::tableName());

            foreach ($pkMap as $k => $v) {
                if (is_int($k)) {
                    $pkAttribute = $v;
                    $pkColumn = $pks[$k];
                } else {
                    $pkAttribute = $k;
                    $pkColumn = $v;
                }

                if ($model->$pkAttribute) {
                    $exists = true;
                    $filter[] = ['not', ["$tableName.$pkColumn" => $model->$pkAttribute]];
                }
            }

            if ($exists) {
                $this->filter = $filter;
            }
        }

        $originalAttributes = [];
        $originalTargetAttribute = $this->targetAttribute;

        if ($this->caseInsensitive && Craft::$app->getDb()->getIsPgsql()) {
            // Convert targetAttribute to an array of ['attribute' => 'lower([[column]])'] conditions
            // and set the model attributes to lowercase
            $targetAttributes = (array)($this->targetAttribute ?? $attribute);
            $newTargetAttributes = [];
            foreach ($targetAttributes as $k => $v) {
                $a = is_int($k) ? $v : $k;
                $originalAttributes[$a] = $model->$a;
                $model->$a = mb_strtolower($model->$a);
                $newTargetAttributes[$a] = "lower([[$v]])";
            }
            $this->targetAttribute = $newTargetAttributes;
        }

        parent::validateAttribute($model, $attribute);

        $this->targetAttribute = $originalTargetAttribute;
        foreach ($originalAttributes as $k => $v) {
            $model->$k = $v;
        }
    }

    /**
     * @inheritdoc
     */
    public function addError($model, $attribute, $message, $params = []): void
    {
        // Use the original model if there is one
        if (isset($this->originalModel)) {
            $model = $this->originalModel;
        }

        parent::addError($model, $attribute, $message, $params);
    }
}
