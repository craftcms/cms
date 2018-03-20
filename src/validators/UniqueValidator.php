<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\validators;

use craft\helpers\StringHelper;
use yii\base\Model;
use yii\db\ActiveRecord;
use yii\validators\UniqueValidator as YiiUniqueValidator;

/**
 * Class UniqueValidator.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class UniqueValidator extends YiiUniqueValidator
{
    // Properties
    // =========================================================================

    /**
     * @var string|string[] If [[targetClass]] is set, this defines the model
     * attributes that represent the record's primary key(s). Can be set to a
     * string or array of strings of model attributes in the same respective
     * order as the primary keys defined by the record's primaryKey() method, or
     * can be set to an array of attribute/PK pairs, which explicitly maps model
     * attributes to record primary keys. Defaults to whatever the record's
     * primaryKey() method returns.
     */
    public $pk;

    /**
     * @var Model|null The model that is being validated
     */
    protected $originalModel;

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function validateAttribute($model, $attribute)
    {
        if ($this->targetClass) {
            // Run validation on the record instead of here
            /** @var ActiveRecord $record */
            $record = new $this->targetClass();

            // Set the primary key values on the record, if they're set
            $pks = $record::primaryKey();
            if ($this->pk !== null) {
                $pkMap = is_string($this->pk) ? StringHelper::split($this->pk) : $this->pk;
            } else {
                $pkMap = $pks;
            }
            $isNewRecord = true;

            foreach ($pkMap as $k => $v) {
                if (is_int($k)) {
                    $sourcePk = $v;
                    $targetPk = $pks[$k];
                } else {
                    $sourcePk = $k;
                    $targetPk = $v;
                }
                if ($model->$sourcePk) {
                    $record->$targetPk = $model->$sourcePk;
                    $isNewRecord = false;
                }
            }

            $record->setIsNewRecord($isNewRecord);

            // Set the new attribute value(s) on the record
            $targetAttribute = $this->targetAttribute ?? $attribute;

            if (is_array($targetAttribute)) {
                foreach ($targetAttribute as $k => $v) {
                    $record->$v = is_int($k) ? $model->$v : $model->$k;
                }
            } else {
                $record->$targetAttribute = $model->$attribute;
            }

            // Validate the record, but make sure any errors are added to the model
            $this->originalModel = $model;
            parent::validateAttribute($record, $attribute);
            $this->originalModel = null;
        } else {
            parent::validateAttribute($model, $attribute);
        }
    }

    /**
     * @inheritdoc
     */
    public function addError($model, $attribute, $message, $params = [])
    {
        // Use the original model if there is one
        if ($this->originalModel !== null) {
            $model = $this->originalModel;
        }

        parent::addError($model, $attribute, $message, $params);
    }
}
