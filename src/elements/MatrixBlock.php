<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\elements\db\ElementQueryInterface;
use craft\elements\db\MatrixBlockQuery;
use craft\fields\Matrix;
use craft\helpers\ArrayHelper;
use craft\helpers\ElementHelper;
use craft\models\MatrixBlockType;
use craft\records\MatrixBlock as MatrixBlockRecord;
use craft\validators\SiteIdValidator;
use craft\web\assets\matrix\MatrixAsset;
use yii\base\Exception;
use yii\base\InvalidConfigException;

/**
 * MatrixBlock represents a matrix block element.
 *
 * @property ElementInterface|null $owner the owner
 * @property MatrixBlockType $type The block type
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class MatrixBlock extends Element
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Matrix Block');
    }

    /**
     * @inheritdoc
     */
    public static function refHandle()
    {
        return 'matrixblock';
    }

    /**
     * @inheritdoc
     */
    public static function hasContent(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function isLocalized(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function hasStatuses(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     * @return MatrixBlockQuery The newly created [[MatrixBlockQuery]] instance.
     */
    public static function find(): ElementQueryInterface
    {
        return new MatrixBlockQuery(static::class);
    }

    /**
     * @inheritdoc
     */
    public static function eagerLoadingMap(array $sourceElements, string $handle)
    {
        // $handle *must* be set as "blockTypeHandle:fieldHandle" so we know _which_ myRelationalField to resolve to
        $handleParts = explode(':', $handle);

        if (count($handleParts) != 2) {
            return false;
        }

        list($blockTypeHandle, $fieldHandle) = $handleParts;

        // Get the block type
        $matrixFieldId = $sourceElements[0]->fieldId;
        $blockTypes = ArrayHelper::index(Craft::$app->getMatrix()->getBlockTypesByFieldId($matrixFieldId), 'handle');

        if (!isset($blockTypes[$blockTypeHandle])) {
            // Not a valid block type handle (assuming all $sourceElements are blocks from the same Matrix field)
            return false;
        }

        $blockType = $blockTypes[$blockTypeHandle];

        // Set the field context
        $contentService = Craft::$app->getContent();
        $originalFieldContext = $contentService->fieldContext;
        $contentService->fieldContext = 'matrixBlockType:' . $blockType->id;

        $map = parent::eagerLoadingMap($sourceElements, $fieldHandle);

        $contentService->fieldContext = $originalFieldContext;

        return $map;
    }

    // Properties
    // =========================================================================

    /**
     * @var int|null Field ID
     */
    public $fieldId;

    /**
     * @var int|null Owner ID
     */
    public $ownerId;

    /**
     * @var int|null Owner site ID
     */
    public $ownerSiteId;

    /**
     * @var int|null Type ID
     */
    public $typeId;

    /**
     * @var int|null Sort order
     */
    public $sortOrder;

    /**
     * @var bool Collapsed
     */
    public $collapsed = false;

    /**
     * @var ElementInterface|false|null The owner element, or false if [[ownerId]] is invalid
     */
    private $_owner;

    /**
     * @var ElementInterface[]|null
     */
    private $_eagerLoadedBlockTypeElements;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        $names = parent::extraFields();
        $names[] = 'owner';
        $names[] = 'type';
        return $names;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();
        $rules[] = [['fieldId', 'ownerId', 'typeId', 'sortOrder'], 'number', 'integerOnly' => true];
        $rules[] = [['ownerSiteId'], SiteIdValidator::class];

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getSupportedSites(): array
    {
        // If the Matrix field is translatable, than each individual block is tied to a single site, and thus aren't
        // translatable. Otherwise all blocks belong to all sites, and their content is translatable.

        if ($this->ownerSiteId !== null) {
            return [$this->ownerSiteId];
        }

        $owner = $this->getOwner();

        if ($owner) {
            // Just send back an array of site IDs -- don't pass along enabledByDefault configs
            $siteIds = [];

            foreach (ElementHelper::supportedSitesForElement($owner) as $siteInfo) {
                $siteIds[] = $siteInfo['siteId'];
            }

            return $siteIds;
        }

        return [Craft::$app->getSites()->getPrimarySite()->id];
    }

    /**
     * @inheritdoc
     */
    public function getFieldLayout()
    {
        return parent::getFieldLayout() ?? $this->getType()->getFieldLayout();
    }

    /**
     * Returns the block type.
     *
     * @return MatrixBlockType
     * @throws InvalidConfigException if [[typeId]] is missing or invalid
     */
    public function getType(): MatrixBlockType
    {
        if ($this->typeId === null) {
            throw new InvalidConfigException('Matrix block is missing its type ID');
        }

        $blockType = Craft::$app->getMatrix()->getBlockTypeById($this->typeId);

        if (!$blockType) {
            throw new InvalidConfigException('Invalid Matrix block ID: ' . $this->typeId);
        }

        return $blockType;
    }

    /**
     * Returns the owner.
     *
     * @return ElementInterface|null
     */
    public function getOwner()
    {
        if ($this->_owner !== null) {
            return $this->_owner !== false ? $this->_owner : null;
        }

        if ($this->ownerId === null) {
            return null;
        }

        if (($this->_owner = Craft::$app->getElements()->getElementById($this->ownerId, null, $this->siteId)) === null) {
            // Be forgiving of invalid ownerId's in this case, since the field
            // could be in the process of being saved to a new element/site
            $this->_owner = false;

            return null;
        }

        return $this->_owner;
    }

    /**
     * Sets the owner
     *
     * @param ElementInterface|null $owner
     */
    public function setOwner(ElementInterface $owner = null)
    {
        $this->_owner = $owner;
    }

    /**
     * @inheritdoc
     */
    public function getContentTable(): string
    {
        return $this->_getField()->contentTable;
    }

    /**
     * @inheritdoc
     */
    public function getFieldColumnPrefix(): string
    {
        return 'field_' . $this->getType()->handle . '_';
    }

    /**
     * Returns the field context this element's content uses.
     *
     * @return string
     */
    public function getFieldContext(): string
    {
        return 'matrixBlockType:' . $this->typeId;
    }

    /**
     * @inheritdoc
     */
    public function hasEagerLoadedElements(string $handle): bool
    {
        // See if we have this stored with a block type-specific handle
        $blockTypeHandle = $this->getType()->handle . ':' . $handle;

        if (isset($this->_eagerLoadedBlockTypeElements[$blockTypeHandle])) {
            return true;
        }

        return parent::hasEagerLoadedElements($handle);
    }

    /**
     * @inheritdoc
     */
    public function getEagerLoadedElements(string $handle)
    {
        // See if we have this stored with a block type-specific handle
        $blockTypeHandle = $this->getType()->handle . ':' . $handle;

        if (isset($this->_eagerLoadedBlockTypeElements[$blockTypeHandle])) {
            return $this->_eagerLoadedBlockTypeElements[$blockTypeHandle];
        }

        return parent::getEagerLoadedElements($handle);
    }

    /**
     * @inheritdoc
     */
    public function setEagerLoadedElements(string $handle, array $elements)
    {
        // See if this was eager-loaded with a block type-specific handle
        $blockTypeHandlePrefix = $this->getType()->handle . ':';
        if (strpos($handle, $blockTypeHandlePrefix) === 0) {
            $this->_eagerLoadedBlockTypeElements[$handle] = $elements;
        } else {
            parent::setEagerLoadedElements($handle, $elements);
        }
    }

    /**
     * @inheritdoc
     */
    public function getHasFreshContent(): bool
    {
        // Defer to the owner element
        $owner = $this->getOwner();

        return $owner ? $owner->getHasFreshContent() : false;
    }

    // Events
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     * @throws Exception if reasons
     */
    public function afterSave(bool $isNew)
    {
        // Get the block record
        if (!$isNew) {
            $record = MatrixBlockRecord::findOne($this->id);

            if (!$record) {
                throw new Exception('Invalid Matrix block ID: ' . $this->id);
            }
        } else {
            $record = new MatrixBlockRecord();
            $record->id = $this->id;
        }

        $record->fieldId = $this->fieldId;
        $record->ownerId = $this->ownerId;
        $record->ownerSiteId = $this->ownerSiteId;
        $record->typeId = $this->typeId;
        $record->sortOrder = $this->sortOrder;
        $record->save(false);

        parent::afterSave($isNew);
    }

    /**
     * @inheritdoc
     */
    public function afterDelete()
    {
        if (Craft::$app->getRequest()->getIsCpRequest() && !Craft::$app->getResponse()->isSent) {
            // Tell the browser to forget about this block
            $session = Craft::$app->getSession();
            $session->addAssetBundleFlash(MatrixAsset::class);
            $session->addJsFlash('Craft.MatrixInput.forgetCollapsedBlockId(' . $this->id . ');');
        }

        parent::afterDelete();
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns the Matrix field.
     *
     * @return Matrix
     */
    private function _getField(): Matrix
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return Craft::$app->getFields()->getFieldById($this->fieldId);
    }
}
