<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Elements;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Concerns\NestedElement;
use CraftCms\Cms\Element\Contracts\NestedElementInterface;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Queries\ContentBlockQuery;
use CraftCms\Cms\Field\ContentBlock as ContentBlockField;
use CraftCms\Cms\Field\Elements\Concerns\LegacyConstants;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Field\Models\ContentBlock as ContentBlockModel;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Gql\Interfaces\Elements\ContentBlock as ContentBlockInterface;
use CraftCms\RulesetValidation\Attributes\Ruleset;
use GraphQL\Type\Definition\Type;
use Override;

use function CraftCms\Cms\t;

/**
 * @method ContentBlockField getField()
 */
#[Ruleset(ContentBlockRules::class)]
class ContentBlock extends Element implements NestedElementInterface
{
    use LegacyConstants;
    use NestedElement;

    #[Override]
    public static function displayName(): string
    {
        return t('Content Block');
    }

    #[Override]
    public static function lowerDisplayName(): string
    {
        return t('content block');
    }

    #[Override]
    public static function pluralDisplayName(): string
    {
        return t('Content Blocks');
    }

    #[Override]
    public static function pluralLowerDisplayName(): string
    {
        return t('content blocks');
    }

    public static function refHandle(): string
    {
        return 'block';
    }

    #[Override]
    public static function hasDrafts(): bool
    {
        return true;
    }

    #[Override]
    public static function trackChanges(): bool
    {
        return true;
    }

    #[Override]
    public static function isLocalized(): bool
    {
        return true;
    }

    /**
     * @return ContentBlockQuery The newly created [[ContentBlockQuery]] instance.
     */
    #[Override]
    public static function find(): ContentBlockQuery
    {
        return new ContentBlockQuery;
    }

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

    #[Override]
    public static function baseGqlType(): Type
    {
        return ContentBlockInterface::getType();
    }

    /** @return list<int|array{siteId: int, propagate?: bool, enabledByDefault?: bool}> */
    #[Override]
    public function getSupportedSites(): array
    {
        return $this->getField()->getSupportedSitesForElement($this);
    }

    #[Override]
    protected function cacheTags(): array
    {
        return [
            "field:$this->fieldId",
            "owner:$this->ownerId",
        ];
    }

    #[Override]
    public function getUiLabel(): string
    {
        return '';
    }

    protected function uiLabel(): ?string
    {
        return null;
    }

    #[Override]
    public function getFieldLayout(): FieldLayout
    {
        return $this->getField()->getFieldLayout();
    }

    #[Override]
    public function getGqlTypeName(): string
    {
        return self::gqlTypeName($this->getField());
    }

    // Events
    // -------------------------------------------------------------------------

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
