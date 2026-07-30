<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Field\BaseRelationField;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Contracts\MergeableFieldInterface;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;

class FieldsAutoMergeCommand extends Command
{
    use CraftCommand;

    #[\Override]
    protected $signature = 'craft:fields:auto-merge';

    #[\Override]
    protected $description = 'Finds fields with identical settings and merges them together.';

    #[\Override]
    protected $aliases = ['fields/auto-merge'];

    public function handle(Fields $fieldsService): int
    {
        if (! $this->input->isInteractive()) {
            $this->components->error('The fields:auto-merge command must be run interactively');

            return self::FAILURE;
        }

        /** @var Collection<string, Collection<int, FieldInterface&MergeableFieldInterface>> $groups */
        $groups = $fieldsService->getAllFields()
            ->filter(fn ($field) => $field instanceof MergeableFieldInterface)
            ->groupBy(fn (FieldInterface $field) => implode(',', [
                $field::class,
                (int) $field->searchable,
                $field->translationMethod,
                $field->translationKeyFormat ?? '-',
                md5(Json::encode($field->getSettings())),
            ]))
            ->filter(function (Collection $group) {
                if ($group->count() === 1) {
                    return false;
                }

                $others = Collection::make($group);
                /** @var MergeableFieldInterface $first */
                $first = $others->shift();
                $reason = null;

                return $others->doesntContain(fn (MergeableFieldInterface $other) => (
                    ! $other->canMergeInto($first, $reason) ||
                    ! $first->canMergeFrom($other, $reason)
                ));
            });

        if ($groups->isEmpty()) {
            $this->components->success('No fields with identical settings could be found.');

            return self::SUCCESS;
        }

        $migrationPaths = [];
        $relationFieldHandles = [];

        /** @var Collection<int, FieldInterface&MergeableFieldInterface> $group */
        foreach ($groups as $group) {
            /** @var FieldInterface $first */
            $first = $group->first();

            $this->components->info(sprintf(
                '**Found %s %s fields with identical settings:**',
                $group->count(),
                $first::displayName(),
            ));

            $usagesByField = [];
            $group = $group
                ->each(function (FieldInterface $field) use ($fieldsService, &$usagesByField) {
                    $usagesByField[$field->id] = $fieldsService->findFieldUsages($field);
                })
                ->sortBy(fn (FieldInterface $field) => $field->handle)
                ->sortBy(fn (FieldInterface $field) => count($usagesByField[$field->id]), SORT_NUMERIC, true)
                ->keyBy(fn (FieldInterface $field) => $field->handle);

            $this->components->bulletList($group->map(function (FieldInterface $field) use (&$usagesByField) {
                return sprintf(
                    '`%s` (%s)',
                    $field->handle,
                    Str::plural('usage', count($usagesByField[$field->id]), true),
                );
            })->all());

            if (! confirm('Merge these fields?')) {
                continue;
            }

            $mergingRelationFields = $group->first() instanceof BaseRelationField;

            if ($mergingRelationFields) {
                $handles = $group->map(fn (FieldInterface $field) => $field->handle)->values()->all();
                array_push($relationFieldHandles, ...$handles);
                $this->components->warn('Merging relation fields should only be done after all elements using them have been resaved.');

                if (confirm('Resave them now?')) {
                    $this->components->info(sprintf('Running `resave/all --with-fields=%s`', implode(',', $handles)));

                    $this->call('craft:resave:all', [
                        '--with-fields' => $handles,
                    ]);
                }
            }

            $choice = select(
                'Which one should persist?',
                $group
                    ->keyBy(fn (FieldInterface $field) => $field->handle)
                    ->mapWithKeys(fn (FieldInterface $field) => [$field->handle => $field->getUiLabel()])
                    ->all(),
            );

            /** @var FieldInterface&MergeableFieldInterface $persistentField */
            $persistentField = $group->get($choice);

            $group
                ->except($choice)
                ->each(function (FieldInterface&MergeableFieldInterface $outgoingField) use ($fieldsService, $persistentField, &$migrationPaths) {
                    $this->components->info("Merging `{$outgoingField->handle}` → `{$persistentField->handle}`");
                    $this->components->task("Merging `{$outgoingField->handle}` → `{$persistentField->handle}`", function () use ($fieldsService, $persistentField, $outgoingField, &$migrationPaths) {
                        $result = $fieldsService->merge($persistentField, $outgoingField);
                        $migrationPaths[] = $result->migrationPath;
                    });
                });

            // flush out the project config in case the command is aborted early
            // (https://github.com/craftcms/cms/issues/16198)
            ProjectConfig::flush();
        }

        if (! empty($migrationPaths)) {
            $this->components->success(<<<'EOD'
            Fields merged. Commit the new content migrations and your project config changes,
            and run `craft up` on other environments for the changes to take effect.
            EOD);

            if (! empty($relationFieldHandles)) {
                $this->components->warn(sprintf(<<<'MD'
                Be sure to run this command on other environments **before** deploying these changes:
                
                ```
                php craft resave:all --with-fields=%s
                ```
                MD, implode(',', $relationFieldHandles)));
            }
        } else {
            $this->components->info('No fields merged.');
        }

        return self::SUCCESS;
    }
}
