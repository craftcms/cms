<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Elements;

use craft\base\Element;
use craft\base\NestedElementInterface;
use craft\base\NestedElementTrait;
use craft\gql\interfaces\elements\ContentBlock as ContentBlockInterface;
use craft\models\FieldLayout;
use CraftCms\Cms\Database\Queries\ContentBlockQuery;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Field\ContentBlock as ContentBlockField;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Field\Models\ContentBlock as ContentBlockModel;
use GraphQL\Type\Definition\Type;
use Override;

use function CraftCms\Cms\t;

/**
 * @method ContentBlockField getField()
 */
final class ContentBlock extends Element implements NestedElementInterface
{
    use NestedElementTrait;

    /**
     * {@inheritdoc}
     */
    #[Override]
    public static function displayName(): string
    {
        return t('Content Block');
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public static function lowerDisplayName(): string
    {
        return t('content block');
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public static function pluralDisplayName(): string
    {
        return t('Content Blocks');
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public static function pluralLowerDisplayName(): string
    {
        return t('content blocks');
    }

    /**
     * {@inheritdoc}
     */
    public static function refHandle(): string
    {
        return 'block';
    }

    /**
     * {@inheritdoc}
     */
    public static function hasDrafts(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public static function trackChanges(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public static function isLocalized(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @return ContentBlockQuery The newly created [[ContentBlockQuery]] instance.
     */
    #[Override]
    public static function find(): ContentBlockQuery
    {
        return new ContentBlockQuery;
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    protected static function defineFieldLayouts(?string $source): array
    {
        /** @var ContentBlockField[] $fields */
        $fields = app(Fields::class)->getFieldsByType(ContentBlockField::class)->all();

        return array_map(fn (ContentBlockField $field) => $field->getFieldLayout(), $fields);
    }

    /**
     * Returns the GraphQL type name that content block elements should use, based on their Content Block field.
     */
    public static function gqlTypeName(ContentBlockField $field): string
    {
        return sprintf('%s_ContentBlock', $field->layoutElement?->getOriginalHandle() ?? $field->handle);
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public static function baseGqlType(): Type
    {
        return ContentBlockInterface::getType();
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    protected function defineRules(): array
    {
        return [
            ...parent::defineRules(),
            [['fieldId', 'ownerId', 'primaryOwnerId', 'sortOrder'], 'number', 'integerOnly' => true],
        ];
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function getSupportedSites(): array
    {
        return $this->getField()->getSupportedSitesForElement($this);
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    protected function cacheTags(): array
    {
        return [
            "field:$this->fieldId",
            "owner:$this->ownerId",
        ];
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function getUiLabel(): string
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function uiLabel(): ?string
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function getFieldLayout(): FieldLayout
    {
        return $this->getField()->getFieldLayout();
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function getGqlTypeName(): string
    {
        return self::gqlTypeName($this->getField());
    }

    // Events
    // -------------------------------------------------------------------------

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function afterSave(bool $isNew): void
    {
        if (! $this->propagating) {
            // Get the content block record
            if (! $isNew) {
                $model = ContentBlockModel::findOrFail($this->id);
            } else {
                $model = new ContentBlockModel;
                $model->id = (int) $this->id;
            }

            $model->fieldId = $this->fieldId;
            $model->primaryOwnerId = $this->getPrimaryOwnerId();

            // Capture the dirty attributes from the record
            $dirtyAttributes = array_keys($model->getDirty());
            $model->save();

            $this->setDirtyAttributes($dirtyAttributes);

            $this->saveOwnership($isNew, Table::CONTENTBLOCKS);
        }

        parent::afterSave($isNew);
    }
}
