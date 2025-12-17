<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use Craft;
use craft\base\Event;
use craft\console\Controller;
use craft\elements\Category;
use craft\elements\GlobalSet;
use craft\elements\Tag;
use craft\events\SectionEvent;
use craft\fields\Categories;
use craft\fields\Tags;
use craft\helpers\Db;
use craft\models\CategoryGroup;
use craft\models\TagGroup;
use craft\services\Entries as EntriesService;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\BaseRelationField;
use CraftCms\Cms\Field\Entries;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Structure\Enums\Mode;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Structures;
use CraftCms\Cms\Support\Facades\Users;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB as DbFacade;
use Tpetry\QueryExpressions\Language\Alias;
use yii\base\InvalidConfigException;
use yii\console\ExitCode;
use yii\helpers\Console;

/**
 * Converts categories, tags, and global sets to entries.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4.0
 * @deprecated in 6.0.0
 */
class EntrifyController extends Controller
{
    /**
     * @var string|null The section handle that entries should be saved in
     */
    public ?string $section = null;

    /**
     * @var string|null The entry type handle that entries should have
     */
    public ?string $entryType = null;

    /**
     * @var string|null The author username or email that entries should have
     */
    public ?string $author = null;

    private bool $_forSingle;
    private Section $_section;
    private EntryType $_entryType;
    private User $_author;

    /**
     * @inheritdoc
     */
    public function options($actionID): array
    {
        $options = parent::options($actionID);
        $options[] = 'section';

        if (in_array($actionID, ['categories', 'tags'])) {
            $options[] = 'entryType';
            $options[] = 'author';
        }

        return $options;
    }

    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        $this->_forSingle = $action->id === 'global-set';

        return true;
    }

    /**
     * Converts categories to entries.
     *
     * @param string|null $categoryGroup The category group handle
     * @return int
     */
    public function actionCategories(?string $categoryGroup = null): int
    {
        $categoriesService = Craft::$app->getCategories();

        if ($categoryGroup) {
            $categoryGroupHandle = $categoryGroup;
            $categoryGroup = $categoriesService->getGroupByHandle($categoryGroupHandle, true);

            if (!$categoryGroup) {
                $this->stderr("Invalid category group handle: $categoryGroupHandle\n", Console::FG_RED);
                return ExitCode::UNSPECIFIED_ERROR;
            }
        } else {
            if (!$this->interactive) {
                throw new InvalidConfigException('A category group handle is required when this command is run non-interactively.');
            }

            /** @var Collection<CategoryGroup> $categoryGroups */
            $categoryGroups = Collection::make($categoriesService->getAllGroups())
                ->keyBy(fn(CategoryGroup $group) => $group->handle);

            if (empty($categoryGroups)) {
                $this->output('No category groups exist.', Console::FG_YELLOW);
                return ExitCode::OK;
            }

            $categoryGroupHandle = $this->select(
                'Choose a category group:',
                $categoryGroups->map(fn(CategoryGroup $group) => $group->name)->all(),
            );
            $categoryGroup = $categoryGroups->get($categoryGroupHandle);
        }

        $projectConfigService = app(ProjectConfig::class);
        $projectConfigChanged = false;
        $sectionCreated = false;

        if (!isset($this->section)) {
            // Capture the new section handle
            Event::once(EntriesService::class, EntriesService::EVENT_AFTER_SAVE_SECTION, function(SectionEvent $event) {
                $this->section = $event->section->handle;
            });
            $this->run('sections/create', [
                'fromCategoryGroup' => $categoryGroup->handle,
            ]);

            // Add it to a “Categories” page
            $this->_addSectionToPage('Categories', 'sitemap');

            $projectConfigChanged = true;
            $sectionCreated = true;
        }

        try {
            $section = $this->_section();
            $entryType = $this->_entryType();
            $author = $this->_author();
        } catch (InvalidConfigException $e) {
            $this->stderr($e->getMessage() . PHP_EOL, Console::FG_RED);
            if ($projectConfigChanged) {
                $projectConfigService->saveModifiedConfigData();
            }
            return ExitCode::UNSPECIFIED_ERROR;
        }

        if (
            $section->type === SectionType::Channel &&
            !$this->confirm("The categories’ structure data will be lost because “{$section->name}” is a Channel section. Are you sure you want to continue?\n")
        ) {
            $this->stdout("Aborted.\n");
            return ExitCode::OK;
        }

        $this->stdout(PHP_EOL);

        $categoryQuery = Category::find()
            ->group($categoryGroup)
            ->status(null);

        if ($categoryGroup->dateDeleted) {
            $categoryQuery
                ->trashed()
                ->andWhere(['categories.deletedWithGroup' => true]);
        }

        $entriesByLevel = [];

        foreach (Db::batch($categoryQuery) as $categories) {
            $authorData = [];

            foreach ($categories as $category) {
                /** @var Category $category */
                $this->do("Converting “{$category->title}” ($category->id)", function() use (
                    $section,
                    $entryType,
                    $author,
                    &$entriesByLevel,
                    $categoryGroup,
                    $category,
                    &$authorData,
                ) {
                    DbFacade::table(Table::ENTRIES)
                        ->insert([
                            'id' => $category->id,
                            'sectionId' => $section->id,
                            'typeId' => $entryType->id,
                            'postDate' => $category->dateCreated,
                            'dateCreated' => $category->dateCreated,
                            'dateUpdated' => $category->dateUpdated,
                        ]);

                    DbFacade::table(Table::ELEMENTS)
                        ->where('id', $category->id)
                        ->update([
                            'type' => Entry::class,
                            'dateDeleted' => null,
                        ]);

                    DbFacade::table('categories')
                        ->delete($category->id);

                    DbFacade::table(Table::STRUCTUREELEMENTS)
                        ->where('structureId', $categoryGroup->structureId)
                        ->where('elementId', $category->id)
                        ->delete();

                    if ($section->type === SectionType::Structure) {
                        $entry = Entry::find()
                            ->id($category->id)
                            ->drafts(null)
                            ->revisions(null)
                            ->status(null)
                            ->one();
                        $parentLevel = $category->level - 1;
                        $parentEntry = null;
                        while ($parentLevel >= 1) {
                            if (isset($entriesByLevel[$parentLevel])) {
                                $parentEntry = $entriesByLevel[$parentLevel];
                                break;
                            }
                            $parentLevel--;
                        }
                        if ($parentEntry) {
                            Structures::append($section->structureId, $entry, $parentEntry, Mode::Insert);
                        } else {
                            Structures::appendToRoot($section->structureId, $entry, Mode::Insert);
                        }
                        $entriesByLevel[$entry->level] = $entry;
                    }

                    $authorData[] = [
                        'entryId' => $category->id,
                        'authorId' => $author->id,
                        'sortOrder' => 1,
                    ];
                });
            }

            DbFacade::table(Table::ENTRIES_AUTHORS)
                ->insert($authorData);
        }

        $this->success('Categories converted.');

        $this->_updateUserPermissions([
            "viewCategories:$categoryGroup->uid" => [
                "viewEntries:$section->uid",
                "viewPeerEntries:$section->uid",
            ],
            "saveCategories:$categoryGroup->uid" => [
                "createEntries:$section->uid",
                "saveEntries:$section->uid",
                "savePeerEntries:$section->uid",
            ],
            "deleteCategories:$categoryGroup->uid" => [
                "deleteEntries:$section->uid",
                "deletePeerEntries:$section->uid",
            ],
            "viewPeerCategoryDrafts:$categoryGroup->uid" => "viewPeerEntryDrafts:$section->uid",
            "savePeerCategoryDrafts:$categoryGroup->uid" => "savePeerEntryDrafts:$section->uid",
            "deletePeerCategoryDrafts:$categoryGroup->uid" => "deletePeerEntryDrafts:$section->uid",
        ], $sectionCreated);

        if (!$projectConfigService->readOnly) {
            if (!$categoryGroup->dateDeleted && $this->confirm("Delete the “{$categoryGroup}” category group?", true)) {
                $this->do('Deleting category group', function() use ($categoryGroup) {
                    Craft::$app->getCategories()->deleteGroup($categoryGroup);
                });
                $this->success('Category group deleted.');
                $projectConfigChanged = true;
            }

            $fields = $this->_findInProjectConfig($projectConfigService, fn(array $config) => (
                ($config['type'] ?? null) === Categories::class &&
                ($config['settings']['source'] ?? null) === "group:$categoryGroup->uid"
            ));
            if (!empty($fields)) {
                $total = count($fields);
                $this->stdout(sprintf("Found %s relating to the “{$categoryGroup->name}” category group.\n", $total === 1 ? 'one Categories field' : "$total Categories fields"));
                if ($this->confirm($total === 1 ? 'Convert it to an Entries field?' : 'Convert them to Entries fields?', true)) {
                    foreach ($fields as [$path, $config]) {
                        $this->do(sprintf('Converting %s', ($config['name'] ?? null) ? "“{$config['name']}”" : 'Categories filed'), function() use ($section, $projectConfigService, $path, $config) {
                            $config['type'] = Entries::class;
                            $config['settings']['maintainHierarchy'] ??= true;
                            $config['settings']['sources'] = ["section:$section->uid"];
                            unset(
                                $config['settings']['source'],
                                $config['settings']['allowMultipleSources'],
                                $config['settings']['allowLimit'],
                                $config['settings']['allowLargeThumbsView'],
                            );
                            $projectConfigService->set($path, $config);
                        });
                    }

                    $this->success(sprintf('Categories %s converted.', $total === 1 ? 'field' : 'fields'));
                    $projectConfigChanged = true;
                }
            }
        }

        if ($projectConfigChanged) {
            $this->_deployTip('categories', $categoryGroup->handle);
        }

        return ExitCode::OK;
    }

    /**
     * Converts tags to entries.
     *
     * @param string|null $tagGroup The tag group handle
     * @return int
     */
    public function actionTags(?string $tagGroup = null): int
    {
        $tagsService = Craft::$app->getTags();

        if ($tagGroup) {
            $tagGroupHandle = $tagGroup;

            $tagGroup = $tagsService->getTagGroupByHandle($tagGroupHandle, true);
            if (!$tagGroup) {
                $this->stderr("Invalid tag group handle: $tagGroupHandle\n", Console::FG_RED);
                return ExitCode::UNSPECIFIED_ERROR;
            }
        } else {
            if (!$this->interactive) {
                throw new InvalidConfigException('A tag group handle is required when this command is run non-interactively.');
            }

            /** @var Collection<TagGroup> $tagGroups */
            $tagGroups = Collection::make($tagsService->getAllTagGroups())
                ->keyBy(fn(TagGroup $group) => $group->handle);

            if (empty($tagGroups)) {
                $this->output('No tag groups exist.', Console::FG_YELLOW);
                return ExitCode::OK;
            }

            $tagGroupHandle = $this->select(
                'Choose a tag group:',
                $tagGroups->map(fn(TagGroup $group) => $group->name)->all(),
            );
            $tagGroup = $tagGroups->get($tagGroupHandle);
        }

        $projectConfigService = app(ProjectConfig::class);
        $projectConfigChanged = false;

        if (!isset($this->section)) {
            // Capture the new section handle
            Event::once(EntriesService::class, EntriesService::EVENT_AFTER_SAVE_SECTION, function(SectionEvent $event) {
                $this->section = $event->section->handle;
            });
            $this->run('sections/create', [
                'fromTagGroup' => $tagGroup->handle,
            ]);

            // Add it to a “Tags” page
            $this->_addSectionToPage('Tags', 'tags');

            $projectConfigChanged = true;
        }

        try {
            $section = $this->_section();
            $entryType = $this->_entryType();
            $author = $this->_author();
        } catch (InvalidConfigException $e) {
            $this->stderr($e->getMessage() . PHP_EOL, Console::FG_RED);
            if ($projectConfigChanged) {
                $projectConfigService->saveModifiedConfigData();
            }
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $tagQuery = Tag::find()
            ->group($tagGroup)
            ->status(null);

        if ($tagGroup->dateDeleted) {
            $tagQuery
                ->trashed()
                ->andWhere(['tags.deletedWithGroup' => true]);
        }

        if ($tagGroup->dateDeleted) {
            $tagQuery
                ->trashed()
                ->andWhere(['tags.deletedWithGroup' => true]);
        }

        foreach (Db::batch($tagQuery) as $tags) {
            $authorData = [];

            foreach ($tags as $tag) {
                /** @var Tag $tag */
                $this->do("Converting “{$tag->title}” ($tag->id)", function() use (
                    $section,
                    $entryType,
                    $author,
                    $tag,
                    &$authorData
                ) {
                    DbFacade::table(Table::ENTRIES)
                        ->insert([
                            'id' => $tag->id,
                            'sectionId' => $section->id,
                            'typeId' => $entryType->id,
                            'postDate' => $tag->dateCreated,
                            'dateCreated' => $tag->dateCreated,
                            'dateUpdated' => $tag->dateUpdated,
                        ]);

                    DbFacade::table(Table::ELEMENTS)
                        ->where('id', $tag->id)
                        ->update([
                            'type' => Entry::class,
                            'dateDeleted' => null,
                        ]);

                    DbFacade::table('tags')->delete($tag->id);

                    $authorData[] = [
                        'entryId' => $tag->id,
                        'authorId' => $author->id,
                        'sortOrder' => 1,
                    ];
                });
            }

            DbFacade::table(Table::ENTRIES_AUTHORS)
                ->insert($authorData);
        }

        $this->success('Tags converted.');

        if (!$projectConfigService->readOnly) {
            if (!$tagGroup->dateDeleted && $this->confirm("Delete the “{$tagGroup}” tag group?", true)) {
                $this->do('Deleting tag group', function() use ($tagGroup) {
                    Craft::$app->getTags()->deleteTagGroup($tagGroup);
                });
                $this->success('Tag group deleted.');
                $projectConfigChanged = true;
            }

            $fields = $this->_findInProjectConfig($projectConfigService, fn(array $config) => (
                ($config['type'] ?? null) === Tags::class &&
                ($config['settings']['source'] ?? null) === "taggroup:$tagGroup->uid"
            ));
            if (!empty($fields)) {
                $total = count($fields);
                $this->stdout(sprintf("Found %s relating to the “{$tagGroup->name}” tag group.\n", $total === 1 ? 'one Tags field' : "$total Tags fields"));
                if ($this->confirm($total === 1 ? 'Convert it to an Entries field?' : 'Convert them to Entries fields?', true)) {
                    foreach ($fields as [$path, $config]) {
                        $this->do(sprintf('Converting %s', ($config['name'] ?? null) ? "“{$config['name']}”" : 'Tags filed'), function() use ($section, $projectConfigService, $path, $config) {
                            $config['type'] = Entries::class;
                            $config['settings']['sources'] = ["section:$section->uid"];
                            $config['settings']['viewMode'] = BaseRelationField::VIEW_MODE_LIST_INLINE;
                            unset(
                                $config['settings']['source'],
                                $config['settings']['allowMultipleSources'],
                                $config['settings']['allowLimit'],
                                $config['settings']['allowLargeThumbsView'],
                            );
                            $projectConfigService->set($path, $config);
                        });
                    }

                    $this->success(sprintf('Tags %s converted.', $total === 1 ? 'field' : 'fields'));
                    $projectConfigChanged = true;
                }
            }
        }

        if ($projectConfigChanged) {
            $this->_deployTip('tags', $tagGroup->handle);
        }

        return ExitCode::OK;
    }

    /**
     * Converts a global set to a Single section.
     *
     * @param string|null $globalSet The global set handle
     * @return int
     */
    public function actionGlobalSet(?string $globalSet = null): int
    {
        $globalsService = Craft::$app->getGlobals();

        if ($globalSet) {
            $globalSetHandle = $globalSet;

            $globalSet = Craft::$app->getGlobals()->getSetByHandle($globalSetHandle, withTrashed: true);
            if (!$globalSet) {
                $this->stderr("Invalid global set handle: $globalSetHandle\n", Console::FG_RED);
                return ExitCode::UNSPECIFIED_ERROR;
            }
        } else {
            if (!$this->interactive) {
                throw new InvalidConfigException('A global set handle is required when this command is run non-interactively.');
            }

            /** @var Collection<GlobalSet> $globalSets */
            $globalSets = Collection::make($globalsService->getAllSets())
                ->keyBy(fn(GlobalSet $globalSet) => $globalSet->handle);

            if (empty($globalSets)) {
                $this->output('No global sets exist.', Console::FG_YELLOW);
                return ExitCode::OK;
            }

            $globalSetHandle = $this->select(
                'Choose a global set:',
                $globalSets->map(fn(GlobalSet $globalSet) => $globalSet->name)->all(),
            );
            $globalSet = $globalSets->get($globalSetHandle);
        }

        $projectConfigChanged = false;
        $sectionCreated = false;

        if (!isset($this->section)) {
            // Capture the new section handle
            Event::once(EntriesService::class, EntriesService::EVENT_AFTER_SAVE_SECTION, function(SectionEvent $event) {
                $this->section = $event->section->handle;
            });
            $this->run('sections/create', [
                'fromGlobalSet' => $globalSet->handle,
            ]);
            $projectConfigChanged = true;
            $sectionCreated = true;
        }

        try {
            $section = $this->_section();
            $entryType = $this->_entryType();
        } catch (InvalidConfigException $e) {
            $this->stderr($e->getMessage() . PHP_EOL, Console::FG_RED);
            if ($projectConfigChanged) {
                app(ProjectConfig::class)->saveModifiedConfigData();
            }
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->do("Converting “{$globalSet->name}”", function() use (
            $section,
            $entryType,
            $globalSet,
            &$projectConfigChanged,
        ) {
            if (!$globalSet->dateDeleted) {
                Craft::$app->getGlobals()->deleteSet($globalSet);
                $projectConfigChanged = true;
            }

            $oldEntry = Entry::find()
                ->section($section)
                ->status(null)
                ->site('*')
                ->unique()
                ->one();

            if ($oldEntry) {
                Craft::$app->getElements()->deleteElement($oldEntry, true);
            }

            DbFacade::table(Table::ENTRIES)
                ->insert([
                    'id' => $globalSet->id,
                    'sectionId' => $section->id,
                    'typeId' => $entryType->id,
                    'postDate' => $globalSet->dateCreated,
                    'dateCreated' => $globalSet->dateCreated,
                    'dateUpdated' => $globalSet->dateUpdated,
                ]);

            DbFacade::table(Table::ELEMENTS)
                ->where('id', $globalSet->id)
                ->update([
                    'type' => Entry::class,
                    'dateDeleted' => null,
                ]);

            DbFacade::table(Table::ELEMENTS_SITES)
                ->where('elementId', $globalSet->id)
                ->update([
                    'title' => $globalSet->name,
                ]);

            DbFacade::table('globalsets')->delete($globalSet->id);
        });

        $this->success('Global set converted.');

        $this->_updateUserPermissions([
            "editGlobalSet:$globalSet->uid" => [
                "viewEntries:$section->uid",
                "saveEntries:$section->uid",
                "viewPeerEntryDrafts:$section->uid",
                "savePeerEntryDrafts:$section->uid",
                "deletePeerEntryDrafts:$section->uid",
            ],
        ], $sectionCreated);

        if ($projectConfigChanged) {
            $this->_deployTip('global-set', $globalSet->handle);
        }

        return ExitCode::OK;
    }

    private function _section(): Section
    {
        if (!isset($this->_section)) {
            if (!$this->section) {
                throw new InvalidConfigException('The --section option is required when this command is run non-interactively.');
            }

            $section = Sections::getSectionByHandle($this->section);
            if (!$section) {
                throw new InvalidConfigException("Invalid section handle: $this->section");
            }
            if ($this->_forSingle) {
                if ($section->type !== SectionType::Single) {
                    throw new InvalidConfigException("“{$section->name}” isn’t a Single section. You must specify a Single section.", Console::FG_RED);
                }
            } elseif ($section->type === SectionType::Single) {
                throw new InvalidConfigException("“{$section->name}” is a Single section. You must specify a Structure or Channel section.", Console::FG_RED);
            }
            $this->_section = $section;
        }

        return $this->_section;
    }

    private function _entryType(): EntryType
    {
        if (!isset($this->_entryType)) {
            $section = $this->_section();
            $allEntryTypes = Arr::keyBy($section->getEntryTypes(), 'handle');
            if (isset($this->entryType)) {
                if (!isset($allEntryTypes[$this->entryType])) {
                    throw new InvalidConfigException("Invalid entry type handle for the section “{$section->name}”: $this->entryType");
                }
                $this->_entryType = $allEntryTypes[$this->entryType];
            } elseif (count($allEntryTypes) === 1) {
                $this->_entryType = reset($allEntryTypes);
            } else {
                if (!$this->interactive) {
                    throw new InvalidConfigException('The --entry-type option is required when this command is run non-interactively.');
                }
                $entryTypeHandle = $this->select('Which entry type should the entries have?', array_map(
                    fn(EntryType $entryType) => $entryType->name,
                    $allEntryTypes,
                ));
                $this->_entryType = $allEntryTypes[$entryTypeHandle];
            }
        }

        return $this->_entryType;
    }

    private function _author(): User
    {
        if (!isset($this->_author)) {
            if (isset($this->author)) {
                $author = Users::getUserByUsernameOrEmail($this->author);
                if (!$author) {
                    throw new InvalidConfigException("Invalid author username or email: $this->author");
                }
                $this->_author = $author;
            } else {
                if (!$this->interactive) {
                    throw new InvalidConfigException('The --author option is required when this command is run non-interactively.');
                }
                $what = Cms::config()->useEmailAsUsername ? 'email' : 'username or email';
                $usernameOrEmail = $this->prompt("Enter the $what of the author that the entries should have:", [
                    'required' => true,
                    'validator' => fn(string $value) => Users::getUserByUsernameOrEmail($value) !== null,
                    'error' => "Invalid $what.",
                ]);
                $this->_author = Users::getUserByUsernameOrEmail($usernameOrEmail);
            }
        }

        return $this->_author;
    }

    private function _updateUserPermissions(array $map, $updateUserGroups): void
    {
        // Normalize the permission map
        $map = array_combine(
            array_map('strtolower', array_keys($map)),
            array_map(fn($newPermissions) => array_map('strtolower', (array)$newPermissions), $map)
        );

        $this->do('Updating user permissions', function() use ($map, $updateUserGroups) {
            foreach ($map as $oldPermission => $newPermissions) {
                $userIds = DbFacade::table(Table::USERPERMISSIONS_USERS, 'upu')
                    ->join(new Alias(Table::USERPERMISSIONS, 'up'), 'up.id', 'upu.permissionId')
                    ->where('up.name', $oldPermission)
                    ->pluck('upu.userId')
                    ->unique();

                if ($userIds->isEmpty()) {
                    continue;
                }

                $insert = [];

                foreach ($newPermissions as $newPermission) {
                    $newPermissionId = DbFacade::table(Table::USERPERMISSIONS)
                        ->where('name', $newPermission)
                        ->value('id');

                    if (!$newPermissionId) {
                        $newPermissionId = DbFacade::table(Table::USERPERMISSIONS)
                            ->insertGetId([
                                'name' => $newPermission,
                            ]);
                    }

                    foreach ($userIds as $userId) {
                        $insert[] = [
                            'permissionId' => $newPermissionId,
                            'userId' => $userId,
                        ];
                    }
                }

                DbFacade::table(Table::USERPERMISSIONS_USERS)
                    ->insert($insert);
            }

            if ($updateUserGroups) {
                $projectConfig = app(ProjectConfig::class);

                foreach ($projectConfig->get('users.groups') ?? [] as $uid => $group) {
                    $groupPermissions = array_flip($group['permissions'] ?? []);
                    $changed = false;

                    foreach ($map as $oldPermission => $newPermissions) {
                        if (isset($groupPermissions[$oldPermission])) {
                            foreach ($newPermissions as $newPermission) {
                                $groupPermissions[$newPermission] = true;
                            }
                            $changed = true;
                        }
                    }

                    if ($changed) {
                        $projectConfig->set("users.groups.$uid.permissions", array_keys($groupPermissions));
                    }
                }
            }
        });

        $this->stdout(PHP_EOL);
    }

    private function _findInProjectConfig(ProjectConfig $projectConfigService, callable $check): array
    {
        $results = [];
        $this->_findInProjectConfigInternal($projectConfigService->get(), $check, $results, null);
        return $results;
    }

    private function _findInProjectConfigInternal(array $config, callable $check, array &$results, ?string $path): void
    {
        foreach ($config as $key => $value) {
            if (is_array($value)) {
                $subpath = ($path !== null ? "$path." : '') . $key;
                if ($check($value)) {
                    $results[] = [$subpath, $value];
                } else {
                    $this->_findInProjectConfigInternal($value, $check, $results, $subpath);
                }
            }
        }
    }

    private function _deployTip(string $action, string $handle): void
    {
        $command = "php craft entrify/$action $handle --section={$this->_section->handle}";

        if (!$this->_forSingle) {
            $command .= " --entry-type={$this->_entryType->handle} --author={$this->_author->username}";
        }

        $this->tip(<<<MD
Run this command on other environments immediately after deploying these changes:

```
$command
```
MD);
    }

    private function _addSectionToPage(string $name, string $icon): void
    {
        $projectConfig = Craft::$app->getProjectConfig();

        $sourceConfigPath = sprintf('%s.%s', ProjectConfig::PATH_ELEMENT_SOURCES, Entry::class);
        $sourceConfigs = Collection::make($projectConfig->get($sourceConfigPath))
            ->map(fn(array $config) => $config + ['page' => 'Entries'])
            ->all();
        $sourceConfigs[] = [
            'key' => sprintf('section:%s', $this->_section()->uid),
            'page' => $name,
            'type' => 'native',
        ];
        $projectConfig->set($sourceConfigPath, $sourceConfigs);

        $pageSettings = app(ElementSources::class)->getPageSettings(Entry::class);
        $pageSettings[$name] = [
            'icon' => $icon,
        ];
        $projectConfig->set(sprintf('%s.%s', ProjectConfig::PATH_ELEMENT_SOURCE_PAGES, Entry::class), $pageSettings);
    }
}
