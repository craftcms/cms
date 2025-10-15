<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements;

use Craft;
use craft\base\Element;
use craft\behaviors\DraftBehavior;
use craft\controllers\ElementIndexesController;
use craft\db\Query;
use craft\db\Table;
use craft\elements\actions\Delete;
use craft\elements\actions\Duplicate;
use craft\elements\actions\NewChild;
use craft\elements\actions\Restore;
use craft\elements\conditions\categories\CategoryCondition;
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\db\CategoryQuery;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\gql\interfaces\elements\Category as CategoryInterface;
use craft\helpers\Cp;
use craft\helpers\Db;
use craft\helpers\Html;
use craft\helpers\UrlHelper;
use craft\models\CategoryGroup;
use craft\models\FieldLayout;
use craft\records\Category as CategoryRecord;
use craft\services\ElementSources;
use craft\services\Structures;
use GraphQL\Type\Definition\Type;
use yii\base\Exception;
use yii\base\InvalidConfigException;

/**
 * Category represents a category element.
 *
 * @property CategoryGroup $group the category's group
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Category extends Element
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Category');
    }

    /**
     * @inheritdoc
     */
    public static function lowerDisplayName(): string
    {
        return Craft::t('app', 'category');
    }

    /**
     * @inheritdoc
     */
    public static function pluralDisplayName(): string
    {
        return Craft::t('app', 'Categories');
    }

    /**
     * @inheritdoc
     */
    public static function pluralLowerDisplayName(): string
    {
        return Craft::t('app', 'categories');
    }

    /**
     * @inheritdoc
     */
    public static function refHandle(): ?string
    {
        return 'category';
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
    public static function hasTitles(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function hasUris(): bool
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
     * @return CategoryQuery The newly created [[CategoryQuery]] instance.
     */
    public static function find(): CategoryQuery
    {
        return new CategoryQuery(static::class);
    }

    /**
     * @inheritdoc
     * @return CategoryCondition
     */
    public static function createCondition(): ElementConditionInterface
    {
        return Craft::createObject(CategoryCondition::class, [static::class]);
    }

    /**
     * Returns the GraphQL type name that categories should use, based on their category group.
     *
     * @since 5.0.0
     */
    public static function gqlTypeName(CategoryGroup $categoryGroup): string
    {
        return sprintf('%s_Category', $categoryGroup->handle);
    }

    /**
     * @inheritdoc
     */
    public static function baseGqlType(): Type
    {
        return CategoryInterface::getType();
    }

    /**
     * @inheritdoc
     * @since 3.3.0
     */
    public static function gqlScopesByContext(mixed $context): array
    {
        /** @var CategoryGroup $context */
        return ['categorygroups.' . $context->uid];
    }

    /**
     * @inheritdoc
     */
    protected static function defineSources(string $context): array
    {
        $sources = [];

        if ($context === ElementSources::CONTEXT_INDEX) {
            $groups = Craft::$app->getCategories()->getEditableGroups();
        } else {
            $groups = Craft::$app->getCategories()->getAllGroups();
        }

        foreach ($groups as $group) {
            $sources[] = [
                'key' => 'group:' . $group->uid,
                'label' => Craft::t('site', $group->name),
                'data' => ['handle' => $group->handle],
                'criteria' => ['groupId' => $group->id],
                'structureId' => $group->structureId,
                'structureEditable' => Craft::$app->getRequest()->getIsConsoleRequest() || Craft::$app->getUser()->checkPermission("viewCategories:$group->uid"),
            ];
        }

        return $sources;
    }

    /**
     * @inheritdoc
     */
    protected static function defineFieldLayouts(?string $source): array
    {
        if ($source !== null && preg_match('/^group:(.+)$/', $source, $matches)) {
            $groups = array_filter([
                Craft::$app->getCategories()->getGroupByUid($matches[1]),
            ]);
        } else {
            $groups = Craft::$app->getCategories()->getAllGroups();
        }

        return array_map(fn(CategoryGroup $group) => $group->getFieldLayout(), $groups);
    }

    /**
     * @inheritdoc
     */
    protected static function defineActions(string $source): array
    {
        // Get the selected site
        $controller = Craft::$app->controller;
        if ($controller instanceof ElementIndexesController) {
            /** @var ElementQuery $elementQuery */
            $elementQuery = $controller->getElementQuery();
        } else {
            $elementQuery = null;
        }
        $site = $elementQuery && $elementQuery->siteId
            ? Craft::$app->getSites()->getSiteById($elementQuery->siteId)
            : Craft::$app->getSites()->getCurrentSite();

        // Get the group we need to check permissions on
        if (preg_match('/^group:(\d+)$/', $source, $matches)) {
            $group = Craft::$app->getCategories()->getGroupById((int)$matches[1]);
        } elseif (preg_match('/^group:(.+)$/', $source, $matches)) {
            $group = Craft::$app->getCategories()->getGroupByUid($matches[1]);
        } else {
            $group = null;
        }

        // Now figure out what we can do with it
        $actions = [];
        $elementsService = Craft::$app->getElements();

        if ($group) {
            // New Child
            if ($group->maxLevels != 1) {
                $newChildUrl = 'categories/' . $group->handle . '/new';

                if (Craft::$app->getIsMultiSite()) {
                    $newChildUrl .= '?site=' . $site->handle;
                }

                $actions[] = $elementsService->createAction([
                    'type' => NewChild::class,
                    'maxLevels' => $group->maxLevels,
                    'newChildUrl' => $newChildUrl,
                ]);
            }

            // Duplicate
            $actions[] = Duplicate::class;

            if ($group->maxLevels != 1) {
                $actions[] = [
                    'type' => Duplicate::class,
                    'deep' => true,
                ];
            }

            // Delete
            $actions[] = Delete::class;

            if ($group->maxLevels != 1) {
                $actions[] = [
                    'type' => Delete::class,
                    'withDescendants' => true,
                ];
            }
        }

        // Restore
        $actions[] = Restore::class;

        return $actions;
    }

    /**
     * @inheritdoc
     */
    protected static function includeSetStatusAction(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    protected static function defineSortOptions(): array
    {
        return [
            'title' => Craft::t('app', 'Title'),
            'slug' => Craft::t('app', 'Slug'),
            'uri' => Craft::t('app', 'URI'),
            [
                'label' => Craft::t('app', 'Date Created'),
                'orderBy' => 'dateCreated',
                'defaultDir' => 'desc',
            ],
            [
                'label' => Craft::t('app', 'Date Updated'),
                'orderBy' => 'dateUpdated',
                'defaultDir' => 'desc',
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    protected static function defineTableAttributes(): array
    {
        return array_merge(parent::defineTableAttributes(), [
            'ancestors' => ['label' => Craft::t('app', 'Ancestors')],
            'parent' => ['label' => Craft::t('app', 'Parent')],
        ]);
    }

    /**
     * @inheritdoc
     */
    protected static function defineDefaultTableAttributes(string $source): array
    {
        return [
            'status',
            'link',
        ];
    }

    /**
     * @inheritdoc
     */
    protected static function defineCardAttributes(): array
    {
        return array_merge(parent::defineCardAttributes(), [
            'parent' => [
                'label' => Craft::t('app', 'Parent'),
                'placeholder' => fn() => Html::tag(
                    'span',
                    Craft::t('app', 'Parent {type} Title', ['type' => self::displayName()]),
                    ['class' => 'card-placeholder'],
                ),
            ],
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function attributePreviewHtml(array $attribute): mixed
    {
        return match ($attribute['value']) {
            'parent' => $attribute['placeholder'],
            default => parent::attributePreviewHtml($attribute),
        };
    }

    /**
     * @var int|null Group ID
     */
    public ?int $groupId = null;

    /**
     * @var bool Whether the category was deleted along with its group
     * @see beforeDelete()
     */
    public bool $deletedWithGroup = false;

    /**
     * @inheritdoc
     */
    public function extraFields(): array
    {
        $names = parent::extraFields();
        $names[] = 'group';
        return $names;
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['groupId'], 'number', 'integerOnly' => true];
        return $rules;
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    protected function cacheTags(): array
    {
        return [
            "group:$this->groupId",
        ];
    }

    /**
     * @inheritdoc
     */
    public function getUriFormat(): ?string
    {
        $categoryGroupSiteSettings = $this->getGroup()->getSiteSettings();

        if (!isset($categoryGroupSiteSettings[$this->siteId])) {
            throw new InvalidConfigException('Category’s group (' . $this->groupId . ') is not enabled for site ' . $this->siteId);
        }

        return $categoryGroupSiteSettings[$this->siteId]->uriFormat;
    }

    /**
     * @inheritdoc
     */
    protected function previewTargets(): array
    {
        $previewTargets = [];

        if ($url = $this->getUrl()) {
            $previewTargets[] = [
                'label' => Craft::t('app', 'Primary {type} page', [
                    'type' => self::lowerDisplayName(),
                ]),
                'url' => $url,
            ];
        }

        return $previewTargets;
    }

    /**
     * @inheritdoc
     */
    protected function route(): array|string|null
    {
        // Make sure the category group is set to have URLs for this site
        $categoryGroupSiteSettings = $this->getGroup()->getSiteSettings()[$this->siteId] ?? null;

        if (!$categoryGroupSiteSettings?->hasUrls) {
            return null;
        }

        return [
            'templates/render', [
                'template' => (string)$categoryGroupSiteSettings->template,
                'variables' => [
                    'category' => $this,
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function crumbs(): array
    {
        $group = $this->getGroup();

        $crumbs = [
            [
                'label' => Craft::t('app', 'Categories'),
                'url' => UrlHelper::url('categories'),
            ],
            [
                'label' => Craft::t('site', $group->name),
                'url' => UrlHelper::url('categories/' . $group->handle),
            ],
        ];

        $elementsService = Craft::$app->getElements();
        $user = Craft::$app->getUser()->getIdentity();

        $ancestors = $this->getAncestors();
        if ($ancestors instanceof ElementQueryInterface) {
            $ancestors->status(null);
        }

        foreach ($ancestors->all() as $ancestor) {
            if ($elementsService->canView($ancestor, $user)) {
                $crumbs[] = [
                    'html' => Cp::elementChipHtml($ancestor, ['class' => 'chromeless']),
                ];
            }
        }

        return $crumbs;
    }

    /**
     * @inheritdoc
     */
    public function createAnother(): ?self
    {
        $group = $this->getGroup();

        /** @var self $category */
        $category = Craft::createObject([
            'class' => self::class,
            'groupId' => $this->groupId,
            'siteId' => $this->siteId,
        ]);

        $category->enabled = $this->enabled;
        $category->setEnabledForSite($this->getEnabledForSite());

        // Structure parent
        if ($group->maxLevels !== 1) {
            $category->setParentId($this->getParentId());
        }

        return $category;
    }

    /**
     * @inheritdoc
     */
    public function canView(User $user): bool
    {
        if (parent::canView($user)) {
            return true;
        }

        $group = $this->getGroup();

        if ($this->getIsDraft() && $this->getIsDerivative()) {
            /**
             * @var static|DraftBehavior $this
             * @phpstan-ignore varTag.nativeType
             */
            return (
                $this->creatorId === $user->id ||
                $user->can("viewPeerCategoryDrafts:$group->uid")
            );
        }

        return $user->can("viewCategories:$group->uid");
    }

    /**
     * @inheritdoc
     */
    public function canSave(User $user): bool
    {
        if (parent::canSave($user)) {
            return true;
        }

        $group = $this->getGroup();

        if ($this->getIsDraft()) {
            /**
             * @var static|DraftBehavior $this
             * @phpstan-ignore varTag.nativeType
             */
            return (
                $this->creatorId === $user->id ||
                $user->can("savePeerCategoryDrafts:$group->uid")
            );
        }

        return $user->can("saveCategories:$group->uid");
    }

    /**
     * @inheritdoc
     */
    public function canDuplicate(User $user): bool
    {
        if (parent::canDuplicate($user)) {
            return true;
        }

        $group = $this->getGroup();
        return $user->can("saveCategories:$group->uid");
    }

    /**
     * @inheritdoc
     */
    public function canDelete(User $user): bool
    {
        $group = $this->getGroup();

        if (parent::canDelete($user)) {
            return true;
        }

        if ($this->getIsDraft() && $this->getIsDerivative()) {
            /**
             * @var static|DraftBehavior $this
             * @phpstan-ignore varTag.nativeType
             */
            return (
                $this->creatorId === $user->id ||
                $user->can("deletePeerCategoryDrafts:$group->uid")
            );
        }

        return $user->can("deleteCategories:$group->uid");
    }

    /**
     * @inheritdoc
     */
    public function canCreateDrafts(User $user): bool
    {
        // Everyone with view permissions can create drafts
        return true;
    }

    /**
     * @inheritdoc
     */
    protected function cpEditUrl(): ?string
    {
        $group = $this->getGroup();

        $path = sprintf('categories/%s/%s', $group->handle, $this->getCanonicalId());

        // Ignore homepage/temp slugs
        if ($this->slug && !str_starts_with($this->slug, '__')) {
            $path .= sprintf('-%s', str_replace('/', '-', $this->slug));
        }

        return UrlHelper::cpUrl($path);
    }

    /**
     * @inheritdoc
     */
    public function getPostEditUrl(): ?string
    {
        return UrlHelper::cpUrl('categories');
    }

    /**
     * @inheritdoc
     */
    public function getFieldLayout(): ?FieldLayout
    {
        try {
            return $this->getGroup()->getFieldLayout();
        } catch (InvalidConfigException) {
            return null;
        }
    }

    /**
     * @inheritdoc
     */
    protected function metaFieldsHtml(bool $static): string
    {
        $fields = [
            $this->slugFieldHtml($static),
        ];

        $group = $this->getGroup();

        if ($group->maxLevels !== 1) {
            $fields[] = (function() use ($static, $group) {
                if ($parentId = $this->getParentId()) {
                    $parent = Craft::$app->getCategories()->getCategoryById($parentId, $this->siteId, [
                        'drafts' => null,
                        'draftOf' => false,
                    ]);
                } else {
                    // If the category already has structure data, use it. Otherwise, use its canonical category
                    /** @var self|null $parent */
                    $parent = self::find()
                        ->siteId($this->siteId)
                        ->ancestorOf($this->lft ? $this : ($this->getIsCanonical() ? $this->id : $this->getCanonical(true)))
                        ->ancestorDist(1)
                        ->drafts(null)
                        ->draftOf(false)
                        ->status(null)
                        ->one();
                }

                return Cp::elementSelectFieldHtml([
                    'label' => Craft::t('app', 'Parent'),
                    'id' => 'parentId',
                    'name' => 'parentId',
                    'elementType' => self::class,
                    'selectionLabel' => Craft::t('app', 'Choose'),
                    'sources' => ["group:$group->uid"],
                    'criteria' => $this->_parentOptionCriteria($group),
                    'limit' => 1,
                    'elements' => $parent ? [$parent] : [],
                    'disabled' => $static,
                    'describedBy' => 'parentId-label',
                ]);
            })();
        }

        $fields[] = parent::metaFieldsHtml($static);

        return implode("\n", $fields);
    }

    private function _parentOptionCriteria(CategoryGroup $group): array
    {
        $parentOptionCriteria = [
            'siteId' => $this->siteId,
            'groupId' => $group->id,
            'status' => null,
            'drafts' => null,
            'draftOf' => false,
        ];

        // Prevent the current entry, or any of its descendants, from being selected as a parent
        if ($this->id) {
            $excludeIds = self::find()
                ->descendantOf($this)
                ->drafts(null)
                ->draftOf(false)
                ->status(null)
                ->ids();
            $excludeIds[] = $this->getCanonicalId();
            $parentOptionCriteria['id'] = array_merge(['not'], $excludeIds);
        }

        if ($group->maxLevels) {
            if ($this->id) {
                // Figure out how deep the ancestors go
                $maxDepth = self::find()
                    ->select('level')
                    ->descendantOf($this)
                    ->status(null)
                    ->leaves()
                    ->scalar();
                $depth = 1 + ($maxDepth ?: $this->level) - $this->level;
            } else {
                $depth = 1;
            }

            $parentOptionCriteria['level'] = sprintf('<=%s', $group->maxLevels - $depth);
        }

        return $parentOptionCriteria;
    }

    /**
     * Returns the category's group.
     *
     * @return CategoryGroup
     * @throws InvalidConfigException if [[groupId]] is missing or invalid
     */
    public function getGroup(): CategoryGroup
    {
        if (!isset($this->groupId)) {
            throw new InvalidConfigException('Category is missing its group ID');
        }

        $group = Craft::$app->getCategories()->getGroupById($this->groupId);

        if (!$group) {
            throw new InvalidConfigException('Invalid category group ID: ' . $this->groupId);
        }

        return $group;
    }

    // Indexes, etc.
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    protected function inlineAttributeInputHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'slug':
                return Cp::textHtml([
                    'name' => 'slug',
                    'value' => $this->slug,
                ]);
            default:
                return parent::inlineAttributeInputHtml($attribute);
        }
    }

    /**
     * @inheritdoc
     * @since 3.3.0
     */
    public function getGqlTypeName(): string
    {
        return static::gqlTypeName($this->getGroup());
    }

    // Events
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     * @throws Exception if reasons
     */
    public function beforeSave(bool $isNew): bool
    {
        // Set the structure ID for Element::attributes() and afterSave()
        $this->structureId = $this->getGroup()->structureId;

        // Has the category been assigned a new parent?
        if (!$this->duplicateOf && $this->hasNewParent()) {
            if ($parentId = $this->getParentId()) {
                $parentCategory = Craft::$app->getCategories()->getCategoryById($parentId, $this->siteId, [
                    'drafts' => null,
                    'draftOf' => false,
                ]);

                if (!$parentCategory) {
                    throw new InvalidConfigException("Invalid category ID: $parentId");
                }
            } else {
                $parentCategory = null;
            }

            $this->setParent($parentCategory);
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
            $group = $this->getGroup();

            // Get the category record
            if (!$isNew) {
                $record = CategoryRecord::findOne($this->id);

                if (!$record) {
                    throw new InvalidConfigException("Invalid category ID: $this->id");
                }
            } else {
                $record = new CategoryRecord();
                $record->id = (int)$this->id;
            }

            $record->groupId = (int)$this->groupId;
            $record->save(false);

            if (!$this->duplicateOf || $this->updatingFromDerivative) {
                // Has the parent changed?
                if ($this->hasNewParent()) {
                    $this->_placeInStructure($isNew, $group);
                }

                // Update the category's descendants, who may be using this category's URI in their own URIs
                if (!$isNew && $this->getIsCanonical()) {
                    Craft::$app->getElements()->updateDescendantSlugsAndUris($this, true, true);
                }
            }
        }

        parent::afterSave($isNew);
    }

    private function _placeInStructure(bool $isNew, CategoryGroup $group): void
    {
        $parentId = $this->getParentId();
        $structuresService = Craft::$app->getStructures();

        // If this is a provisional draft and its new parent matches the canonical entry’s, just drop it from the structure
        if ($this->isProvisionalDraft) {
            $canonicalParentId = self::find()
                ->select(['elements.id'])
                ->ancestorOf($this->getCanonicalId())
                ->ancestorDist(1)
                ->status(null)
                ->scalar();

            if ($parentId == $canonicalParentId) {
                $structuresService->remove($this->structureId, $this);
                return;
            }
        }

        $mode = $isNew ? Structures::MODE_INSERT : Structures::MODE_AUTO;

        if (!$parentId) {
            if ($group->defaultPlacement === CategoryGroup::DEFAULT_PLACEMENT_BEGINNING) {
                $structuresService->prependToRoot($this->structureId, $this, $mode);
            } else {
                $structuresService->appendToRoot($this->structureId, $this, $mode);
            }
        } else {
            if ($group->defaultPlacement === CategoryGroup::DEFAULT_PLACEMENT_BEGINNING) {
                $structuresService->prepend($this->structureId, $this, $this->getParent(), $mode);
            } else {
                $structuresService->append($this->structureId, $this, $this->getParent(), $mode);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function beforeDelete(): bool
    {
        if (!parent::beforeDelete()) {
            return false;
        }

        // Update the category record
        $data = [
            'deletedWithGroup' => $this->deletedWithGroup,
            'parentId' => null,
        ];

        if ($this->structureId) {
            // Remember the parent ID, in case the category needs to be restored later
            $parentId = $this->ancestors()
                ->ancestorDist(1)
                ->status(null)
                ->select(['elements.id'])
                ->scalar();
            if ($parentId) {
                $data['parentId'] = $parentId;
            }
        }

        Db::update(Table::CATEGORIES, $data, [
            'id' => $this->id,
        ], [], false);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function afterRestore(): void
    {
        $structureId = $this->getGroup()->structureId;

        // Add the category back into its structure
        /** @var self|null $parent */
        $parent = self::find()
            ->structureId($structureId)
            ->innerJoin(['j' => Table::CATEGORIES], '[[j.parentId]] = [[elements.id]]')
            ->andWhere(['j.id' => $this->id])
            ->one();

        if (!$parent) {
            Craft::$app->getStructures()->appendToRoot($structureId, $this);
        } else {
            Craft::$app->getStructures()->append($structureId, $this, $parent);
        }

        parent::afterRestore();
    }

    /**
     * @inheritdoc
     */
    public function afterMoveInStructure(int $structureId): void
    {
        // Was the category moved within its group's structure?
        if ($this->getGroup()->structureId == $structureId) {
            // Update its URI
            Craft::$app->getElements()->updateElementSlugAndUri($this, true, true, true);

            // Make sure that each of the category's ancestors are related wherever the category is related
            $newRelationValues = [];

            $ancestorIds = $this->ancestors()
                ->status(null)
                ->ids();

            $sources = (new Query())
                ->select(['fieldId', 'sourceId', 'sourceSiteId'])
                ->from([Table::RELATIONS])
                ->where(['targetId' => $this->id])
                ->all();

            foreach ($sources as $source) {
                $existingAncestorRelations = (new Query())
                    ->select(['targetId'])
                    ->from([Table::RELATIONS])
                    ->where([
                        'fieldId' => $source['fieldId'],
                        'sourceId' => $source['sourceId'],
                        'sourceSiteId' => $source['sourceSiteId'],
                        'targetId' => $ancestorIds,
                    ])
                    ->column();

                $missingAncestorRelations = array_diff($ancestorIds, $existingAncestorRelations);

                foreach ($missingAncestorRelations as $categoryId) {
                    $newRelationValues[] = [
                        $source['fieldId'],
                        $source['sourceId'],
                        $source['sourceSiteId'],
                        $categoryId,
                    ];
                }
            }

            if (!empty($newRelationValues)) {
                Db::batchInsert(Table::RELATIONS, ['fieldId', 'sourceId', 'sourceSiteId', 'targetId'], $newRelationValues);
            }
        }

        parent::afterMoveInStructure($structureId);
    }
}
