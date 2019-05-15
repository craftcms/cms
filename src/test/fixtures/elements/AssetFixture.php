<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */

namespace craft\test\fixtures\elements;

use Craft;
use craft\base\Element;
use craft\elements\Asset;
use craft\records\VolumeFolder;

/**
 * Class AssetFixture.
 *
 * Credit to: https://github.com/robuust/craft-fixtures
 *
 * @todo https://github.com/robuust/craft-fixtures/blob/master/src/base/AssetFixture.php#L60 ? Why override?
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Robuust digital | Bob Olde Hampsink <bob@robuust.digital>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since  3.1
 */
abstract class AssetFixture extends ElementFixture
{
    // Public Properties
    // =========================================================================

    /**
     * @inheritDoc
     */
    public $modelClass = Asset::class;

    /**
     * @var array
     */
    protected $volumeIds = [];

    /**
     * @var array
     */
    protected $folderIds = [];

    // Public Methods
    // =========================================================================

    /**
     * {@inheritdoc}
     */
    public function init(): void
    {
        parent::init();

        foreach (Craft::$app->getVolumes()->getAllVolumes() as $volume) {
            $this->volumeIds[$volume->handle] = $volume->id;
            $this->folderIds[$volume->handle] = VolumeFolder::findOne([
                'parentId' => null,
                'name' => $volume->name,
                'volumeId' => $volume->id,
            ])->id;
        }
    }

    /**
     * Get asset model.
     *
     * @param array $data
     * @return Element
     */
    public function getElement(array $data = null)
    {
        /* @var Asset $element */
        $element = parent::getElement($data);

        if ($data === null) {
            $element->avoidFilenameConflicts = true;
            $element->setScenario(Asset::SCENARIO_REPLACE);
        }

        return $element;
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    protected function isPrimaryKey(string $key): bool
    {
        return parent::isPrimaryKey($key) || in_array($key, ['volumeId', 'folderId', 'filename', 'title']);
    }
}
