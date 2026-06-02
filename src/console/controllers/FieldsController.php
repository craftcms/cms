<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use Craft;
use craft\base\FieldInterface;
use craft\base\MergeableFieldInterface;
use craft\console\Controller;
use craft\db\Table;
use craft\errors\InvalidFieldException;
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
     * Merges custom fields together.
     *
     * @param string ...$handles
     * @return int
     */
    public function actionMerge(string ...$handles): int
    {
        if (!$this->interactive) {
            $this->stderr("The fields/merge command must be run interactively.\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $fieldsService = Craft::$app->getFields();

        $handles = Collection::make($handles)
            ->map(function(string $handle) use ($fieldsService) {
                if (str_ends_with($handle, '#')) {
                    $pattern = preg_quote(substr($handle, 0, -1), '/');
                    return Collection::make($fieldsService->getAllFields())
                        ->filter(fn(FieldInterface $field) => preg_match("/^$pattern\d*$/", $field->handle))
                        ->map(fn(FieldInterface $field) => $field->handle)
                        ->all();
                }
                return $handle;
            })
            ->flatten(1)
            ->unique()
            ->all();

        if (count($handles) < 2) {
            $this->stderr("At least two field handles must be provided.\n", Console::FG_RED);
            return ExitCode::USAGE;
        }

        try {
            /** @var Collection<string,FieldInterface> $fields */
            $fields = Collection::make($handles)
                ->map(function(string $handle) use ($fieldsService) {
                    $field = $fieldsService->getFieldByHandle($handle);
                    if (!$field instanceof MergeableFieldInterface) {
                        $message = $field ? sprintf("%s fields don’t support merging.\n", $field::displayName()) : null;
                        throw new InvalidFieldException($handle, $message);
                    }
                    return $field;
                })
                ->keyBy(fn(FieldInterface $field) => $field->handle);
        } catch (InvalidFieldException $e) {
            $this->stderr(sprintf("%s\n", $e->getMessage()), Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        /** @var Collection<string,FieldLayout[]> $layoutsByField */
        $layoutsByField = $fields->map(fn(FieldInterface $field) => $fieldsService->findFieldUsages($field));
        /** @var Collection<FieldLayout> $layouts */
        $layouts = $layoutsByField->values()->flatten(1)->unique();

        // Make sure all the layouts either have an ID or UUID; otherwise we wouldn't know what to do with it
        $unsavableLayouts = $layouts->filter(fn(FieldLayout $layout) => !$layout->id && !$layout->uid);
        if ($unsavableLayouts->isNotEmpty()) {
            $this->output(<<<EOD
These fields can’t be merged because they’re used in field layouts that don’t
have an `id` or `uid`:
EOD, Console::FG_RED);
            $this->output();
            foreach ($unsavableLayouts as $layout) {
                $this->output(sprintf(" - %s", $this->layoutDescriptor($layout)), Console::FG_RED);
            }
            return ExitCode::UNSPECIFIED_ERROR;
        }

        // If any of them are single-instance fields, make sure there are no layouts that already 2+ of them
        if ($fields->contains(fn(FieldInterface $field) => !$field::isMultiInstance())) {
            foreach ($layouts as $layout) {
                $includedFieldCount = 0;
                foreach ($layout->getCustomFields() as $layoutField) {
                    if ($fields->contains(fn(FieldInterface $field) => $field->id === $layoutField->id)) {
                        $includedFieldCount++;
                        if ($includedFieldCount > 1) {
                            break;
                        }
                    }
                }
                if ($includedFieldCount > 1) {
                    $singleInstanceFields = $fields
                        ->filter(fn(FieldInterface $field) => !$field::isMultiInstance())
                        ->map(fn(FieldInterface $field) => sprintf('%s (%s)', $field->name, $field::displayName()))
                        ->all();
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

        /** @var Collection<string,bool> $canMergeByField */
        $canMergeByField = $fields->map(fn() => true);
        $reasons = [];

        foreach ($fields as $fieldA) {
            foreach ($fields as $fieldB) {
                $reason1 = $reason2 = null;
                $canMerge = $fieldB->canMergeInto($fieldA, $reason1) && $fieldA->canMergeFrom($fieldB, $reason2);
                $canMergeByField[$fieldA->handle] = $canMergeByField[$fieldA->handle] && $canMerge;
                if (!$canMerge) {
                    array_push($reasons, ...array_filter([$reason1, $reason2]));
                }
            }
        }

        /** @var Collection<string,string> $mergeableFields */
        $mergeableFields = $canMergeByField->filter()->map(fn(bool $value, string $handle) => $handle);

        if ($mergeableFields->isEmpty()) {
            $this->stderr(sprintf(
                "Not all of those fields support merging into/from the other ones%s\n",
                !empty($reasons)
                    ? sprintf(":\n\n%s\n", implode("\n", array_map(fn(string $reason) => " - $reason", $reasons)))
                    : '.',
            ), Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $mergingRelationFields = $fields->first() instanceof BaseRelationField;
        if ($mergingRelationFields) {
            $this->warning('Merging relation fields should only be done after all elements using them have been resaved.');
            if ($this->confirm('Resave them now?', true)) {
                $description = sprintf('Running `resave/all --with-fields=%s`', implode(',', $handles));
                $this->do($description, function() use ($handles) {
                    $this->output();
                    Console::indent();
                    try {
                        $this->run('resave/all', [
                            'withFields' => $handles,
                        ]);
                    } finally {
                        Console::outdent();
                    }
                });
            }
        }

        $persistingField = $this->choosePersistingField($fields, $layoutsByField, $mergeableFields);
        $outgoingFields = $fields->filter(fn(FieldInterface $field) => $field->handle !== $persistingField->handle);

        $this->output();

        $migrationPaths = [];
        foreach ($outgoingFields as $field) {
            $this->mergeFields($persistingField, $field, $layoutsByField[$field->handle], $migrationPaths);
        }

        $this->success(<<<EOD
Fields merged. Commit the new content migrations and your project config changes,
and run `craft up` on other environments for the changes to take effect.
EOD);

        if ($mergingRelationFields) {
            $this->warning(sprintf(<<<MD
Be sure to run this command on other environments **before** deploying these changes:

```
php craft resave/all --with-fields=%s
```
MD, $fields->keys()->join(',')));
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
            $this->stderr("The fields/auto-merge command must be run interactively.\n");
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

        $projectConfig = Craft::$app->getProjectConfig();

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
                    $this->mergeFields($persistentField, $outgoingField, $usagesByField[$outgoingField->id], $migrationPaths);
                    $this->output();
                });

            // flush out the project config in case the command is aborted early
            // (https://github.com/craftcms/cms/issues/16198)
            $projectConfig->flush();
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
     * @param Collection<string,FieldInterface> $fields
     * @param Collection<string,FieldLayout[]> $layoutsByField
     * @param Collection<string,string> $mergeableFields
     * @return FieldInterface
     */
    private function choosePersistingField(
        Collection $fields,
        Collection $layoutsByField,
        Collection $mergeableFields,
    ): FieldInterface {
        if ($mergeableFields->count() > 1) {
            /** @var Collection<string,string> $infoByField */
            $infoByField = $mergeableFields->map(fn(string $handle) => sprintf(
                ' - `%s` (%s)',
                $handle,
                $this->usagesDescriptor($layoutsByField[$handle]),
            ));

            $this->output();
            $this->output($this->markdownToAnsi(sprintf(<<<MD
**Which field should persist?**

%s
MD, $infoByField->join("\n"))));
            $this->output();

            $layoutsByField = $layoutsByField
                ->sortBy(fn(array $layouts) => count($layouts), SORT_NUMERIC, true);

            $choice = $this->select(
                'Choose:',
                $layoutsByField
                    ->map(fn(array $layouts, string $handle) => $fields[$handle]->name)
                    ->all(),
                $layoutsByField->keys()->first());

            return $fields[$choice];
        }

        return $fields[$mergeableFields->first()];
    }

    private function usagesDescriptor(array $layouts): string
    {
        return sprintf('%s %s', count($layouts), count($layouts) === 1 ? 'usage' : 'usages');
    }

    private function layoutDescriptor(FieldLayout $layout): string
    {
        $elementDisplayName = $layout->type::lowerDisplayName();
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
     * @param string[] $migrationPaths
     */
    private function mergeFields(
        FieldInterface $persistingField,
        FieldInterface $outgoingField,
        array $outgoingLayouts,
        array &$migrationPaths,
    ): void {
        $fieldsService = Craft::$app->getFields();

        $this->do("Updating usages for `$outgoingField->handle`", function() use (
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

        $this->do("Removing `$outgoingField->handle`", function() use ($fieldsService, $outgoingField) {
            $fieldsService->deleteField($outgoingField);
        });

        $contentMigrator = Craft::$app->getContentMigrator();
        $migrationName = sprintf('m%s_merge_%s_into_%s', gmdate('ymd_His'), $outgoingField->handle, $persistingField->handle);
        $migrationPath = $migrationPaths[] = "$contentMigrator->migrationPath/$migrationName.php";

        $this->do("Generating content migration for `$outgoingField->handle`", function() use (
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

        $this->output($this->markdownToAnsi(" → Running content migration for `$outgoingField->handle` …"));
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

    /**
     * Deletes custom fields.
     *
     * @param string ...$handles
     * @return int
     * @since 5.7.0
     */
    public function actionDelete(string ...$handles): int
    {
        /** @var FieldInterface[] $fields */
        $fields = [];
        $fieldsService = Craft::$app->getFields();

        foreach ($handles as $handle) {
            $field = $fieldsService->getFieldByHandle($handle);
            if (!$field) {
                $this->stdout("Invalid field handle: $handle\n", Console::FG_RED);
                return ExitCode::UNSPECIFIED_ERROR;
            }
            $fields[] = $field;
        }

        foreach ($fields as $field) {
            $this->do("Deleting `$field->name`", function() use ($fieldsService, $field) {
                $fieldsService->deleteField($field);
            });
        }

        $this->stdout("Done\n", Console::FG_GREEN);
        return ExitCode::OK;
    }
}
