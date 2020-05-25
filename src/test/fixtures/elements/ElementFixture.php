<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */

namespace craft\test\fixtures\elements;

use Craft;
use craft\base\ElementInterface;
use craft\elements\db\ElementQuery;
use craft\errors\InvalidElementException;
use Throwable;
use yii\base\InvalidConfigException;
use yii\db\Exception;
use yii\test\ActiveFixture;

/**
 * Class ElementFixture is a base class for setting up fixtures for Craft 3's element types.
 *
 * Credit to: https://github.com/robuust/craft-fixtures
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Robuust digital | Bob Olde Hampsink <bob@robuust.digital>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since  3.2
 */
abstract class ElementFixture extends ActiveFixture
{
    /**
     * @var array
     */
    protected $siteIds = [];

    /**
     * @var bool Whether the fixture data should be unloaded
     * @since 3.3.5
     */
    public $unload = true;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if (!($this->getElement() instanceof ElementInterface)) {
            throw new InvalidConfigException('"modelClass" must be an Element');
        }

        foreach (Craft::$app->getSites()->getAllSites() as $site) {
            $this->siteIds[$site->handle] = $site->id;
        }
    }

    /**
     * @inheritdoc
     */
    public function getModel($name)
    {
        if (!isset($this->data[$name])) {
            return null;
        }

        if (array_key_exists($name, $this->_models)) {
            return $this->_models[$name];
        }

        return $this->_models[$name] = $this->getElement($this->data[$name]);
    }

    /**
     * @inheritdoc
     */
    public function load()
    {
        $this->data = [];

        foreach ($this->getData() as $alias => $data) {
            $element = $this->getElement($data) ?: new $this->modelClass;

            // If they want to add a date deleted. Store it but dont set that as an element property
            $dateDeleted = null;

            if (isset($data['dateDeleted'])) {
                $dateDeleted = $data['dateDeleted'];
                unset($data['dateDeleted']);
            }

            // Set the field layout
            if (isset($data['fieldLayoutType'])) {
                $fieldLayoutType = $data['fieldLayoutType'];
                unset($data['fieldLayoutType']);

                $fieldLayout = Craft::$app->getFields()->getLayoutByType($fieldLayoutType);
                if ($fieldLayout) {
                    $element->fieldLayoutId = $fieldLayout->id;
                } else {
                    codecept_debug("Field layout with type: $fieldLayoutType could not be found");
                }
            }

            foreach ($data as $handle => $value) {
                $element->$handle = $value;
            }

            if (!Craft::$app->getElements()->saveElement($element)) {
                throw new InvalidElementException($element, implode(' ', $element->getErrorSummary(true)));
            }

            // Add it here
            if ($dateDeleted) {
                $elementRecord = \craft\records\Element::find()
                    ->where(['id' => $element->id])
                    ->one();

                $elementRecord->dateDeleted = $dateDeleted;

                if (!$elementRecord->save()) {
                    throw new Exception('Unable to set element as deleted');
                }
            } else {
                Craft::$app->getSearch()->indexElementAttributes($element);
            }

            $this->data[$alias] = array_merge($data, ['id' => $element->id]);
        }
    }

    /**
     * @inheritdoc
     *
     * @throws InvalidElementException
     * @throws Throwable
     */
    public function unload()
    {
        if ($this->unload) {
            foreach ($this->getData() as $data) {
                $element = $this->getElement($data);

                if ($element && !Craft::$app->getElements()->deleteElement($element, true)) {
                    throw new InvalidElementException($element, 'Unable to delete element.');
                }
            }

            $this->data = [];
        }
    }

    /**
     * Get element model.
     *
     * @param array|null $data The data to get the element by
     * @return ElementInterface|null
     */
    public function getElement(array $data = null)
    {
        if ($data === null) {
            return new $this->modelClass();
        }

        return $this->generateElementQuery($data)->one();
    }

    /**
     * @param array $data
     * @return ElementQuery
     */
    public function generateElementQuery(array $data): ElementQuery
    {
        $modelClass = $this->modelClass;
        $query = $modelClass::find()->anyStatus()->trashed(null);

        foreach ($data as $key => $value) {
            // Is this the "field:handle" syntax?
            if (strncmp($key, 'field:', 6) === 0) {
                $key = substr($key, 6);
            }

            if ($this->isPrimaryKey($key)) {
                if (is_array($value)) {
                    $query = $query->relatedTo([
                        'targetElement' => $value,
                        'field' => $key,
                    ]);
                } else {
                    $query = $query->$key(addcslashes($value, ','));
                }
            }
        }

        return $query;
    }

    /**
     * See if an element's handle is a primary key.
     *
     * @param string $key
     * @return bool
     */
    protected function isPrimaryKey(string $key): bool
    {
        return $key === 'siteId';
    }
}
