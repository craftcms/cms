<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */
namespace craft\test\elementfixtures;

use Craft;
use craft\errors\InvalidElementException;
use craft\events\DeleteElementEvent;
use craft\services\Elements;
use yii\base\ErrorException;
use yii\base\Event;
use yii\base\InvalidConfigException;
use yii\test\ActiveFixture;
use craft\base\Element;

/**
 * Class ElementFixture is a base class for setting up fixtures for Craft 3's element types.
 * Based on https://github.com/robuust/craft-fixtures/blob/master/src/base/ElementFixture.php
 *
 * Credit to: https://github.com/robuust/craft-fixtures
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author  Robuust digital | Bob Olde Hampsink <bob@robuust.digital>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since  3.0
 */
abstract class ElementFixture extends ActiveFixture
{
    /**
     * {@inheritdoc}
     */
    public function init(): void
    {
        parent::init();
        if (!($this->getElement() instanceof Element)) {
            throw new InvalidConfigException('"modelClass" must be an Element');
        }
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function load(): void
    {
        $this->data = [];
        foreach ($this->getData() as $alias => $data) {
            $element = $this->getElement();
            foreach ($data as $handle => $value) {
                $element->$handle = $value;
            }
            if (!Craft::$app->getElements()->saveElement($element)) {
                throw new ErrorException(implode(' ', $element->getErrorSummary(true)));
            }
            $this->data[$alias] = array_merge($data, ['id' => $element->id]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function unload(): void
    {
        // Create an event handler that ensures elements get hard deleted.
        $eventHandler = function(DeleteElementEvent $event) {
            $event->hardDelete = true;
        };

        // Ensure it gets hard deleted
        Event::on(Elements::class, Elements::EVENT_BEFORE_DELETE_ELEMENT, $eventHandler);

        foreach ($this->getData() as $data) {
            $element = $this->getElement($data);
            if ($element) {
                if (!Craft::$app->getElements()->deleteElement($element)) {
                    throw new InvalidElementException($element, 'Unable to delete element');
                }
            }
        }

        Event::off(Elements::class, Elements::EVENT_BEFORE_DELETE_ELEMENT, $eventHandler);

        $this->data = [];
    }

    /**
     * See if an element's handle is a primary key.
     *
     * @param string $key
     *
     * @return bool
     */
    abstract protected function isPrimaryKey(string $key): bool;

    /**
     * Get element model.
     *
     * @param array|null $data The data to get the element by
     *
     * @return Element
     */
    public function getElement(array $data = null)
    {
        $modelClass = $this->modelClass;
        if (is_null($data)) {
            return new $modelClass();
        }
        $query = $modelClass::find()->anyStatus();
        foreach ($data as $key => $value) {
            if ($this->isPrimaryKey($key)) {
                $query = $query->$key($value);
            }
        }

        return $query->one();
    }
}
