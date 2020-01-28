<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\test\fixtures;

use Craft;
use craft\fields\PlainText;
use craft\records\Field;
use craft\services\Fields;
use craft\test\Fixture;
use yii\base\InvalidArgumentException;

/**
 * Base fixture for setting up fields
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2.0
 */
class FieldFixture extends Fixture
{
    /**
     * @inheritdoc
     */
    public $modelClass = Field::class;

    /**
     * @inheritdoc
     */
    public function load()
    {
        foreach ($this->getData() as $alias => $row) {
            if (isset($row['fieldType'])) {
                $class = $row['fieldType'];
                $field = new $class;
                unset($row['fieldType']);
            } else {
                $field = new PlainText();
            }

            foreach ($row as $key => $value) {
                $field->$key = $value;
            }

            if (!Craft::$app->getFields()->saveField($field)) {
                throw new InvalidArgumentException('Unable to save field');
            }
        }

        Craft::$app->set('fields', new Fields());
        // TODO: How do we updated content behavior here?
    }

    /**
     * @inheritdoc
     */
    public function unload()
    {
        $fieldsThatDidntSave = [];

        foreach ($this->getData() as $toBeDeletedRow) {
            $field = Craft::$app->getFields()->getFieldByHandle($toBeDeletedRow['handle']);

            if ($field && !Craft::$app->getFields()->deleteField($field)) {
                $fieldsThatDidntSave[$field->handle] = $field->name;
            }
        }
        if ($fieldsThatDidntSave !== []) {
            throw new InvalidArgumentException(implode(', ', $fieldsThatDidntSave));
        }
    }
}
