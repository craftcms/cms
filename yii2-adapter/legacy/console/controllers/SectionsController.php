<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use Craft;
use craft\console\Controller;
use craft\elements\Entry;
use craft\models\CategoryGroup_SiteSettings;
use craft\models\EntryType;
use craft\models\FieldLayout;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Section\Data\SectionSiteSettings;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Str;
use Illuminate\Support\Collection;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\console\ExitCode;
use yii\helpers\Console;
use function CraftCms\Cms\t;

/**
 * Manages sections.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4.0
 */
class SectionsController extends Controller
{
    /**
     * @var string|null The section name.
     * @since 4.6.0
     */
    public ?string $name = null;

    /**
     * @var string|null The section handle.
     * @since 4.6.0
     */
    public ?string $handle = null;

    /**
     * @var string|null The section type (single, channel, or structure).
     * @since 4.6.0
     */
    public ?string $type = null;

    /**
     * @var bool|null Whether to disable versioning for the section.
     * @since 4.6.0
     */
    public ?bool $noVersioning = null;

    /**
     * @var string|null Comma-separated list of entry type handles to assign to the section.
     * @since 5.0.0
     */
    public ?string $entryTypes = null;

    /**
     * @var string|null The entry URI format to set for each site.
     * @since 4.6.0
     */
    public ?string $uriFormat = null;

    /**
     * @var string|null The template to load when an entry’s URL is requested.
     * @since 4.6.0
     */
    public ?string $template = null;

    /**
     * @var string|null The category group handle to model the section from.
     */
    public ?string $fromCategoryGroup = null;

    /**
     * @var string|null The tag group handle to model the section from.
     */
    public ?string $fromTagGroup = null;

    /**
     * @var string|null The global set handle to model the section from.
     */
    public ?string $fromGlobalSet = null;

    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        $projectConfigService = app(ProjectConfig::class);
        if ($projectConfigService->readOnly) {
            $this->stdout("Project config changes aren’t allowed on this environment.\n", Console::FG_RED);
            if (!$this->confirm($this->ansiFormat('Continue anyway?', Console::FG_RED))) {
                return false;
            }
            $projectConfigService->readOnly = false;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function options($actionID): array
    {
        $options = parent::options($actionID);
        switch ($actionID) {
            case 'create':
                $options = array_merge($options, [
                    'name',
                    'handle',
                    'type',
                    'noVersioning',
                    'entryTypes',
                    'uriFormat',
                    'template',
                    'fromCategoryGroup',
                    'fromTagGroup',
                    'fromGlobalSet',
                ]);
                break;
        }
        return $options;
    }

    /**
     * Creates a section.
     *
     * @return int
     */
    public function actionCreate(): int
    {
        $section = new \CraftCms\Cms\Section\Data\Section(
            // Avoid the default preview target
            previewTargets: [],
        );

        $validateAttribute = function($attributes, ?string &$error = null, string $class = Section::class): bool {
            $model = new $class($attributes);
            $attributeNames = array_keys($attributes);
            if (!$model->validate($attributeNames)) {
                $error = $model->getFirstError($attributeNames[0]);
                return false;
            }
            return true;
        };

        $getDefaultAttribute = function(string $attribute, string $value, string $class = Section::class) use ($validateAttribute): ?string {
            if ($validateAttribute([$attribute => $value], class: $class)) {
                return $value;
            }
            return null;
        };

        $saveEntryType = false;
        $sourceFieldLayout = null;

        if ($this->fromCategoryGroup) {
            $categoryGroup = Craft::$app->getCategories()->getGroupByHandle($this->fromCategoryGroup);
            if (!$categoryGroup) {
                $this->stderr("Invalid category group handle: $this->fromCategoryGroup\n", Console::FG_RED);
                return ExitCode::UNSPECIFIED_ERROR;
            }
            $section->name = $getDefaultAttribute('name', $categoryGroup->name);
            $section->handle = $getDefaultAttribute('handle', $categoryGroup->handle);
            $section->type = SectionType::Structure;
            $section->setSiteSettings(array_map(
                fn(CategoryGroup_SiteSettings $siteSettings) => new SectionSiteSettings(
                    siteId: $siteSettings->siteId,
                    hasUrls: $siteSettings->hasUrls,
                    uriFormat: $siteSettings->uriFormat,
                    template: $siteSettings->template,
                ),
                $categoryGroup->getSiteSettings(),
            ));
            $section->maxLevels = $categoryGroup->maxLevels;
            $sourceFieldLayout = $categoryGroup->getFieldLayout();
            $saveEntryType = true;
        } elseif ($this->fromTagGroup) {
            $tagGroup = Craft::$app->getTags()->getTagGroupByHandle($this->fromTagGroup);
            if (!$tagGroup) {
                $this->stderr("Invalid tag group handle: $this->fromTagGroup\n", Console::FG_RED);
                return ExitCode::UNSPECIFIED_ERROR;
            }
            $section->name = $getDefaultAttribute('name', $tagGroup->name);
            $section->handle = $getDefaultAttribute('handle', $tagGroup->handle);
            $section->type = SectionType::Channel;
            $sourceFieldLayout = $tagGroup->getFieldLayout();
            $saveEntryType = true;
        } elseif ($this->fromGlobalSet) {
            $globalSet = Craft::$app->getGlobals()->getSetByHandle($this->fromGlobalSet);
            if (!$globalSet) {
                $this->stderr("Invalid global set handle: $this->fromGlobalSet\n", Console::FG_RED);
                return ExitCode::UNSPECIFIED_ERROR;
            }
            $section->name = $getDefaultAttribute('name', $globalSet->name);
            $section->handle = $getDefaultAttribute('handle', $globalSet->handle);
            $section->type = SectionType::Single;
            $sourceFieldLayout = $globalSet->getFieldLayout();
            $saveEntryType = true;
        }

        $section->name = $this->name ?? $this->prompt('Section name:', [
            'required' => true,
            'validator' => fn(string $name, ?string & $error = null) => $validateAttribute(compact('name'), $error),
            'default' => $section->name,
        ]);

        $section->handle = $this->handle ?? $this->prompt('Section handle:', [
            'required' => true,
            'validator' => fn(string $handle, ?string & $error = null) => $validateAttribute(compact('handle'), $error),
            'default' => $section->handle ?? Str::toHandle($section->name),
        ]);

        if (isset($this->type)) {
            $section->type = SectionType::from($this->type);
        } elseif (!$section->type) {
            if ($this->interactive) {
                $section->type = SectionType::from($this->select('Section type:', [
                    SectionType::Single->value => 'for one-off content',
                    SectionType::Channel->value => 'for repeating content',
                    SectionType::Structure->value => 'for ordered/hierarchical content',
                ]));
            } else {
                $section->type = SectionType::Channel;
            }
        }

        if (isset($this->noVersioning)) {
            $section->enableVersioning = !$this->noVersioning;
        } else {
            $section->enableVersioning = $this->confirm('Enable entry versioning for the section?', true);
        }

        if (empty($section->getSiteSettings())) {
            $section->setSiteSettings(Sites::getAllSites(true)->map(
                fn(Site $site) => new SectionSiteSettings(
                    siteId: $site->id,
                    hasUrls: $this->uriFormat !== null,
                    uriFormat: $this->uriFormat,
                    template: $this->template,
                )
            )->all());
        }

        $hasUrls = Collection::make($section->getSiteSettings())->contains(fn(SectionSiteSettings $siteSettings) => $siteSettings->hasUrls);
        if ($hasUrls) {
            $section->previewTargets = [
                [
                    'label' => t('Primary {type} page', [
                        'type' => Entry::lowerDisplayName(),
                    ]),
                    'urlFormat' => '{url}',
                ],
            ];
        }

        $entryTypes = [];
        $entriesService = Craft::$app->getEntries();

        if (isset($this->entryTypes)) {
            foreach (explode(',', $this->entryTypes) as $entryTypeHandle) {
                $entryType = $entriesService->getEntryTypeByHandle($entryTypeHandle);
                if (!$entryType) {
                    throw new InvalidArgumentException("Invalid entry type handle: $entryTypeHandle");
                }
                $entryTypes[] = $entryType;
            }
        } elseif ($this->interactive) {
            /** @var EntryType[] $allEntryTypes */
            $allEntryTypes = Arr::keyBy($entriesService->getAllEntryTypes(), 'handle');
            if (
                !$this->fromCategoryGroup &&
                !$this->fromTagGroup &&
                !$this->fromGlobalSet &&
                !empty($allEntryTypes) &&
                $this->confirm('Have you already created an entry type for this section?')
            ) {
                $entryTypeHandle = $this->select("Which entry type should be used?", array_map(
                    fn(EntryType $entryType) => $entryType->name,
                    $allEntryTypes,
                ));
                $entryType = $allEntryTypes[$entryTypeHandle];
            } else {
                $entryType = new EntryType();
                $entryType->name = $this->prompt('Entry type name:', [
                    'default' => Str::singular($section->name),
                ]);
                $entryType->handle = $this->prompt('Entry type handle:', [
                    'validator' => fn(string $handle, ?string & $error = null) => $validateAttribute(compact('handle'), $error, EntryType::class),
                    'default' => $getDefaultAttribute('handle', Str::toHandle($entryType->name), EntryType::class),
                ]);
                $saveEntryType = true;
            }

            if ($saveEntryType) {
                if ($this->fromGlobalSet) {
                    $entryType->showStatusField = false;
                }

                $this->do('Saving the entry type', function() use ($entryType, $sourceFieldLayout, $entriesService) {
                    if ($sourceFieldLayout) {
                        $fieldLayout = FieldLayout::createFromConfig($sourceFieldLayout->getConfig() ?? []);
                        $entryType->setFieldLayout($fieldLayout);
                    }

                    $entriesService->saveEntryType($entryType);
                });
            }

            $entryTypes[] = $entryType;
        }

        $section->setEntryTypes($entryTypes);

        try {
            $this->do('Saving the section', function() use ($section) {
                if (!Sections::saveSection($section)) {
                    throw new InvalidConfigException('Unable to save the section');
                }
            });
        } catch (InvalidConfigException) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->success('Section created.');
        return ExitCode::OK;
    }

    /**
     * Deletes a section.
     *
     * @param string $handle The section handle
     * @return int
     */
    public function actionDelete(string $handle): int
    {
        $sectionsService = Craft::$app->getEntries();
        $section = Sections::getSectionByHandle($handle);

        if (!$section) {
            $this->stderr("Invalid section handle: $handle\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        if ($this->interactive && !$this->confirm("Are you sure you want to delete the section “{$section->name}”?")) {
            $this->stdout("Aborted.\n");
            return ExitCode::OK;
        }

        $this->do('Deleting section', function() use ($section) {
            if (!Sections::deleteSection($section)) {
                throw new InvalidConfigException('Unable to delete the section.');
            }
        });

        $this->success('Section deleted.');
        return ExitCode::OK;
    }
}
