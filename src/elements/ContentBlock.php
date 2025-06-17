<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements;

use Craft;
use craft\base\Element;
use craft\base\NestedElementInterface;
use craft\base\NestedElementTrait;
use craft\db\Table;
use craft\elements\db\ContentBlockQuery;
use craft\fields\ContentBlock as ContentBlockField;
use craft\gql\interfaces\elements\ContentBlock as ContentBlockInterface;
use craft\models\FieldLayout;
use craft\records\ContentBlock as ContentBlockRecord;
use GraphQL\Type\Definition\Type;
use yii\base\InvalidConfigException;

/**
 * Content block element.
 *
 * @method ContentBlockField getField()
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.8.0
 */
class ContentBlock extends Element implements NestedElementInterface
{
    use NestedElementTrait;

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Content Block');
    }

    /**
     * @inheritdoc
     */
    public static function lowerDisplayName(): string
    {
        return Craft::t('app', 'content block');
    }

    /**
     * @inheritdoc
     */
    public static function pluralDisplayName(): string
    {
        return Craft::t('app', 'Content Blocks');
    }

    /**
     * @inheritdoc
     */
    public static function pluralLowerDisplayName(): string
    {
        return Craft::t('app', 'content blocks');
    }

    /**
     * @inheritdoc
     */
    public static function refHandle(): ?string
    {
        return 'block';
    }

    /**
     * @inheritdoc
     */
    public static function hasDrafts(): bool
    {
        return true;
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
    public static function isLocalized(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     * @return ContentBlockQuery The newly created [[ContentBlockQuery]] instance.
     */
    public static function find(): ContentBlockQuery
    {
        return new ContentBlockQuery(static::class);
    }

    /**
     * @inheritdoc
     */
    protected static function defineFieldLayouts(?string $source): array
    {
        /** @var ContentBlockField[] $fields */
        $fields = Craft::$app->getFields()->getFieldsByType(ContentBlockField::class);
        return array_map(fn(ContentBlockField $field) => $field->getFieldLayout(), $fields);
    }

    /**
     * Returns the GraphQL type name that content block elements should use, based on their Content Block field.
     */
    public static function gqlTypeName(ContentBlockField $field): string
    {
        return sprintf('%s_ContentBlock', $field->layoutElement?->getOriginalHandle() ?? $field->handle);
    }

    /**
     * @inheritdoc
     */
    public static function baseGqlType(): Type
    {
        return ContentBlockInterface::getType();
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        return [
            ...parent::defineRules(),
            [['fieldId', 'ownerId', 'primaryOwnerId', 'sortOrder'], 'number', 'integerOnly' => true],
        ];
    }

    /**
     * @inheritdoc
     */
    public function getSupportedSites(): array
    {
        return $this->getField()->getSupportedSitesForElement($this);
    }

    /**
     * @inheritdoc
     */
    protected function cacheTags(): array
    {
        return [
            "field:$this->fieldId",
            "owner:$this->ownerId",
        ];
    }

    /**
     * @inheritdoc
     */
    public function getUiLabel(): string
    {
        return '';
    }

    /**
     * @inheritdoc
     */
    protected function uiLabel(): ?string
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getFieldLayout(): ?FieldLayout
    {
        return $this->getField()->getFieldLayout();
    }

    /**
     * @inheritdoc
     */
    public function getGqlTypeName(): string
    {
        return self::gqlTypeName($this->getField());
    }

    // Events
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function afterSave(bool $isNew): void
    {
        if (!$this->propagating) {
            // Get the content block record
            if (!$isNew) {
                $record = ContentBlockRecord::findOne($this->id);

                if (!$record) {
                    throw new InvalidConfigException("Invalid content block ID: $this->id");
                }
            } else {
                $record = new ContentBlockRecord();
                $record->id = (int)$this->id;
            }

            $record->fieldId = $this->fieldId;
            $record->primaryOwnerId = $this->getPrimaryOwnerId();

            // Capture the dirty attributes from the record
            $dirtyAttributes = array_keys($record->getDirtyAttributes());
            $record->save(false);

            $this->setDirtyAttributes($dirtyAttributes);

            $this->saveOwnership($isNew, Table::CONTENTBLOCKS);
        }

        parent::afterSave($isNew);
    }
}
