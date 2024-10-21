<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use Craft;
use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\base\MergeableFieldInterface;
use craft\console\Controller;
use craft\db\Table;
use craft\fields\BaseRelationField;
use craft\helpers\Console;
use craft\helpers\Db;
use craft\helpers\FileHelper;
use craft\helpers\Json;
use craft\models\FieldLayout;
use craft\services\Fields;
use Illuminate\Support\Collection;
use yii\console\ExitCode;

/**
 * Manages custom fields.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.3.0
 */
class FieldsController extends Controller
{
    /**
     * Merges two custom fields together.
     *
     * @param string $handleA
     * @param string $handleB
     * @return int
     */
    public function actionMerge(string $handleA, string $handleB): int
    {
        if (!$this->interactive) {
            $this->stderr("The fields/merge command must be run interactively.\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $fieldsService = Craft::$app->getFields();

        $fieldA = $fieldsService->getFieldByHandle($handleA);
        if (!$fieldA) {
            $this->stderr("Invalid field handle: $handleA\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }
        if (!$fieldA instanceof MergeableFieldInterface) {
            $this->stderr(sprintf("%s fields don’t support merging.\n", $fieldA::displayName()), Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $fieldB = $fieldsService->getFieldByHandle($handleB);
        if (!$fieldB) {
            $this->stderr("Invalid field handle: $handleB\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }
        if (!$fieldB instanceof MergeableFieldInterface) {
            $this->stderr(sprintf("%s fields don’t support merging.\n", $fieldB::displayName()), Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $layoutsA = $fieldsService->findFieldUsages($fieldA);
        $layoutsB = $fieldsService->findFieldUsages($fieldB);
        /** @var Collection<FieldLayout> $layouts */
        $layouts = Collection::make([...$layoutsA, ...$layoutsB])->unique();

        // Make sure all the layouts either have an ID or UUID; otherwise we wouldn't know what to do with it
        $unsavableLayouts = $layouts->filter(fn(FieldLayout $layout) => !$layout->id && !$layout->uid);
        if ($unsavableLayouts->isNotEmpty()) {
            $this->output(<<<EOD
These fields can’t be merged because one or both are used in a field layout(s)
that doesn’t have an `id` or `uid`:
EOD, Console::FG_RED);
            $this->output();
            foreach ($unsavableLayouts as $layout) {
                $this->output(sprintf(" - %s", $this->layoutDescriptor($layout)), Console::FG_RED);
            }
            return ExitCode::UNSPECIFIED_ERROR;
        }

        // If either one of them is a single-instance field, make sure there are no layouts that already include both
        if (!$fieldA::isMultiInstance() || !$fieldB::isMultiInstance()) {
            foreach ($layouts as $layout) {
                $layoutFields = Collection::make($layout->getCustomFields());
                if (
                    $layoutFields->contains(fn(FieldInterface $field) => $field->id === $fieldA->id) &&
                    $layoutFields->contains(fn(FieldInterface $field) => $field->id === $fieldB->id)
                ) {
                    $singleInstanceFields = array_filter([
                        !$fieldA::isMultiInstance() ? sprintf('%s (%s)', $fieldA->name, $fieldA::displayName()) : null,
                        !$fieldB::isMultiInstance() ? sprintf('%s (%s)', $fieldB->name, $fieldB::displayName()) : null,
                    ]);
                    $this->output($this->markdownToAnsi(sprintf(<<<EOD
These fields can’t be merged because %s %s support multiple instances,
and both fields are already in use by %s.
EOD,
                        implode(' and ', $singleInstanceFields),
                        count($singleInstanceFields) === 1 ? 'doesn’t' : 'don’t',
                        $this->layoutDescriptor($layout),
                    )), Console::FG_RED);
                    return ExitCode::UNSPECIFIED_ERROR;
                }
            }
        }

        $reasonA1 = $reasonA2 = $reasonB1 = $reasonB2 = null;
        $canMergeIntoFieldA = $fieldB->canMergeInto($fieldA, $reasonA1) && $fieldA->canMergeFrom($fieldB, $reasonA2);
        $canMergeIntoFieldB = $fieldA->canMergeInto($fieldB, $reasonB1) && $fieldB->canMergeFrom($fieldA, $reasonB2);

        if (!$canMergeIntoFieldA && !$canMergeIntoFieldB) {
            $reasons = array_filter([$reasonA1, $reasonA2, $reasonB1, $reasonB2]);
            $this->stderr(sprintf(
                "Neither of those fields support merging into/from the other one%s\n",
                !empty($reasons)
                    ? sprintf(":\n\n%s\n", implode("\n", array_map(fn(string $reason) => " - $reason", $reasons)))
                    : '.',
            ), Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $mergingRelationFields = $fieldA instanceof BaseRelationField;
        if ($mergingRelationFields) {
            $this->warning('Merging relation fields should only be done after all elements using them have been resaved.');
            if ($this->confirm('Resave them now?', true)) {
                $this->do("Running `resave/all --with-fields=$handleA,$handleB`", function() use ($handleA, $handleB) {
                    $this->output();
                    Console::indent();
                    try {
                        $this->run('resave/all', [
                            'withFields' => [$handleA, $handleB],
                        ]);
                    } finally {
                        Console::outdent();
                    }
                });
            }
        }

        [$persistingField, $outgoingField, $outgoingLayouts] = $this->choosePersistingField(
            $fieldA,
            $fieldB,
            $layoutsA,
            $layoutsB,
            $canMergeIntoFieldA,
            $canMergeIntoFieldB,
        );

        $this->output();
        $this->mergeFields($persistingField, $outgoingField, $outgoingLayouts, $migrationPath);

        $this->success(sprintf(<<<EOD
Fields merged. Commit `%s`
and your project config changes, and run `craft up` on other environments
for the changes to take effect.
EOD,
            FileHelper::relativePath($migrationPath)
        ));

        if ($mergingRelationFields) {
            $this->warning(<<<MD
Be sure to run this command on other environments **before** deploying these changes:

```
php craft resave/all --with-fields=$handleA,$handleB
```
MD);
        }

        return ExitCode::OK;
    }

    /**
     * Finds fields with identical settings and merges them together.
     *
     * @return int
     */
    public function actionAutoMerge(): int
    {
        if (!$this->interactive) {
            $this->stderr("The fields/merge command must be run interactively.\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $fieldsService = Craft::$app->getFields();

        /** @var Collection<Collection<FieldInterface>> $groups */
        $groups = Collection::make($fieldsService->getAllFields())
            ->filter(fn($field) => $field instanceof MergeableFieldInterface)
            ->groupBy(fn(FieldInterface $field) => implode(',', [
                $field::class,
                (int)$field->searchable,
                $field->translationMethod,
                $field->translationKeyFormat ?? '-',
                md5(Json::encode($field->getSettings())),
            ]))
            ->filter(function(Collection $group) {
                if ($group->count() === 1) {
                    return false;
                }

                $others = Collection::make($group);
                /** @var MergeableFieldInterface $first */
                $first = $others->shift();
                $reason = null;
                return $others->doesntContain(fn(MergeableFieldInterface $other) => (
                    !$other->canMergeInto($first, $reason) ||
                    !$first->canMergeFrom($other, $reason)
                ));
            });

        if ($groups->isEmpty()) {
            $this->success('No fields with identical settings could be found.');
            return ExitCode::OK;
        }

        $migrationPaths = [];
        $relationFieldHandles = [];

        foreach ($groups as $group) {
            /** @var Collection<FieldInterface> $group */
            /** @var FieldInterface $first */
            $first = $group->first();

            $this->output($this->markdownToAnsi(sprintf(
                '**Found %s %s fields with identical settings:**',
                $group->count(),
                $first::displayName(),
            )));
            $this->output();
            $usagesByField = [];
            $group = $group
                ->each(function(FieldInterface $field) use ($fieldsService, &$usagesByField) {
                    $usagesByField[$field->id] = $fieldsService->findFieldUsages($field);
                })
                ->sortBy(fn(FieldInterface $field) => $field->handle)
                ->sortBy(fn(FieldInterface $field) => count($usagesByField[$field->id]), SORT_NUMERIC, true)
                ->keyBy(fn(FieldInterface $field) => $field->handle)
                ->each(function(FieldInterface $field) use (&$usagesByField) {
                    $this->output($this->markdownToAnsi(sprintf(
                        " - `%s` (%s)",
                        $field->handle,
                        $this->usagesDescriptor($usagesByField[$field->id]),
                    )));
                });

            $this->output();

            if (!$this->confirm('Merge these fields?')) {
                continue;
            }

            $this->output();

            $mergingRelationFields = $group->first() instanceof BaseRelationField;
            if ($mergingRelationFields) {
                $handles = $group->map(fn(FieldInterface $field) => $field->handle)->values()->all();
                array_push($relationFieldHandles, ...$handles);
                $this->warning('Merging relation fields should only be done after all elements using them have been resaved.');
                if ($this->confirm('Resave them now?', true)) {
                    $this->do(
                        sprintf('Running `resave/all --with-fields=%s`', implode(',', $handles)),
                        function() use ($handles) {
                            $this->output();
                            Console::indent();
                            try {
                                $this->run('resave/all', [
                                    'withFields' => $handles,
                                ]);
                            } finally {
                                Console::outdent();
                            }
                        },
                    );
                }
            }

            $this->output($this->markdownToAnsi('**Which one should persist?**'));

            $choice = $this->select(
                'Choose:',
                $group
                    ->keyBy(fn(FieldInterface $field) => $field->handle)
                    ->map(fn(FieldInterface $field) => $field->getUiLabel())
                    ->all(),
                $group->first()->handle,
            );

            $this->output();
            /** @var FieldInterface $persistentField */
            $persistentField = $group->get($choice);

            $group
                ->except($choice)
                ->each(function(FieldInterface $outgoingField) use ($persistentField, $usagesByField, &$migrationPaths) {
                    $this->output($this->markdownToAnsi("Merging `{$outgoingField->handle}` → `{$persistentField->handle}`"));
                    $this->mergeFields($persistentField, $outgoingField, $usagesByField[$outgoingField->id], $migrationPath);
                    $migrationPaths[] = $migrationPath;
                    $this->output();
                });
        }

        if (!empty($migrationPaths)) {
            $this->success(<<<EOD
Fields merged. Commit the new content migrations and your project config changes,
and run `craft up` on other environments for the changes to take effect.
EOD);

            if (!empty($relationFieldHandles)) {
                $this->warning(sprintf(<<<MD
Be sure to run this command on other environments **before** deploying these changes:

```
php craft resave/all --with-fields=%s
```
MD, implode(',', $relationFieldHandles)));
            }
        } else {
            $this->failure('No fields merged.');
        }

        return ExitCode::OK;
    }

    /**
     * @param FieldInterface $fieldA
     * @param FieldInterface $fieldB
     * @param FieldLayout[] $layoutsA
     * @param FieldLayout[] $layoutsB
     * @param bool $canMergeIntoFieldA
     * @param bool $canMergeIntoFieldB
     * @return array{0:FieldInterface,1:FieldInterface,2:FieldLayout[]}
     */
    private function choosePersistingField(
        FieldInterface $fieldA,
        FieldInterface $fieldB,
        array $layoutsA,
        array $layoutsB,
        bool $canMergeIntoFieldA,
        bool $canMergeIntoFieldB,
    ): array {
        if ($canMergeIntoFieldA && $canMergeIntoFieldB) {
            $infoA = $this->usagesDescriptor($layoutsA);
            $infoB = $this->usagesDescriptor($layoutsB);

            $this->output();
            $this->output($this->markdownToAnsi(<<<MD
**Which field should persist?**

 - `$fieldA->handle` ($infoA)
 - `$fieldB->handle` ($infoB)
MD));
            $this->output();

            $choice = $this->select('Choose:', [
                $fieldA->handle => $fieldA->name,
                $fieldB->handle => $fieldB->name,
            ], count($layoutsA) >= count($layoutsB) ? $fieldA->handle : $fieldB->handle);

            return $choice === $fieldA->handle
                ? [$fieldA, $fieldB, $layoutsB]
                : [$fieldB, $fieldA, $layoutsA];
        }

        return $canMergeIntoFieldA
            ? [$fieldA, $fieldB, $layoutsB]
            : [$fieldB, $fieldA, $layoutsA];
    }

    private function usagesDescriptor(array $layouts): string
    {
        return sprintf('%s %s', count($layouts), count($layouts) === 1 ? 'usage' : 'usages');
    }

    private function layoutDescriptor(FieldLayout $layout): string
    {
        /** @var string|ElementInterface $elementType */
        $elementType = $layout->type;
        $elementDisplayName = $elementType::lowerDisplayName();
        $providerHandle = $layout->provider?->getHandle();
        return $providerHandle
            ? "the `$providerHandle` $elementDisplayName layout"
            : sprintf(
                "%s $elementDisplayName layout",
                in_array(strtolower($elementDisplayName[0]), ['a', 'e', 'i', 'o', 'u']) ? 'an' : 'a',
            );
    }

    /**
     * @param FieldInterface $persistingField
     * @param FieldInterface $outgoingField
     * @param FieldLayout[] $outgoingLayouts
     * @param string|null $migrationPath
     */
    private function mergeFields(
        FieldInterface $persistingField,
        FieldInterface $outgoingField,
        array $outgoingLayouts,
        ?string &$migrationPath = null,
    ): void {
        $fieldsService = Craft::$app->getFields();

        $this->do('Updating usages', function() use (
            $fieldsService,
            $persistingField,
            $outgoingField,
            $outgoingLayouts,
        ) {
            $projectConfigService = Craft::$app->getProjectConfig();
            $muteEvents = $projectConfigService->muteEvents;
            $projectConfigService->muteEvents = true;

            foreach ($outgoingLayouts as $layout) {
                $changed = false;
                foreach ($layout->getCustomFieldElements() as $layoutElement) {
                    if ($layoutElement->getFieldUid() === $outgoingField->uid) {
                        // hard code the label, handle, and instructions, if they differ from the persistent field
                        $layoutElement->label = $this->layoutElementOverride($persistingField->name, $outgoingField->name, $layoutElement->label);
                        $layoutElement->handle = $this->layoutElementOverride($persistingField->handle, $outgoingField->handle, $layoutElement->handle);
                        $layoutElement->instructions = $this->layoutElementOverride($persistingField->instructions, $outgoingField->instructions, $layoutElement->instructions);

                        $layoutElement->setField($persistingField);
                        $changed = true;
                    }
                }

                if ($changed) {
                    if (!$layout->id) {
                        // Maybe the ID just wasn't known
                        $layout->id = Db::idByUid(Table::FIELDLAYOUTS, $layout->uid);
                    }
                    if ($layout->id) {
                        $fieldsService->saveLayout($layout);
                    }
                    if ($layout->uid) {
                        $projectConfigOccurrences = $projectConfigService->find(fn(array $item) => isset($item[$layout->uid]));
                        foreach ($projectConfigOccurrences as $path => $item) {
                            $projectConfigService->set("$path.$layout->uid", $layout->getConfig());
                        }
                    }
                }
            }

            $projectConfigService->muteEvents = $muteEvents;
        });

        $this->do("Removing $outgoingField->name", function() use ($fieldsService, $outgoingField) {
            $fieldsService->deleteField($outgoingField);
        });

        $contentMigrator = Craft::$app->getContentMigrator();
        $migrationName = sprintf('m%s_merge_%s_into_%s', gmdate('ymd_His'), $outgoingField->handle, $persistingField->handle);
        $migrationPath = "$contentMigrator->migrationPath/$migrationName.php";

        $this->do("Generating content migration", function() use (
            $persistingField,
            $outgoingField,
            $migrationName,
            $migrationPath,
        ) {
            $content = $this->getView()->renderFile('@app/updates/field-merge.php.template', [
                'namespace' => Craft::$app->getContentMigrator()->migrationNamespace,
                'className' => $migrationName,
                'persistingFieldUid' => $persistingField->uid,
                'outgoingFieldUid' => $outgoingField->uid,
            ], $this);
            FileHelper::writeToFile($migrationPath, $content);
        });

        $this->output(" → Running content migration …");
        Craft::$app->getContentMigrator()->migrateUp($migrationName);
    }

    private function layoutElementOverride(?string $persistingFieldValue, ?string $outgoingFieldValue, ?string $override): ?string
    {
        $persistingFieldValue = ($persistingFieldValue === '' ? null : $persistingFieldValue);
        $outgoingFieldValue = ($outgoingFieldValue === '' ? null : $outgoingFieldValue);
        $override = ($override === '' ? null : $override);
        $expected = $override ?? $outgoingFieldValue;
        return $persistingFieldValue !== $expected ? $expected : null;
    }
}
