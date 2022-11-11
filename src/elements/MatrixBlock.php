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
use craft\elements\db\MatrixBlockQuery;
use craft\fields\Matrix;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\models\FieldLayout;
use craft\models\MatrixBlockType;
use craft\models\MatrixBlockType as MatrixBlockTypeModel;
use craft\records\MatrixBlock as MatrixBlockRecord;
use craft\web\assets\matrix\MatrixAsset;
use Illuminate\Support\Collection;
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
    public static function refHandle(): ?string
    {
        return 'matrixblock';
    }

    /**
     * @inheritdoc
     */
    public static function trackChanges(): bool
    {
        return true;
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
    public static function find(): MatrixBlockQuery
    {
        return new MatrixBlockQuery(static::class);
    }

    /**
     * @inheritdoc
     */
    public static function eagerLoadingMap(array $sourceElements, string $handle): array|null|false
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
    public static function gqlTypeNameByContext(mixed $context): string
    {
        /** @var MatrixBlockTypeModel $context */
        return $context->getField()->handle . '_' . $context->handle . '_BlockType';
    }

    /**
     * @var int|null Field ID
     */
    public ?int $fieldId = null;

    /**
     * @var int|null Primary owner ID
     * @since 4.0.0
     */
    public ?int $primaryOwnerId = null;

    /**
     * @var int|null Owner ID
     */
    public ?int $ownerId = null;

    /**
     * @var int|null Type ID
     */
    public ?int $typeId = null;

    /**
     * @var int|null Sort order
     */
    public ?int $sortOrder = null;

    /**
     * @var bool Whether the block has changed.
     * @internal
     * @since 3.4.0
     */
    public bool $dirty = false;

    /**
     * @var bool Collapsed
     */
    public bool $collapsed = false;

    /**
     * @var bool Whether the block was deleted along with its owner
     * @see beforeDelete()
     */
    public bool $deletedWithOwner = false;

    /**
     * @var bool Whether to save the block’s row in the `matrixblocks_owners` table in [[afterSave()]].
     * @since 4.0.0
     */
    public bool $saveOwnership = true;

    /**
     * @var ElementInterface|null The owner element, or false if [[ownerId]] is invalid
     */
    private ?ElementInterface $_owner = null;

    /**
     * @var ElementInterface[]|null
     */
    private ?array $_eagerLoadedBlockTypeElements = null;

    /**
     * @inheritdoc
     */
    public function attributes(): array
    {
        $names = parent::attributes();
        $names[] = 'owner';
        return $names;
    }

    /**
     * @inheritdoc
     */
    public function extraFields(): array
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
        $rules[] = [['fieldId', 'primaryOwnerId', 'typeId', 'sortOrder'], 'number', 'integerOnly' => true];
        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getSupportedSites(): array
    {
        try {
            $owner = $this->getOwner();
        } catch (InvalidConfigException) {
            $owner = $this->duplicateOf;
        }

        if (!$owner) {
            return [Craft::$app->getSites()->getPrimarySite()->id];
        }

        $field = $this->_field();
        return Craft::$app->getMatrix()->getSupportedSiteIds($field->propagationMethod, $owner, $field->propagationKeyFormat);
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    protected function cacheTags(): array
    {
        return [
            "field-owner:$this->fieldId-$this->primaryOwnerId",
            "field:$this->fieldId",
            "owner:$this->primaryOwnerId",
        ];
    }

    /**
     * @inheritdoc
     */
    public function getFieldLayout(): ?FieldLayout
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
        if (!isset($this->typeId)) {
            throw new InvalidConfigException('Matrix block is missing its type ID');
        }

        $blockType = Craft::$app->getMatrix()->getBlockTypeById($this->typeId);

        if (!$blockType) {
            throw new InvalidConfigException('Invalid Matrix block type ID: ' . $this->typeId);
        }

        return $blockType;
    }

    /**
     * @inheritdoc
     */
    public function getOwner(): ElementInterface
    {
        if (!isset($this->_owner)) {
            if (!isset($this->ownerId)) {
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
    public function setOwner(?ElementInterface $owner = null): void
    {
        $this->_owner = $owner;
        $this->ownerId = $owner->id;
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
     * Returns the field context this element’s content uses.
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
    public function getEagerLoadedElements(string $handle): ?Collection
    {
        // See if we have this stored with a block type-specific handle
        $blockTypeHandle = $this->getType()->handle . ':' . $handle;

        if (isset($this->_eagerLoadedBlockTypeElements[$blockTypeHandle])) {
            return ElementCollection::make($this->_eagerLoadedBlockTypeElements[$blockTypeHandle]);
        }

        return parent::getEagerLoadedElements($handle);
    }

    /**
     * @inheritdoc
     */
    public function setEagerLoadedElements(string $handle, array $elements): void
    {
        // See if this was eager-loaded with a block type-specific handle
        $blockTypeHandlePrefix = $this->getType()->handle . ':';
        if (str_starts_with($handle, $blockTypeHandlePrefix)) {
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
     */
    public function beforeSave(bool $isNew): bool
    {
        if (!$this->primaryOwnerId && !$this->ownerId) {
            throw new InvalidConfigException('No owner ID assigned to the Matrix block.');
        }

        return parent::beforeSave($isNew);
    }

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function afterSave(bool $isNew): void
    {
        if (!$this->propagating) {
            $this->primaryOwnerId = $this->primaryOwnerId ?? $this->ownerId;
            $this->ownerId = $this->ownerId ?? $this->primaryOwnerId;

            // Get the block record
            if (!$isNew) {
                $record = MatrixBlockRecord::findOne($this->id);

                if (!$record) {
                    throw new InvalidConfigException("Invalid Matrix block ID: $this->id");
                }
            } else {
                $record = new MatrixBlockRecord();
                $record->id = (int)$this->id;
            }

            $record->fieldId = $this->fieldId;
            $record->primaryOwnerId = $this->primaryOwnerId ?? $this->ownerId;
            $record->typeId = $this->typeId;
            $record->save(false);

            // ownerId will be null when creating a revision
            if ($this->saveOwnership) {
                if ($isNew) {
                    Db::insert(Table::MATRIXBLOCKS_OWNERS, [
                        'blockId' => $this->id,
                        'ownerId' => $this->ownerId,
                        'sortOrder' => $this->sortOrder ?? 0,
                    ]);
                } else {
                    Db::update(Table::MATRIXBLOCKS_OWNERS, [
                        'sortOrder' => $this->sortOrder ?? 0,
                    ], [
                        'blockId' => $this->id,
                        'ownerId' => $this->ownerId,
                    ]);
                }
            }
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
    public function afterDelete(): void
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
        /** @phpstan-ignore-next-line */
        return Craft::$app->getFields()->getFieldById($this->fieldId);
    }
}
