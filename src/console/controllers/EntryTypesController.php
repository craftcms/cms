<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use Craft;
use craft\base\ElementContainerFieldInterface;
use craft\base\FieldInterface;
use craft\base\FieldLayoutElement;
use craft\console\Controller;
use craft\elements\Entry;
use craft\helpers\Console;
use craft\helpers\FileHelper;
use craft\helpers\StringHelper;
use craft\models\EntryType;
use craft\models\FieldLayoutTab;
use craft\models\Section;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use yii\console\ExitCode;

/**
 * Manages entry types.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.3.0
 */
class EntryTypesController extends Controller
{
    /**
     * Merges two entry types.
     *
     * @param string $handleA
     * @param string $handleB
     * @return int
     */
    public function actionMerge(string $handleA, string $handleB): int
    {
        $entriesService = Craft::$app->getEntries();
        $fieldsService = Craft::$app->getFields();

        $entryTypeA = $entriesService->getEntryTypeByHandle($handleA);
        if (!$entryTypeA) {
            $this->stderr("Invalid entry type handle: $handleA\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }
        $entryTypeB = $entriesService->getEntryTypeByHandle($handleB);
        if (!$entryTypeB) {
            $this->stderr("Invalid entry type handle: $handleB\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $usagesA = $entryTypeA->findUsages();
        $usagesB = $entryTypeB->findUsages();

        if ($this->interactive) {
            $queryA = Entry::find()->typeId($entryTypeA->id)->status(null);
            $queryB = Entry::find()->typeId($entryTypeB->id)->status(null);

            $totalEntriesA = $queryA->count();
            $totalEntriesB = $queryB->count();

            $infoA = sprintf(
                '%s %s, %s %s',
                count($usagesA),
                count($usagesA) === 1 ? 'usage' : 'usages',
                $totalEntriesA,
                $totalEntriesA === 1 ? 'entry' : 'entries',
            );
            $infoB = sprintf(
                '%s %s, %s %s',
                count($usagesB),
                count($usagesB) === 1 ? 'usage' : 'usages',
                $totalEntriesB,
                $totalEntriesB === 1 ? 'entry' : 'entries',
            );

            $this->stdout("\n" . $this->markdownToAnsi(<<<MD
**Which entry type should persist?**

 - `$entryTypeA->handle` ($infoA)
 - `$entryTypeB->handle` ($infoB)
MD) . "\n\n");
            $choice = $this->select('Choose:', [
                $entryTypeA->handle => $entryTypeA->name,
                $entryTypeB->handle => $entryTypeB->name,
            ], $totalEntriesA >= $totalEntriesB ? $entryTypeA->handle : $entryTypeB->handle);
        } else {
            $choice = $entryTypeA->handle;
        }

        /** @var EntryType $persistingEntryType */
        /** @var EntryType $outgoingEntryType */
        /** @var array<Section|ElementContainerFieldInterface> $outgoingUsages */
        [$persistingEntryType, $outgoingEntryType, $outgoingUsages] = $choice === $entryTypeA->handle
            ? [$entryTypeA, $entryTypeB, $usagesB]
            : [$entryTypeB, $entryTypeA, $usagesA];

        unset($entryTypeA, $entryTypeB);

        /** @var Collection<string,FieldInterface> $persistingFields */
        $persistingFields = Collection::make($persistingEntryType->getFieldLayout()->getCustomFields())
            ->keyBy(fn(FieldInterface $field) => $field->handle);

        // Track field instances that need to be added to the layout,
        // including the new field handles we're going to need to assign them.
        // And create a map of field instance UUIDs we'll need to update within JSON data.
        /** @var array<string,true> $handlesIdx */
        $handlesIdx = $persistingFields->map(fn() => true)->all();
        /** @var array<string,string> $handleMap */
        $handleMap = [];
        /** @var FieldInterface[] $addFields */
        $addFields = [];
        /** @var array<string,string> $uidMap */
        $uidMap = [];

        $this->stdout("\n");

        $this->do('Inspecting fields', function() use (
            $outgoingEntryType,
            $persistingFields,
            &$handlesIdx,
            &$handleMap,
            &$addFields,
            &$uidMap,
        ) {
            foreach ($outgoingEntryType->getFieldLayout()->getCustomFields() as $outgoingField) {
                $hasContent = $outgoingField::dbType() !== null;
                // See if the same field already exists with the same handle
                if ($outgoingField->id === $persistingFields->get($outgoingField->handle)?->id) {
                    if ($hasContent) {
                        $uidMap[$outgoingField->layoutElement->uid] = $persistingFields->get($outgoingField->handle)->layoutElement->uid;
                    }
                    continue;
                }

                // If it's a single-instance field, check if the field already exists by a different handle
                if (!$outgoingField::isMultiInstance()) {
                    $persistingField = $persistingFields->first(fn(FieldInterface $field) => $field->id === $outgoingField->id);
                    if ($persistingField) {
                        $handleMap[$outgoingField->handle] = $persistingField->handle;
                        if ($hasContent) {
                            $uidMap[$outgoingField->layoutElement->uid] = $persistingField->layoutElement->uid;
                        }
                        continue;
                    }
                }

                // Otherwise, plan to add the field to the layout, possibly with a new handle
                $addFields[] = $outgoingField;
                if (isset($handlesIdx[$outgoingField->handle])) {
                    $i = 2;
                    do {
                        $testHandle = $outgoingField->handle . $i++;
                    } while (isset($handlesIdx[$testHandle]));
                    $handleMap[$outgoingField->handle] = $testHandle;
                    $handlesIdx[$testHandle] = true;
                }
            }
        });

        if (!empty($handleMap)) {
            $this->stdout("\n");
            $this->stdout($this->markdownToAnsi(<<<MD
**These fields will be renamed for existing $outgoingEntryType->name entries:**
MD));
            $this->stdout("\n\n");
            foreach ($handleMap as $oldHandle => $newHandle) {
                $this->stdout(' ' . $this->markdownToAnsi("- `$oldHandle` → `$newHandle`") . "\n");
            }
            $this->stdout("\n");
            if (!$this->confirm('Proceed?')) {
                return ExitCode::OK;
            }
            $this->stdout("\n");
        }

        if (!empty($addFields)) {
            $this->do("Updating {$persistingEntryType->name}’s field layout", function() use (
                $persistingEntryType,
                &$handleMap,
                &$addFields,
                &$uidMap,
            ) {
                $fieldLayout = $persistingEntryType->getFieldLayout();
                /** @var array<string,true> $uidIdx */
                $uidIdx = Collection::make($fieldLayout->getAllElements())
                    ->keyBy(fn(FieldLayoutElement $layoutElement) => $layoutElement->uid)
                    ->map(fn() => true)
                    ->all();
                $tabs = $fieldLayout->getTabs();
                /** @var FieldLayoutTab|null $tab */
                $tab = Arr::first($tabs, fn(FieldLayoutTab $tab) => $tab->name === 'Merged Fields');
                if (!$tab) {
                    $tab = new FieldLayoutTab(['name' => 'Merged Fields']);
                    $fieldLayout->setTabs([...$tabs, $tab]);
                }
                $layoutElements = $tab->getElements();

                foreach ($addFields as $field) {
                    $layoutElement = $layoutElements[] = clone $field->layoutElement;

                    // Make sure the same UUID doesn't already exist in the field layout, just to be safe
                    if (isset($uidIdx[$layoutElement->uid])) {
                        $layoutElement->uid = StringHelper::UUID();
                        $uidMap[$field->layoutElement->uid] = $layoutElement->uid;
                        $uidIdx[$layoutElement->uid] = true;
                    }

                    if (isset($handleMap[$field->handle])) {
                        $layoutElement->handle = $handleMap[$field->handle];
                    }
                }

                $tab->setElements($layoutElements);
            });

            $entriesService->saveEntryType($persistingEntryType, false);
        }

        $this->do('Updating usages', function() use (
            $entriesService,
            $fieldsService,
            $persistingEntryType,
            $outgoingEntryType,
            $outgoingUsages,
        ) {
            foreach ($outgoingUsages as $usage) {
                if ($usage->canGetProperty('entryTypes') && $usage->canSetProperty('entryTypes')) {
                    $usage->entryTypes = $this->modifyEntryTypes($usage->entryTypes, $persistingEntryType, $outgoingEntryType);
                    if ($usage instanceof Section) {
                        $entriesService->saveSection($usage, false);
                    } else {
                        $fieldsService->saveField($usage, false);
                    }
                }
            }
        });

        $this->do("Removing $outgoingEntryType->name", function() use ($entriesService, $outgoingEntryType) {
            $entriesService->deleteEntryType($outgoingEntryType);
        });

        $contentMigrator = Craft::$app->getContentMigrator();
        $migrationName = sprintf('m%s_merge_%s_into_%s', gmdate('ymd_His'), $outgoingEntryType->handle, $persistingEntryType->handle);
        $migrationPath = "$contentMigrator->migrationPath/$migrationName.php";

        $this->do("Generating content migration", function() use (
            $persistingEntryType,
            $outgoingEntryType,
            &$uidMap,
            $migrationName,
            $migrationPath,
        ) {
            $content = $this->getView()->renderFile('@app/updates/entry-type-merge.php.template', [
                'namespace' => Craft::$app->getContentMigrator()->migrationNamespace,
                'className' => $migrationName,
                'persistingEntryTypeUid' => $persistingEntryType->uid,
                'outgoingEntryTypeUid' => $outgoingEntryType->uid,
                'layoutElementUidMap' => $uidMap,
            ], $this);
            FileHelper::writeToFile($migrationPath, $content);
        });

        $this->stdout(" → Running content migration …\n");
        $contentMigrator->migrateUp($migrationName);

        $this->success(sprintf(<<<EOD
Entry types merged. Commit `%s`
and your project config changes, and run `craft up` on other environments
for the changes to take effect.
EOD,
            FileHelper::relativePath($migrationPath)
        ));

        return ExitCode::OK;
    }

    /**
     * @param EntryType[] $entryTypes
     * @param EntryType $persistingEntryType
     * @param EntryType $outgoingEntryType
     * @return EntryType[]
     */
    private function modifyEntryTypes(array $entryTypes, EntryType $persistingEntryType, EntryType $outgoingEntryType): array
    {
        $modified = [];
        $hasPersistingEntryType = Collection::make($entryTypes)
            ->contains(fn(EntryType $entryType) => $entryType->uid === $persistingEntryType->uid);

        // Replace the outgoing entry type with the persisting one, or tack it onto the end
        foreach ($entryTypes as $entryType) {
            if ($entryType->uid === $outgoingEntryType->uid) {
                if (!$hasPersistingEntryType) {
                    // Clone the persisting entry type with the original name & handle,
                    // in case the usage supports name & handle overrides
                    $clone = $modified[] = clone $persistingEntryType;
                    $clone->original = $persistingEntryType;
                    $clone->name = $entryType->name;
                    $clone->handle = $entryType->handle;

                    $hasPersistingEntryType = true;
                }
            } else {
                $modified[] = $entryType;
            }
        }

        if (!$hasPersistingEntryType) {
            $modified[] = $persistingEntryType;
        }

        return $modified;
    }
}
