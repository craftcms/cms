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
use craft\helpers\ArrayHelper;
use craft\helpers\StringHelper;
use craft\models\CategoryGroup_SiteSettings;
use craft\models\FieldLayout;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use craft\models\Site;
use yii\base\InvalidConfigException;
use yii\console\ExitCode;
use yii\helpers\Console;

/**
 * Manages sections.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4.0
 */
class SectionsController extends Controller
{
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

        $projectConfigService = Craft::$app->getProjectConfig();
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
        if (!$this->interactive) {
            $this->stderr("This command must be run interactively.\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $section = new Section([
            // Avoid the default preview target
            'previewTargets' => [],
        ]);

        $validateAttribute = function($attributes, ?string &$error = null): bool {
            $section = new Section($attributes);
            $attributeNames = array_keys($attributes);
            if (!$section->validate($attributeNames)) {
                $error = $section->getFirstError($attributeNames[0]);
                return false;
            }
            return true;
        };

        $getDefaultAttribute = function(string $attribute, string $value) use ($validateAttribute): ?string {
            if ($validateAttribute([$attribute => $value])) {
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
            $section->type = Section::TYPE_STRUCTURE;
            $section->setSiteSettings(array_map(
                fn(CategoryGroup_SiteSettings $siteSettings) => new Section_SiteSettings([
                    'siteId' => $siteSettings->siteId,
                    'hasUrls' => $siteSettings->hasUrls,
                    'uriFormat' => $siteSettings->uriFormat,
                    'template' => $siteSettings->template,
                ]),
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
            $section->type = Section::TYPE_CHANNEL;
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
            $section->type = Section::TYPE_SINGLE;
            $sourceFieldLayout = $globalSet->getFieldLayout();
            $saveEntryType = true;
        }

        $section->name = $this->prompt('Section name:', [
            'required' => true,
            'validator' => fn(string $name, ?string & $error = null) => $validateAttribute(compact('name'), $error),
            'default' => $section->name,
        ]);

        $section->handle = $this->prompt('Section handle:', [
            'required' => true,
            'validator' => fn(string $handle, ?string & $error = null) => $validateAttribute(compact('handle'), $error),
            'default' => $section->handle ?? StringHelper::toHandle($section->name),
        ]);

        if (!$section->type) {
            $section->type = $this->select('Section type:', [
                Section::TYPE_SINGLE => 'for one-off content',
                Section::TYPE_CHANNEL => 'for repeating content',
                Section::TYPE_STRUCTURE => 'for ordered/hierarchical content',
            ]);
        }

        $section->enableVersioning = $this->confirm('Enable entry versioning for the section?', true);

        if (empty($section->getSiteSettings())) {
            $section->setSiteSettings(array_map(
                fn(Site $site) => new Section_SiteSettings([
                    'siteId' => $site->id,
                ]),
                Craft::$app->getSites()->getAllSites(true),
            ));
        }

        $hasUrls = ArrayHelper::contains($section->getSiteSettings(), fn(Section_SiteSettings $siteSettings) => $siteSettings->hasUrls);
        if ($hasUrls) {
            $section->previewTargets = [
                [
                    'label' => Craft::t('app', 'Primary {type} page', [
                        'type' => StringHelper::toLowerCase(Entry::displayName()),
                    ]),
                    'urlFormat' => '{url}',
                ],
            ];
        }

        $this->do('Saving the section', function() use ($section) {
            if (!Craft::$app->getSections()->saveSection($section)) {
                $message = ArrayHelper::firstValue($section->getFirstErrors()) ?? 'Unable to save the section';
                throw new InvalidConfigException($message);
            }
        });

        $entryType = $section->getEntryTypes()[0];
        $entryTypeName = $this->prompt('Initial entry type name:', [
            'default' => $entryType->name,
        ]);
        $entryTypeHandle = $this->prompt('Initial entry type handle:', [
            'default' => $entryTypeName !== $entryType->name ? StringHelper::toHandle($entryTypeName) : $entryType->handle,
        ]);

        if ($entryTypeName !== $entryType->name || $entryTypeHandle !== $entryType->handle) {
            $entryType->name = $entryTypeName;
            $entryType->handle = $entryTypeHandle;
            $saveEntryType = true;
        }

        if ($saveEntryType) {
            $this->do('Saving the entry type', function() use ($entryType, $sourceFieldLayout) {
                if ($sourceFieldLayout) {
                    $fieldLayout = FieldLayout::createFromConfig($sourceFieldLayout->getConfig());
                    foreach ($fieldLayout->getTabs() as $tab) {
                        $tab->uid = StringHelper::UUID();
                        foreach ($tab->getElements() as $element) {
                            $element->uid = StringHelper::UUID();
                        }
                    }
                    $entryType->setFieldLayout($fieldLayout);
                }

                Craft::$app->getSections()->saveEntryType($entryType);
            });
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
        $sectionsService = Craft::$app->getSections();
        $section = $sectionsService->getSectionByHandle($handle);

        if (!$section) {
            $this->stderr("Invalid section handle: $handle\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        if ($this->interactive && !$this->confirm("Are you sure you want to delete the section “{$section->name}”?")) {
            $this->stdout("Aborted.\n");
            return ExitCode::OK;
        }

        $this->do('Deleting section', function() use ($sectionsService, $section) {
            if (!$sectionsService->deleteSection($section)) {
                $message = ArrayHelper::firstValue($section->getFirstErrors()) ?? 'Unable to delete the section.';
                throw new InvalidConfigException($message);
            }
        });

        $this->success('Section deleted.');
        return ExitCode::OK;
    }
}
