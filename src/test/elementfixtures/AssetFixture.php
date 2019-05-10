<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */
namespace craft\test\elementfixtures;

use Craft;
use craft\base\Element;
use craft\elements\Asset;
use ErrorException;

/**
 * Class AssetFixture.
 *
 * Credit to: https://github.com/robuust/craft-fixtures
 *
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Robuust digital | Bob Olde Hampsink <bob@robuust.digital>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since  3.0
 */
abstract class AssetFixture extends ElementFixture
{
    /**
     * {@inheritdoc}
     */
    public $modelClass = Asset::class;

    /**
     * {@inheritdoc}
     */
    public function load(): void
    {
        $this->data = [];
        foreach ($this->getData() as $alias => $data) {
            $element = $this->getElement();

            if ($element) {
                foreach ($data as $handle => $value) {
                    $element->$handle = $value;
                }

                $result = Craft::$app->getElements()->saveElement($element);

                if (!$result) {
                    throw new ErrorException(implode(' ', $element->getErrorSummary(true)));
                }
                $this->data[$alias] = array_merge($data, ['id' => $element->id]);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function isPrimaryKey(string $key): bool
    {
        return in_array($key, ['volumeId', 'folderId', 'filename', 'title']);
    }

    /**
     * Get asset model.
     *
     * @param array $data
     *
     * @return Element
     */
    public function getElement(array $data = null): Element
    {
        /* @var Asset $element */
        $element = parent::getElement($data);

        if ($data === null) {
            $element->avoidFilenameConflicts = true;
            $element->setScenario(Asset::SCENARIO_REPLACE);
        }

        return $element;
    }
}
