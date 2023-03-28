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
use craft\db\Query;
use craft\db\Table;
use craft\elements\Category;
use craft\elements\Entry;
use craft\elements\Tag;
use craft\elements\User;
use craft\events\SectionEvent;
use craft\fields\Categories;
use craft\fields\Entries;
use craft\fields\Tags;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\models\EntryType;
use craft\models\Section;
use craft\services\ProjectConfig;
use craft\services\Sections;
use craft\services\Structures;
use yii\base\InvalidConfigException;
use yii\console\ExitCode;
use yii\helpers\Console;

/**
 * Converts categories, tags, and global sets to entries.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4.0
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
     * @param string $categoryGroup The category group handle
     * @return int
     */
    public function actionCategories(string $categoryGroup): int
    {
        $categoryGroupHandle = $categoryGroup;

        $categoryGroup = Craft::$app->getCategories()->getGroupByHandle($categoryGroupHandle, true);
        if (!$categoryGroup) {
            $this->stderr("Invalid category group handle: $categoryGroupHandle\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $projectConfigService = Craft::$app->getProjectConfig();
        $projectConfigChanged = false;
        $sectionCreated = false;

        if (
            !isset($this->section) &&
            !$this->confirm("Have you already created a section to replace the “{$categoryGroup->name}” category group?")
        ) {
            $this->stdout("Let’s create one now, then.\n", Console::FG_YELLOW);
            // Capture the new section handle
            Event::once(Sections::class, Sections::EVENT_AFTER_SAVE_SECTION, function(SectionEvent $event) {
                $this->section = $event->section->handle;
            });
            $this->run('sections/create', [
                'fromCategoryGroup' => $categoryGroup->handle,
            ]);
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
            $section->type === Section::TYPE_CHANNEL &&
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

        $structuresService = Craft::$app->getStructures();
        $entriesByLevel = [];

        foreach (Db::each($categoryQuery) as $category) {
            /** @var Category $category */
            $this->do("Converting “{$category->title}” ($category->id)", function() use (
                $section,
                $entryType,
                $author,
                $structuresService,
                &$entriesByLevel,
                $categoryGroup,
                $category
            ) {
                Db::insert(Table::ENTRIES, [
                    'id' => $category->id,
                    'sectionId' => $section->id,
                    'typeId' => $entryType->id,
                    'authorId' => $author->id,
                    'postDate' => Db::prepareDateForDb($category->dateCreated),
                    'dateCreated' => Db::prepareDateForDb($category->dateCreated),
                    'dateUpdated' => Db::prepareDateForDb($category->dateUpdated),
                ]);

                Db::update(Table::ELEMENTS, [
                    'type' => Entry::class,
                    'dateDeleted' => null,
                ], [
                    'id' => $category->id,
                ]);

                Db::delete(Table::CATEGORIES, [
                    'id' => $category->id,
                ]);

                Db::delete(Table::STRUCTUREELEMENTS, [
                    'structureId' => $categoryGroup->structureId,
                    'elementId' => $category->id,
                ]);

                if ($section->type === Section::TYPE_STRUCTURE) {
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
                        $structuresService->append($section->structureId, $entry, $parentEntry, Structures::MODE_INSERT);
                    } else {
                        $structuresService->appendToRoot($section->structureId, $entry, Structures::MODE_INSERT);
                    }
                    $entriesByLevel[$entry->level] = $entry;
                }
            });
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
                            $config['settings']['maintainHierarchy'] = $config['settings']['maintainHierarchy'] ?? true;
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
     * @param string $tagGroup The tag group handle
     * @return int
     */
    public function actionTags(string $tagGroup): int
    {
        $tagGroupHandle = $tagGroup;

        $tagGroup = Craft::$app->getTags()->getTagGroupByHandle($tagGroupHandle, true);
        if (!$tagGroup) {
            $this->stderr("Invalid tag group handle: $tagGroupHandle\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $projectConfigService = Craft::$app->getProjectConfig();
        $projectConfigChanged = false;

        if (
            !isset($this->section) &&
            !$this->confirm("Have you already created a section to replace the “{$tagGroup->name}” tag group?")
        ) {
            $this->stdout("Let’s create one now, then.\n", Console::FG_YELLOW);
            // Capture the new section handle
            Event::once(Sections::class, Sections::EVENT_AFTER_SAVE_SECTION, function(SectionEvent $event) {
                $this->section = $event->section->handle;
            });
            $this->run('sections/create', [
                'fromTagGroup' => $tagGroup->handle,
            ]);
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

        foreach (Db::each($tagQuery) as $tag) {
            /** @var Tag $tag */
            $this->do("Converting “{$tag->title}” ($tag->id)", function() use (
                $section,
                $entryType,
                $author,
                $tag
            ) {
                Db::insert(Table::ENTRIES, [
                    'id' => $tag->id,
                    'sectionId' => $section->id,
                    'typeId' => $entryType->id,
                    'authorId' => $author->id,
                    'postDate' => Db::prepareDateForDb($tag->dateCreated),
                    'dateCreated' => Db::prepareDateForDb($tag->dateCreated),
                    'dateUpdated' => Db::prepareDateForDb($tag->dateUpdated),
                ]);

                Db::update(Table::ELEMENTS, [
                    'type' => Entry::class,
                    'dateDeleted' => null,
                ], [
                    'id' => $tag->id,
                ]);

                Db::delete(Table::TAGS, [
                    'id' => $tag->id,
                ]);
            });
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
     * @param string $globalSet The global set handle
     * @return int
     */
    public function actionGlobalSet(string $globalSet): int
    {
        $globalSetHandle = $globalSet;

        $globalSet = Craft::$app->getGlobals()->getSetByHandle($globalSetHandle, withTrashed: true);
        if (!$globalSet) {
            $this->stderr("Invalid global set handle: $globalSetHandle\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $projectConfigChanged = false;
        $sectionCreated = false;

        if (
            !isset($this->section) &&
            !$this->confirm("Have you already created a section to replace the “{$globalSet->name}” global set")
        ) {
            $this->stdout("Let’s create one now, then.\n", Console::FG_YELLOW);
            // Capture the new section handle
            Event::once(Sections::class, Sections::EVENT_AFTER_SAVE_SECTION, function(SectionEvent $event) {
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
                Craft::$app->getProjectConfig()->saveModifiedConfigData();
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

            Db::insert(Table::ENTRIES, [
                'id' => $globalSet->id,
                'sectionId' => $section->id,
                'typeId' => $entryType->id,
                'postDate' => Db::prepareDateForDb($globalSet->dateCreated),
                'dateCreated' => Db::prepareDateForDb($globalSet->dateCreated),
                'dateUpdated' => Db::prepareDateForDb($globalSet->dateUpdated),
            ]);

            Db::update(Table::ELEMENTS, [
                'type' => Entry::class,
                'dateDeleted' => null,
            ], [
                'id' => $globalSet->id,
            ]);

            Db::update(Table::CONTENT, [
                'title' => $globalSet->name,
            ], [
                'elementId' => $globalSet->id,
            ]);

            Db::delete(Table::GLOBALSETS, [
                'id' => $globalSet->id,
            ]);
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
            if ($this->section) {
                $section = Craft::$app->getSections()->getSectionByHandle($this->section);
                if (!$section) {
                    throw new InvalidConfigException("Invalid section handle: $this->section");
                }
                if ($this->_forSingle) {
                    if ($section->type !== Section::TYPE_SINGLE) {
                        throw new InvalidConfigException("“{$section->name}” isn’t a Single section. You must specify a Single section.", Console::FG_RED);
                    }
                } elseif ($section->type === Section::TYPE_SINGLE) {
                    throw new InvalidConfigException("“{$section->name}” is a Single section. You must specify a Structure or Channel section.", Console::FG_RED);
                }
                $this->_section = $section;
            } else {
                if (!$this->interactive) {
                    throw new InvalidConfigException('The --section option is required when this command is run non-interactively.');
                }
                $allSections = ArrayHelper::index(Craft::$app->getSections()->getAllSections(), 'handle');
                if ($this->_forSingle) {
                    $allSections = array_filter($allSections, fn(Section $section) => $section->type === Section::TYPE_SINGLE);
                } else {
                    $allSections = array_filter($allSections, fn(Section $section) => $section->type !== Section::TYPE_SINGLE);
                }
                if (empty($allSections)) {
                    throw new InvalidConfigException(sprintf('No %s sections exist yet.', $this->_forSingle ? 'Single' : 'Channel/Structure'));
                }
                $sectionHandle = $this->select("Which section should entries be saved to?", array_map(
                    fn(Section $section) => $section->name,
                    $allSections,
                ));
                $this->_section = $allSections[$sectionHandle];
            }
        }

        return $this->_section;
    }

    private function _entryType(): EntryType
    {
        if (!isset($this->_entryType)) {
            $section = $this->_section();
            $allEntryTypes = ArrayHelper::index($section->getEntryTypes(), 'handle');
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
                $author = Craft::$app->getUsers()->getUserByUsernameOrEmail($this->author);
                if (!$author) {
                    throw new InvalidConfigException("Invalid author username or email: $this->author");
                }
                $this->_author = $author;
            } else {
                if (!$this->interactive) {
                    throw new InvalidConfigException('The --author option is required when this command is run non-interactively.');
                }
                $usersService = Craft::$app->getUsers();
                $generalConfig = Craft::$app->getConfig()->getGeneral();
                $what = $generalConfig->useEmailAsUsername ? 'email' : 'username or email';
                $usernameOrEmail = $this->prompt("Enter the $what of the author that the entries should have:", [
                    'required' => true,
                    'validator' => fn(string $value) => $usersService->getUserByUsernameOrEmail($value) !== null,
                    'error' => "Invalid $what.",
                ]);
                $this->_author = $usersService->getUserByUsernameOrEmail($usernameOrEmail);
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
                $userIds = (new Query())
                    ->select(['upu.userId'])
                    ->from(['upu' => Table::USERPERMISSIONS_USERS])
                    ->innerJoin(['up' => Table::USERPERMISSIONS], '[[up.id]] = [[upu.permissionId]]')
                    ->where(['up.name' => $oldPermission])
                    ->column();

                $userIds = array_unique($userIds);

                if (!empty($userIds)) {
                    $insert = [];

                    foreach ($newPermissions as $newPermission) {
                        $newPermissionId = (new Query())
                            ->select('id')
                            ->from(Table::USERPERMISSIONS)
                            ->where(['name' => $newPermission])
                            ->scalar();

                        if (!$newPermissionId) {
                            Db::insert(Table::USERPERMISSIONS, [
                                'name' => $newPermission,
                            ]);
                            $newPermissionId = Craft::$app->getDb()->getLastInsertID(Table::USERPERMISSIONS);
                        }

                        foreach ($userIds as $userId) {
                            $insert[] = [$newPermissionId, $userId];
                        }
                    }

                    Db::batchInsert(Table::USERPERMISSIONS_USERS, ['permissionId', 'userId'], $insert);
                }
            }

            if ($updateUserGroups) {
                $projectConfig = Craft::$app->getProjectConfig();

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
}
