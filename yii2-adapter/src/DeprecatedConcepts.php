<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter;

use Craft;
use craft\base\Event as YiiEvent;
use craft\elements\Category;
use craft\elements\GlobalSet;
use craft\elements\Tag;
use craft\events\DefineGqlArgumentsEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterCpNavItemsEvent;
use craft\events\RegisterCpSettingsEvent;
use craft\events\RegisterGqlArgumentHandlersEvent;
use craft\events\RegisterGqlEagerLoadableFields;
use craft\events\RegisterGqlMutationsEvent;
use craft\events\RegisterGqlQueriesEvent;
use craft\events\RegisterGqlSchemaComponentsEvent;
use craft\events\RegisterGqlTypesEvent;
use craft\events\RegisterTemplateRootsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\fields\Categories as CategoriesField;
use craft\fields\linktypes\Category as CategoryLinkType;
use craft\fields\Tags as TagsField;
use craft\gql\ArgumentManager;
use craft\gql\base\ElementArguments;
use craft\gql\ElementQueryConditionBuilder;
use craft\gql\handlers\RelatedCategories;
use craft\gql\handlers\RelatedTags;
use craft\gql\interfaces\elements\Category as CategoryInterface;
use craft\gql\interfaces\elements\GlobalSet as GlobalSetInterface;
use craft\gql\interfaces\elements\Tag as TagInterface;
use craft\gql\mutations\Category as CategoryMutation;
use craft\gql\mutations\GlobalSet as GlobalSetMutation;
use craft\gql\mutations\Tag as TagMutation;
use craft\gql\queries\Category as CategoryQuery;
use craft\gql\queries\GlobalSet as GlobalSetQuery;
use craft\gql\queries\Tag as TagQuery;
use craft\gql\types\input\criteria\CategoryRelation;
use craft\gql\types\input\criteria\TagRelation;
use craft\models\CategoryGroup;
use craft\models\TagGroup;
use craft\services\Elements;
use craft\services\Gql;
use craft\services\ProjectConfig as LegacyProjectConfig;
use craft\services\UserPermissions;
use craft\web\twig\Extension;
use craft\web\twig\GlobalsExtension;
use craft\web\twig\variables\Cp as CpVariable;
use craft\web\UrlManager;
use craft\web\View;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Jobs\PropagateElements;
use CraftCms\Cms\Field\Events\RegisterFieldTypes;
use CraftCms\Cms\Field\Events\RegisterLinkTypes;
use CraftCms\Cms\FieldLayout\Events\DefineNativeFields;
use CraftCms\Cms\FieldLayout\LayoutElements\TitleField;
use CraftCms\Cms\GarbageCollection\Actions\DeleteOrphanedFieldLayouts;
use CraftCms\Cms\GarbageCollection\Actions\DeletePartialElements;
use CraftCms\Cms\GarbageCollection\Actions\HardDelete;
use CraftCms\Cms\GarbageCollection\Events\RunningGarbageCollection;
use CraftCms\Cms\ProjectConfig\Events\RebuildConfig;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Site\Events\SiteSaved;
use CraftCms\Cms\Support\Facades\Twig;
use CraftCms\Cms\View\TemplateMode;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use PDOException;

use function CraftCms\Cms\t;

class DeprecatedConcepts
{
    private static ?bool $supportsCategories = null;

    private static ?bool $supportsGlobalSets = null;

    private static ?bool $supportsTags = null;

    public static function supportsCategories(): bool
    {
        return self::$supportsCategories ??= self::supports('categories');
    }

    public static function supportsGlobalSets(): bool
    {
        return self::$supportsGlobalSets ??= self::supports('globalsets');
    }

    public static function supportsTags(): bool
    {
        return self::$supportsTags ??= self::supports('tags');
    }

    private static function supports(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (PDOException $e) {
            return false;
        }
    }

    public static function resetSupport(): void
    {
        self::$supportsCategories = null;
        self::$supportsGlobalSets = null;
        self::$supportsTags = null;
    }

    public function boot(): void
    {
        Event::listen(RegisterFieldTypes::class, function(RegisterFieldTypes $event) {
            if (DeprecatedConcepts::supportsCategories()) {
                $event->types->add(CategoriesField::class);
            }
            if (DeprecatedConcepts::supportsTags()) {
                $event->types->add(TagsField::class);
            }
        });

        Event::listen(RegisterLinkTypes::class, function(RegisterLinkTypes $event) {
            if (DeprecatedConcepts::supportsCategories()) {
                $event->types[] = CategoryLinkType::class;
            }
        });

        Event::listen(RunningGarbageCollection::class, function(RunningGarbageCollection $event) {
            $event->garbageCollection->runActions(array_filter([
                [HardDelete::class, [
                    'tables' => array_filter([
                        'categorygroups',
                        'taggroups',
                    ], fn(string $table) => Schema::hasTable($table)),
                ]],
                DeprecatedConcepts::supportsCategories() ? [DeletePartialElements::class, ['elementType' => Category::class, 'table' => 'categories']] : null,
                DeprecatedConcepts::supportsGlobalSets() ? [DeletePartialElements::class, ['elementType' => GlobalSet::class, 'table' => 'globalsets']] : null,
                DeprecatedConcepts::supportsTags() ? [DeletePartialElements::class, ['elementType' => Tag::class, 'table' => 'tags']] : null,
                DeprecatedConcepts::supportsCategories() ? [DeleteOrphanedFieldLayouts::class, ['elementType' => Category::class, 'table' => 'categorygroups']] : null,
                DeprecatedConcepts::supportsGlobalSets() ? [DeleteOrphanedFieldLayouts::class, ['elementType' => GlobalSet::class, 'table' => 'globalsets']] : null,
                DeprecatedConcepts::supportsTags() ? [DeleteOrphanedFieldLayouts::class, ['elementType' => Tag::class, 'table' => 'taggroups']] : null,
            ]));
        });

        Event::listen(SiteSaved::class, function(SiteSaved $event) {
            if (!$event->isNew || !$event->oldPrimarySiteId) {
                return;
            }

            if (DeprecatedConcepts::supportsCategories()) {
                $projectConfig = app(ProjectConfig::class);
                $oldPrimarySiteUid = DB::table(Table::SITES)->uidById($event->oldPrimarySiteId);
                $existingCategorySettings = $projectConfig->get(LegacyProjectConfig::PATH_CATEGORY_GROUPS);

                if (!$projectConfig->isApplyingExternalChanges && is_array($existingCategorySettings)) {
                    foreach ($existingCategorySettings as $categoryUid => $settings) {
                        $projectConfig->set(
                            path: LegacyProjectConfig::PATH_CATEGORY_GROUPS . '.' . $categoryUid . '.siteSettings.' . $event->site->uid,
                            value: $settings['siteSettings'][$oldPrimarySiteUid],
                            message: 'Copy site settings for category groups',
                        );
                    }
                }
            }

            $elementTypes = array_keys(array_filter([
                Category::class => DeprecatedConcepts::supportsCategories(),
                GlobalSet::class => DeprecatedConcepts::supportsGlobalSets(),
                Tag::class => DeprecatedConcepts::supportsTags(),
            ]));

            foreach ($elementTypes as $elementType) {
                dispatch(new PropagateElements(
                    elementType: $elementType,
                    criteria: [
                        'siteId' => $event->oldPrimarySiteId,
                    ],
                    siteId: $event->site->id,
                    isNewSite: true,
                ));
            }
        });

        Event::listen(RebuildConfig::class, function(RebuildConfig $event) {
            if (DeprecatedConcepts::supportsCategories()) {
                $event->config[LegacyProjectConfig::PATH_CATEGORY_GROUPS] = $this->_getCategoryGroupData();
            }
            if (DeprecatedConcepts::supportsGlobalSets()) {
                $event->config[LegacyProjectConfig::PATH_GLOBAL_SETS] = $this->_getGlobalSetData();
            }
            if (DeprecatedConcepts::supportsTags()) {
                $event->config[LegacyProjectConfig::PATH_TAG_GROUPS] = $this->_getTagGroupData();
            }
        });
    }

    /**
     * Return category group data config array.
     */
    private function _getCategoryGroupData(): array
    {
        return collect(Craft::$app->getCategories()->getAllGroups())
            ->mapWithKeys(fn(CategoryGroup $group) => [$group->uid => $group->getConfig()])
            ->all();
    }

    /**
     * Return tag group data config array.
     */
    private function _getTagGroupData(): array
    {
        return collect(Craft::$app->getTags()->getAllTagGroups())
            ->mapWithKeys(fn(TagGroup $group) => [$group->uid => $group->getConfig()])
            ->all();
    }

    /**
     * Return global set data config array.
     */
    private function _getGlobalSetData(): array
    {
        return collect(Craft::$app->getGlobals()->getAllSets())
            ->mapWithKeys(fn(GlobalSet $globalSet) => [$globalSet->uid => $globalSet->getConfig()])
            ->all();
    }

    public static function bootYiiEvents(): void
    {
        YiiEvent::on(
            Elements::class,
            Elements::EVENT_REGISTER_ELEMENT_TYPES,
            function(RegisterComponentTypesEvent $event) {
                if (DeprecatedConcepts::supportsCategories()) {
                    $event->types[] = Category::class;
                }
                if (DeprecatedConcepts::supportsGlobalSets()) {
                    $event->types[] = GlobalSet::class;
                }
                if (DeprecatedConcepts::supportsTags()) {
                    $event->types[] = Tag::class;
                }
            },
        );

        YiiEvent::on(
            ArgumentManager::class,
            ArgumentManager::EVENT_DEFINE_GQL_ARGUMENT_HANDLERS,
            function(RegisterGqlArgumentHandlersEvent $event) {
                if (DeprecatedConcepts::supportsCategories()) {
                    $event->handlers['relatedToCategories'] = RelatedCategories::class;
                }
                if (DeprecatedConcepts::supportsTags()) {
                    $event->handlers['relatedToTags'] = RelatedTags::class;
                }
            },
        );

        YiiEvent::on(
            /** @phpstan-ignore-next-line */
            ElementArguments::class,
            ElementArguments::EVENT_DEFINE_ARGUMENTS,
            function(DefineGqlArgumentsEvent $event) {
                if (DeprecatedConcepts::supportsCategories()) {
                    $event->arguments['relatedToCategories'] = [
                        'name' => 'relatedToCategories',
                        // don't lazy load the type (see https://github.com/craftcms/cms/issues/17858)
                        'type' => Type::listOf(CategoryRelation::getType()),
                        'description' => 'Narrows the query results to elements that relate to a category list defined with this argument.',
                    ];
                }
                if (DeprecatedConcepts::supportsTags()) {
                    $event->arguments['relatedToTags'] = [
                        'name' => 'relatedToTags',
                        // don't lazy load the type (see https://github.com/craftcms/cms/issues/17858)
                        'type' => Type::listOf(TagRelation::getType()),
                        'description' => 'Narrows the query results to elements that relate to a tag list defined with this argument.',
                    ];
                }
            },
        );

        YiiEvent::on(
            Gql::class,
            Gql::EVENT_REGISTER_GQL_SCHEMA_COMPONENTS,
            function(RegisterGqlSchemaComponentsEvent $event) {
                if (DeprecatedConcepts::supportsCategories()) {
                    $label = t('Categories');
                    [$event->queries[$label], $event->mutations[$label]] = self::categorySchemaComponents();
                }

                if (DeprecatedConcepts::supportsGlobalSets()) {
                    $label = t('Global Sets', category: 'yii2-adapter');
                    [$event->queries[$label], $event->mutations[$label]] = self::globalSetSchemaComponents();
                }

                if (DeprecatedConcepts::supportsTags()) {
                    $label = t('Tags', category: 'yii2-adapter');
                    [$event->queries[$label], $event->mutations[$label]] = self::tagSchemaComponents();
                }
            },
        );

        YiiEvent::on(
            Gql::class,
            Gql::EVENT_REGISTER_GQL_QUERIES,
            function(RegisterGqlQueriesEvent $event) {
                if (DeprecatedConcepts::supportsCategories()) {
                    array_push($event->queries, ...CategoryQuery::getQueries());
                }
                if (DeprecatedConcepts::supportsGlobalSets()) {
                    array_push($event->queries, ...GlobalSetQuery::getQueries());
                }
                if (DeprecatedConcepts::supportsTags()) {
                    array_push($event->queries, ...TagQuery::getQueries());
                }
            },
        );

        YiiEvent::on(
            Gql::class,
            Gql::EVENT_REGISTER_GQL_MUTATIONS,
            function(RegisterGqlMutationsEvent $event) {
                if (DeprecatedConcepts::supportsCategories()) {
                    array_push($event->mutations, ...CategoryMutation::getMutations());
                }
                if (DeprecatedConcepts::supportsGlobalSets()) {
                    array_push($event->mutations, ...GlobalSetMutation::getMutations());
                }
                if (DeprecatedConcepts::supportsTags()) {
                    array_push($event->mutations, ...TagMutation::getMutations());
                }
            },
        );

        YiiEvent::on(
            Gql::class,
            Gql::EVENT_REGISTER_GQL_TYPES,
            function(RegisterGqlTypesEvent $event) {
                if (DeprecatedConcepts::supportsCategories()) {
                    $event->types[] = CategoryInterface::class;
                }
                if (DeprecatedConcepts::supportsGlobalSets()) {
                    $event->types[] = GlobalSetInterface::class;
                }
                if (DeprecatedConcepts::supportsTags()) {
                    $event->types[] = TagInterface::class;
                }
            },
        );

        YiiEvent::on(
            ElementQueryConditionBuilder::class,
            ElementQueryConditionBuilder::EVENT_REGISTER_GQL_EAGERLOADABLE_FIELDS,
            function(RegisterGqlEagerLoadableFields $event) {
                if (DeprecatedConcepts::supportsCategories()) {
                    $event->fieldList[ElementQueryConditionBuilder::LOCALIZED_NODENAME][] = CategoriesField::class;
                }
            },
        );

        YiiEvent::on(
            UserPermissions::class,
            UserPermissions::EVENT_REGISTER_PERMISSIONS,
            function(RegisterUserPermissionsEvent $event) {
                if (DeprecatedConcepts::supportsCategories()) {
                    self::categoryPermissions($event->permissions);
                }
                if (DeprecatedConcepts::supportsGlobalSets()) {
                    self::globalSetPermissions($event->permissions);
                }
            },
        );

        YiiEvent::on(
            CpVariable::class,
            CpVariable::EVENT_REGISTER_CP_NAV_ITEMS,
            function(RegisterCpNavItemsEvent $event) {
                $newItems = [];

                if (
                    DeprecatedConcepts::supportsGlobalSets() &&
                    !empty(Craft::$app->getGlobals()->getEditableSets())
                ) {
                    $newItems[] = [
                        'label' => t('Globals', category: 'yii2-adapter'),
                        'url' => 'globals',
                        'icon' => 'globe',
                    ];
                }
                if (
                    DeprecatedConcepts::supportsCategories() &&
                    Craft::$app->getCategories()->getEditableGroupIds()
                ) {
                    $newItems[] = [
                        'label' => t('Categories'),
                        'url' => 'categories',
                        'icon' => 'sitemap',
                    ];
                }

                if (!empty($newItems)) {
                    // Find the last item with a "content/" URL
                    $lastContentKey = array_find_key($event->navItems, fn(array $item, int $key) => (
                        str_starts_with($item['url'], 'content/') &&
                        (!isset($event->navItems[$key + 1]) || !str_starts_with($event->navItems[$key + 1]['url'], 'content/'))
                    ));

                    if ($lastContentKey !== null) {
                        array_splice($event->navItems, $lastContentKey + 1, 0, $newItems);
                    } else {
                        array_push($event->navItems, ...$newItems);
                    }
                }
            },
        );

        YiiEvent::on(
            CpVariable::class,
            Cms::config()->allowAdminChanges ? CpVariable::EVENT_REGISTER_CP_SETTINGS : CpVariable::EVENT_REGISTER_READ_ONLY_CP_SETTINGS,
            function(RegisterCpSettingsEvent $event) {
                $label = t('Content');
                if (DeprecatedConcepts::supportsGlobalSets()) {
                    $event->settings[$label]['globals'] = [
                        'iconMask' => '@craftcms/resources/icons/light/globe.svg',
                        'label' => t('Globals', category: 'yii2-adapter'),
                    ];
                }
                if (DeprecatedConcepts::supportsCategories()) {
                    $event->settings[$label]['categories'] = [
                        'iconMask' => '@craftcms/resources/icons/light/sitemap.svg',
                        'label' => t('Categories'),
                    ];
                }
                if (DeprecatedConcepts::supportsTags()) {
                    $event->settings[$label]['tags'] = [
                        'iconMask' => '@craftcms/resources/icons/light/tags.svg',
                        'label' => t('Tags', category: 'yii2-adapter'),
                    ];
                }
            },
        );

        YiiEvent::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function(RegisterUrlRulesEvent $event) {
                if (DeprecatedConcepts::supportsCategories()) {
                    $event->rules += [
                        'categories' => 'categories/category-index',
                        'categories/<groupHandle:{handle}>' => 'categories/category-index',
                        'categories/<groupHandle:{handle}>/new' => 'categories/create',
                        'categories/<groupHandle:{handle}>/<elementId:\d+><slug:(?:-[^\/]*)?>' => 'elements/edit',
                        'settings/categories' => 'categories/group-index',
                        'settings/categories/new' => 'categories/edit-category-group',
                        'settings/categories/<groupId:\d+>' => 'categories/edit-category-group',
                    ];
                }

                if (DeprecatedConcepts::supportsGlobalSets()) {
                    $event->rules += [
                        'globals' => 'globals',
                        'globals/<globalSetHandle:{handle}>' => 'globals/edit-content',
                        'settings/globals' => 'system-settings/global-set-index',
                        'settings/globals/new' => 'system-settings/edit-global-set',
                        'settings/globals/<globalSetId:\d+>' => 'system-settings/edit-global-set',
                    ];
                }

                if (DeprecatedConcepts::supportsTags()) {
                    $event->rules += [
                        'settings/tags' => 'tags/index',
                        'settings/tags/new' => 'tags/edit-tag-group',
                        'settings/tags/<tagGroupId:\d+>' => 'tags/edit-tag-group',
                    ];
                }
            },
        );

        YiiEvent::on(View::class, View::EVENT_REGISTER_CP_TEMPLATE_ROOTS, function(RegisterTemplateRootsEvent $event) {
            $event->roots['yii2-adapter'] = dirname(__DIR__) . '/resources/templates';
        });

        if (DeprecatedConcepts::supportsCategories()) {
            app(ProjectConfig::class)
                ->onAdd(LegacyProjectConfig::PATH_CATEGORY_GROUPS . '.{uid}', fn($event) => Craft::$app->getCategories()->handleChangedCategoryGroup($event))
                ->onUpdate(LegacyProjectConfig::PATH_CATEGORY_GROUPS . '.{uid}', fn($event) => Craft::$app->getCategories()->handleChangedCategoryGroup($event))
                ->onRemove(LegacyProjectConfig::PATH_CATEGORY_GROUPS . '.{uid}', fn($event) => Craft::$app->getCategories()->handleDeletedCategoryGroup($event));
        }

        if (DeprecatedConcepts::supportsGlobalSets()) {
            app(ProjectConfig::class)
                ->onAdd(LegacyProjectConfig::PATH_GLOBAL_SETS . '.{uid}', fn($event) => Craft::$app->getGlobals()->handleChangedGlobalSet($event))
                ->onUpdate(LegacyProjectConfig::PATH_GLOBAL_SETS . '.{uid}', fn($event) => Craft::$app->getGlobals()->handleChangedGlobalSet($event))
                ->onRemove(LegacyProjectConfig::PATH_GLOBAL_SETS . '.{uid}', fn($event) => Craft::$app->getGlobals()->handleDeletedGlobalSet($event));

            Twig::registerExtension(new GlobalsExtension(), TemplateMode::Site);
        }

        // Legacy `view` global remains available through the adapter layer only.
        Twig::registerExtension(new Extension());

        Event::listen(function(DefineNativeFields $event) {
            switch ($event->fieldLayout->type) {
                case Category::class:
                case Tag::class:
                    $event->fields[] = TitleField::class;
                    break;
            }
        });

        if (DeprecatedConcepts::supportsTags()) {
            app(ProjectConfig::class)
                ->onAdd(LegacyProjectConfig::PATH_TAG_GROUPS . '.{uid}', fn($event) => Craft::$app->getTags()->handleChangedTagGroup($event))
                ->onUpdate(LegacyProjectConfig::PATH_TAG_GROUPS . '.{uid}', fn($event) => Craft::$app->getTags()->handleChangedTagGroup($event))
                ->onRemove(LegacyProjectConfig::PATH_TAG_GROUPS . '.{uid}', fn($event) => Craft::$app->getTags()->handleDeletedTagGroup($event));
        }
    }

    /**
     * Return category group permissions.
     */
    private static function categorySchemaComponents(): array
    {
        $queryComponents = [];
        $mutationComponents = [];

        $categoryGroups = Craft::$app->getCategories()->getAllGroups();

        if (!empty($categoryGroups)) {
            foreach ($categoryGroups as $categoryGroup) {
                $name = t($categoryGroup->name, category: 'site');
                $prefix = "categorygroups.$categoryGroup->uid";
                $queryComponents["$prefix:read"] = [
                    'label' => t('Query for categories in the “{name}” category group', [
                        'name' => $name,
                    ], 'yii2-adapter'),
                ];
                $mutationComponents["$prefix:edit"] = [
                    'label' => t('Edit categories in the “{categoryGroup}” category group', [
                        'categoryGroup' => $name,
                    ], 'yii2-adapter'),
                    'nested' => [
                        "$prefix:save" => [
                            'label' => t('Save categories in the “{categoryGroup}” category group', [
                                'categoryGroup' => $name,
                            ], 'yii2-adapter'),
                        ],
                        "$prefix:delete" => [
                            'label' => t('Delete categories from the “{categoryGroup}” category group', [
                                'categoryGroup' => $name,
                            ], 'yii2-adapter'),
                        ],
                    ],
                ];
            }
        }

        return [$queryComponents, $mutationComponents];
    }

    /**
     * Return global set permissions.
     */
    private static function globalSetSchemaComponents(): array
    {
        $queryComponents = [];
        $mutationComponents = [];

        $globalSets = Craft::$app->getGlobals()->getAllSets();

        if (!empty($globalSets)) {
            foreach ($globalSets as $globalSet) {
                $name = t($globalSet->name, category: 'site');
                $prefix = "globalsets.$globalSet->uid";
                $queryComponents["$prefix:read"] = [
                    'label' => t('Query for the “{name}” global set', [
                        'name' => $name,
                    ], 'yii2-adapter'),
                ];
                $mutationComponents["$prefix:edit"] = [
                    'label' => t('Edit the “{globalSet}” global set.', [
                        'globalSet' => $name,
                    ], 'yii2-adapter'),
                ];
            }
        }

        return [$queryComponents, $mutationComponents];
    }

    /**
     * Return tag group permissions.
     */
    private static function tagSchemaComponents(): array
    {
        $queryComponents = [];
        $mutationComponents = [];

        $tagGroups = Craft::$app->getTags()->getAllTagGroups();

        if (!empty($tagGroups)) {
            foreach ($tagGroups as $tagGroup) {
                $name = t($tagGroup->name, category: 'site');
                $prefix = "taggroups.$tagGroup->uid";
                $queryComponents["$prefix:read"] = [
                    'label' => t('Query for tags in the “{name}” tag group', [
                        'name' => $name,
                    ], 'yii2-adapter'),
                ];
                $mutationComponents["$prefix:edit"] = [
                    'label' => t('Edit tags in the “{tagGroup}” tag group', [
                        'tagGroup' => $name,
                    ], 'yii2-adapter'),
                    'nested' => [
                        "$prefix:save" => [
                            'label' => t('Save tags in the “{tagGroup}” tag group', [
                                'tagGroup' => $name,
                            ], 'yii2-adapter'),
                        ],
                        "$prefix:delete" => [
                            'label' => t('Delete tags from the “{tagGroup}” tag group', [
                                'tagGroup' => $name,
                            ], 'yii2-adapter'),
                        ],
                    ],
                ];
            }
        }

        return [$queryComponents, $mutationComponents];
    }

    private static function categoryPermissions(array &$permissions): void
    {
        $categoryGroups = Craft::$app->getCategories()->getAllGroups();

        if (!$categoryGroups) {
            return;
        }

        $type = Category::pluralLowerDisplayName();

        foreach ($categoryGroups as $group) {
            $permissions[] = [
                'heading' => t('Category Group - {name}', [
                    'name' => t($group->name, category: 'site'),
                ], 'yii2-adapter'),
                'permissions' => [
                    "viewCategories:$group->uid" => [
                        'label' => mb_ucfirst(t('View {type}', ['type' => $type])),
                        'nested' => [
                            "saveCategories:$group->uid" => [
                                'label' => mb_ucfirst(t('Save {type}', ['type' => $type])),
                            ],
                            "deleteCategories:$group->uid" => [
                                'label' => mb_ucfirst(t('Delete {type}', ['type' => $type])),
                            ],
                            "viewPeerCategoryDrafts:$group->uid" => [
                                'label' => mb_ucfirst(t('View other users’ {type}', [
                                    'type' => t('drafts'),
                                ])),
                                'nested' => [
                                    "savePeerCategoryDrafts:$group->uid" => [
                                        'label' => mb_ucfirst(t('Save other users’ {type}', [
                                            'type' => t('drafts'),
                                        ])),
                                    ],
                                    "deletePeerCategoryDrafts:$group->uid" => [
                                        'label' => t('Delete other users’ {type}', [
                                            'type' => t('drafts'),
                                        ]),
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];
        }
    }

    private static function globalSetPermissions(array &$permissions): void
    {
        $globalSets = Craft::$app->getGlobals()->getAllSets();

        if (!$globalSets) {
            return;
        }

        $globalSetPermissions = [];

        foreach ($globalSets as $globalSet) {
            $globalSetPermissions["editGlobalSet:$globalSet->uid"] = [
                'label' => t('Edit “{title}”', [
                    'title' => t($globalSet->name, category: 'site'),
                ]),
            ];
        }

        $permissions[] = [
            'heading' => t('Global Sets', category: 'yii2-adapter'),
            'permissions' => $globalSetPermissions,
        ];
    }
}
