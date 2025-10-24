<?php

namespace CraftCms\Cms\EntryType\Commands;

use craft\base\FieldLayoutElement;
use craft\elements\Entry;
use craft\models\FieldLayoutTab;
use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Database\Migrator;
use CraftCms\Cms\EntryType\Data\EntryType;
use CraftCms\Cms\EntryType\EntryTypes;
use CraftCms\Cms\Field\Contracts\ElementContainerFieldInterface;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;

final class MergeCommand extends Command implements PromptsForMissingInput
{
    use CraftCommand;

    protected $signature = 'craft:entry-types:merge {handleA} {handleB}';

    protected $description = 'Merges two entry types.';

    protected $aliases = ['entry-types/merge'];

    public function handle(EntryTypes $entryTypes, Fields $fields, Migrator $migrator): int
    {
        $entryTypeA = $entryTypes->getEntryTypeByHandle($handleA = $this->argument('handleA'));

        if (! $entryTypeA) {
            $this->components->error("Invalid entry type handle: $handleA\n");

            return self::FAILURE;
        }

        $entryTypeB = $entryTypes->getEntryTypeByHandle($handleB = $this->argument('handleB'));

        if (! $entryTypeB) {
            $this->components->error("Invalid entry type handle: $handleB\n");

            return self::FAILURE;
        }

        $usagesA = $entryTypeA->findUsages();
        $usagesB = $entryTypeB->findUsages();

        if ($this->input->isInteractive()) {
            $queryA = Entry::find()->typeId($entryTypeA->id)->status(null);
            $queryB = Entry::find()->typeId($entryTypeB->id)->status(null);

            $totalEntriesA = $queryA->count();
            $totalEntriesB = $queryB->count();

            $infoA = Str::plural('usage', count($usagesA), true).', '.Str::plural('entry', $totalEntriesA, true);
            $infoB = Str::plural('usage', count($usagesB), true).', '.Str::plural('entry', $totalEntriesB, true);

            $choice = select(
                label: 'Which entry type should persist?',
                options: [
                    $entryTypeA->handle => $entryTypeA->name." ($infoA)",
                    $entryTypeB->handle => $entryTypeB->name." ($infoB)",
                ],
                default: $totalEntriesA >= $totalEntriesB ? $entryTypeA->handle : $entryTypeB->handle,
            );
        } else {
            $choice = $entryTypeA->handle;
        }

        /** @var EntryType $persistingEntryType */
        /** @var EntryType $outgoingEntryType */
        /** @var array<\CraftCms\Cms\Section\Data\Section|ElementContainerFieldInterface> $outgoingUsages */
        [$persistingEntryType, $outgoingEntryType, $outgoingUsages] = $choice === $entryTypeA->handle
            ? [$entryTypeA, $entryTypeB, $usagesB]
            : [$entryTypeB, $entryTypeA, $usagesA];

        unset($entryTypeA, $entryTypeB);

        /** @var Collection<string,FieldInterface> $persistingFields */
        $persistingFields = collect($persistingEntryType->getFieldLayout()->getCustomFields())
            ->keyBy(fn (FieldInterface $field) => $field->handle);

        // Track field instances that need to be added to the layout,
        // including the new field handles we're going to need to assign them.
        // And create a map of field instance UUIDs we'll need to update within JSON data.
        /** @var array<string,true> $handlesIdx */
        $handlesIdx = $persistingFields->map(fn () => true)->all();
        /** @var array<string,string> $handleMap */
        $handleMap = [];
        /** @var FieldInterface[] $addFields */
        $addFields = [];
        /** @var array<string,string> $uidMap */
        $uidMap = [];

        $this->components->task(
            description: 'Inspecting fields',
            task: function () use (
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
                    if (! $outgoingField::isMultiInstance()) {
                        $persistingField = $persistingFields->first(fn (FieldInterface $field) => $field->id === $outgoingField->id);
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
                            $testHandle = $outgoingField->handle.$i++;
                        } while (isset($handlesIdx[$testHandle]));
                        $handleMap[$outgoingField->handle] = $testHandle;
                        $handlesIdx[$testHandle] = true;
                    }
                }
            }
        );

        if (! empty($handleMap)) {
            $this->components->info("These fields will be renamed for existing $outgoingEntryType->name entries:");
            $this->components->bulletList(collect($handleMap)->map(fn ($newHandle, $oldHandle) => "`$oldHandle` → `$newHandle`")->all());

            if (! confirm('Proceed?')) {
                return self::SUCCESS;
            }
        }

        if (! empty($addFields)) {
            $this->components->task(
                description: "Updating {$persistingEntryType->name}’s field layout",
                task: function () use (
                    $persistingEntryType,
                    &$handleMap,
                    &$addFields,
                    &$uidMap,
                ) {
                    $fieldLayout = $persistingEntryType->getFieldLayout();
                    /** @var array<string,true> $uidIdx */
                    $uidIdx = collect($fieldLayout->getAllElements())
                        ->keyBy(fn (FieldLayoutElement $layoutElement) => $layoutElement->uid)
                        ->map(fn () => true)
                        ->all();
                    $tabs = $fieldLayout->getTabs();
                    /** @var FieldLayoutTab|null $tab */
                    $tab = Arr::first($tabs, fn (FieldLayoutTab $tab) => $tab->name === 'Merged Fields');
                    if (! $tab) {
                        $tab = new FieldLayoutTab(['name' => 'Merged Fields']);
                        $fieldLayout->setTabs([...$tabs, $tab]);
                    }
                    $layoutElements = $tab->getElements();

                    foreach ($addFields as $field) {
                        $layoutElement = $layoutElements[] = clone $field->layoutElement;

                        // Make sure the same UUID doesn't already exist in the field layout, just to be safe
                        if (isset($uidIdx[$layoutElement->uid])) {
                            $layoutElement->uid = Str::uuid()->toString();
                            $uidMap[$field->layoutElement->uid] = $layoutElement->uid;
                            $uidIdx[$layoutElement->uid] = true;
                        }

                        if (isset($handleMap[$field->handle])) {
                            $layoutElement->handle = $handleMap[$field->handle];
                        }
                    }

                    $tab->setElements($layoutElements);
                }
            );

            $entryTypes->saveEntryType($persistingEntryType);
        }

        $this->components->task(
            description: 'Updating usages',
            task: function () use (
                $fields,
                $persistingEntryType,
                $outgoingEntryType,
                $outgoingUsages,
            ) {
                foreach ($outgoingUsages as $usage) {
                    if (method_exists($usage, 'getEntryTypes') && method_exists($usage, 'setEntryTypes')) {
                        $usage->setEntryTypes($this->modifyEntryTypes($usage->getEntryTypes(), $persistingEntryType, $outgoingEntryType));
                        if ($usage instanceof Section) {
                            Sections::saveSection($usage);
                        } else {
                            $fields->saveField($usage, false);
                        }
                    }
                }
            }
        );

        $this->components->task(
            description: "Removing $outgoingEntryType->name",
            task: function () use ($entryTypes, $outgoingEntryType) {
                $entryTypes->deleteEntryType($outgoingEntryType);
            }
        );

        $migrationName = sprintf('%s_merge_%s_into_%s', gmdate('Y_m_d_His'), $outgoingEntryType->handle, $persistingEntryType->handle);
        $migrationPath = database_path("migrations/{$migrationName}.php");

        $this->components->task(
            description: 'Generating content migration',
            task: function () use (
                $migrationPath,
                $persistingEntryType,
                $outgoingEntryType,
                $uidMap,
            ) {
                ob_start();
                File::getRequire(Aliases::get('@craftcms/stubs/entry-type-merge.php.stub'), [
                    'persistingEntryTypeUid' => $persistingEntryType->uid,
                    'outgoingEntryTypeUid' => $outgoingEntryType->uid,
                    'layoutElementUidMap' => $uidMap,
                ]);
                File::put($migrationPath, ob_get_clean());
            }
        );

        $this->components->twoColumnDetail('Running content migrations');
        $migrator->track('content')->run();

        $this->components->success(sprintf(<<<'EOD'
            Entry types merged. Commit <options=bold>`%s`</>
            and your project config changes, and run <options=bold>`craft up`</> on other environments
            for the changes to take effect.
            EOD,

            Str::after($migrationPath, base_path('/'))
        ));

        return self::SUCCESS;
    }

    /**
     * @param  EntryType[]  $entryTypes
     * @return EntryType[]
     */
    private function modifyEntryTypes(array $entryTypes, EntryType $persistingEntryType, EntryType $outgoingEntryType): array
    {
        $modified = [];
        $hasPersistingEntryType = collect($entryTypes)->contains(
            fn (EntryType $entryType) => $entryType->uid === $persistingEntryType->uid
        );

        // Replace the outgoing entry type with the persisting one, or tack it onto the end
        foreach ($entryTypes as $entryType) {
            if ($entryType->uid !== $outgoingEntryType->uid) {
                $modified[] = $entryType;

                continue;
            }

            if ($hasPersistingEntryType) {
                continue;
            }

            // Clone the persisting entry type with the original name & handle,
            // in case the usage supports name & handle overrides
            $clone = $modified[] = clone $persistingEntryType;
            $clone->original = $persistingEntryType;
            $clone->name = $entryType->name;
            $clone->handle = $entryType->handle;

            $hasPersistingEntryType = true;
        }

        if (! $hasPersistingEntryType) {
            $modified[] = $persistingEntryType;
        }

        return $modified;
    }

    protected function promptForMissingArgumentsUsing(): array
    {
        $options = app(EntryTypes::class)->getAllEntryTypes()
            ->mapWithKeys(
                fn (EntryType $entryType) => [$entryType->handle => $entryType->name." ({$entryType->handle})"],
            );

        return [
            'handleA' => fn () => select(
                'Select the first entry type to merge',
                $options->all(),
            ),
            'handleB' => fn () => select(
                'Select the second entry type to merge',
                $options->filter(fn ($name, $handle) => $handle !== $this->argument('handleA'))->all(),
            ),
        ];
    }
}
