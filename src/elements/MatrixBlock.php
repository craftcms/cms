<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements;

use Craft;
use craft\base\BlockElementInterface;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\db\Table;
use craft\elements\db\ElementQueryInterface;
use craft\elements\db\MatrixBlockQuery;
use craft\fields\Matrix;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\models\MatrixBlockType;
use craft\models\MatrixBlockType as MatrixBlockTypeModel;
use craft\records\MatrixBlock as MatrixBlockRecord;
use craft\web\assets\matrix\MatrixAsset;
use yii\base\Exception;
use yii\base\InvalidConfigException;

/**
 * MatrixBlock represents a matrix block element.
 *
 * @property ElementInterface $owner the owner
 * @property MatrixBlockType $type The block type
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class MatrixBlock extends Element implements BlockElementInterface
{
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
    public static function lowerDisplayName(): string
    {
        return Craft::t('app', 'Matrix block');
    }

    /**
     * @inheritdoc
     */
    public static function pluralDisplayName(): string
    {
        return Craft::t('app', 'Matrix Blocks');
    }

    /**
     * @inheritdoc
     */
    public static function pluralLowerDisplayName(): string
    {
        return Craft::t('app', 'Matrix blocks');
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

        [$blockTypeHandle, $fieldHandle] = $handleParts;

        // Get the block type
        $matrixFieldId = ArrayHelper::firstValue($sourceElements)->fieldId;
        $blockTypes = ArrayHelper::index(Craft::$app->getMatrix()->getBlockTypesByFieldId($matrixFieldId), 'handle');

        if (!isset($blockTypes[$blockTypeHandle])) {
            // Not a valid block type handle (assuming all $sourceElements are blocks from the same Matrix field)
            return false;
        }

        $blockType = $blockTypes[$blockTypeHandle];

        // Set the field context
        $contentService = Craft::$app->getContent();
        $originalFieldContext = $contentService->fieldContext;
        $contentService->fieldContext = 'matrixBlockType:' . $blockType->uid;

        $map = parent::eagerLoadingMap($sourceElements, $fieldHandle);

        $contentService->fieldContext = $originalFieldContext;

        return $map;
    }

    /**
     * @inheritdoc
     * @since 3.3.0
     */
    public static function gqlTypeNameByContext($context): string
    {
        /** @var MatrixBlockTypeModel $context */
        return $context->getField()->handle . '_' . $context->handle . '_BlockType';
    }

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
     * @deprecated in 3.2.0. Use [[$siteId]] instead.
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
     * @var bool Whether the block has changed.
     * @internal
     * @since 3.4.0
     */
    public $dirty = false;

    /**
     * @var bool Collapsed
     */
    public $collapsed = false;

    /**
     * @var bool Whether the block was deleted along with its owner
     * @see beforeDelete()
     */
    public $deletedWithOwner = false;

    /**
     * @var ElementInterface|null The owner element, or false if [[ownerId]] is invalid
     */
    private $_owner;

    /**
     * @var ElementInterface[]|null
     */
    private $_eagerLoadedBlockTypeElements;

    /**
     * @inheritdoc
     */
    public function attributes()
    {
        $names = parent::attributes();
        $names[] = 'owner';
        return $names;
    }

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
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['fieldId', 'ownerId', 'typeId', 'sortOrder'], 'number', 'integerOnly' => true];
        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getSupportedSites(): array
    {
        try {
            $owner = $this->getOwner();
        } catch (InvalidConfigException $e) {
            $owner = $this->duplicateOf;
        }

        if (!$owner) {
            return [Craft::$app->getSites()->getPrimarySite()->id];
        }

        return Craft::$app->getMatrix()->getSupportedSiteIds($this->_field()->propagationMethod, $owner);
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    public function getCacheTags(): array
    {
        return [
            "field-owner:$this->fieldId-$this->ownerId",
            "field:$this->fieldId",
            "owner:$this->ownerId",
        ];
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
            throw new InvalidConfigException('Invalid Matrix block type ID: ' . $this->typeId);
        }

        return $blockType;
    }

    /** @inheritdoc */
    public function getOwner(): ElementInterface
    {
        if ($this->_owner === null) {
            if ($this->ownerId === null) {
                throw new InvalidConfigException('Matrix block is missing its owner ID');
            }

            if (($this->_owner = Craft::$app->getElements()->getElementById($this->ownerId, null, $this->siteId)) === null) {
                throw new InvalidConfigException('Invalid owner ID: ' . $this->ownerId);
            }
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
        return $this->_field()->contentTable;
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
        return 'matrixBlockType:' . $this->getType()->uid;
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
     * @since 3.3.0
     */
    public function getGqlTypeName(): string
    {
        return static::gqlTypeNameByContext($this->getType());
    }

    // Events
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     * @throws Exception if reasons
     */
    public function afterSave(bool $isNew)
    {
        if (!$this->propagating) {
            // Get the block record
            if (!$isNew) {
                $record = MatrixBlockRecord::findOne($this->id);

                if (!$record) {
                    throw new Exception('Invalid Matrix block ID: ' . $this->id);
                }
            } else {
                $record = new MatrixBlockRecord();
                $record->id = (int)$this->id;
            }

            $record->fieldId = (int)$this->fieldId;
            $record->ownerId = (int)$this->ownerId;
            $record->typeId = (int)$this->typeId;
            $record->sortOrder = (int)$this->sortOrder ?: null;
            $record->save(false);
        }

        parent::afterSave($isNew);
    }

    /**
     * @inheritdoc
     */
    public function beforeDelete(): bool
    {
        if (!parent::beforeDelete()) {
            return false;
        }

        // Update the block record
        Db::update(Table::MATRIXBLOCKS, [
            'deletedWithOwner' => $this->deletedWithOwner,
        ], [
            'id' => $this->id,
        ], [], false);

        return true;
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

    /**
     * Returns the Matrix field.
     *
     * @return Matrix
     */
    private function _field(): Matrix
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return Craft::$app->getFields()->getFieldById($this->fieldId);
    }
}
