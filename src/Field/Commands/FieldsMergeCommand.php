<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Commands;

use craft\errors\InvalidFieldException;
use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Field\BaseRelationField;
use CraftCms\Cms\Field\Commands\Concerns\MergesFields;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Contracts\MergeableFieldInterface;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;

final class FieldsMergeCommand extends Command
{
    use CraftCommand;
    use MergesFields;

    #[\Override]
    protected $signature = 'craft:fields:merge {handles* : The field handles to merge.}';

    #[\Override]
    protected $description = 'Merges custom fields together.';

    #[\Override]
    protected $aliases = ['fields/merge'];

    public function handle(Fields $fieldsService): int
    {
        if (! $this->input->isInteractive()) {
            $this->components->error('The fields:merge command must be run interactively');

            return self::FAILURE;
        }

        $handles = Collection::make($this->argument('handles'))
            ->map(function (string $handle) use ($fieldsService) {
                if (! str_ends_with($handle, '#')) {
                    return $handle;
                }

                $pattern = preg_quote(substr($handle, 0, -1), '/');

                return $fieldsService->getAllFields()
                    ->filter(fn (FieldInterface $field) => preg_match("/^$pattern\d*$/", (string) $field->handle))
                    ->map(fn (FieldInterface $field) => $field->handle)
                    ->all();
            })
            ->flatten(1)
            ->unique();

        if ($handles->count() < 2) {
            $this->components->error('At least two field handles must be provided.');

            return self::FAILURE;
        }

        /** @var Collection<string,FieldInterface> $fields */
        $fields = $handles
            ->map(function (string $handle) use ($fieldsService) {
                $field = $fieldsService->getFieldByHandle($handle);

                if (! $field instanceof MergeableFieldInterface) {
                    $message = $field ? sprintf("%s fields don’t support merging.\n", $field::displayName()) : null;
                    throw new InvalidFieldException($handle, $message);
                }

                return $field;
            })
            ->keyBy(fn (FieldInterface $field) => $field->handle);

        /** @var Collection<string,\CraftCms\Cms\FieldLayout\FieldLayout[]> $layoutsByField */
        $layoutsByField = $fields->map(fn (FieldInterface $field) => $fieldsService->findFieldUsages($field));

        /** @var Collection<\CraftCms\Cms\FieldLayout\FieldLayout> $layouts */
        $layouts = $layoutsByField->values()->flatten(1)->unique();

        // Make sure all the layouts either have an ID or UUID; otherwise we wouldn't know what to do with it
        $unsavableLayouts = $layouts->filter(fn (FieldLayout $layout) => ! $layout->id && ! $layout->uid);

        if ($unsavableLayouts->isNotEmpty()) {
            $this->components->error(<<<'EOD'
            These fields can’t be merged because they’re used in field layouts that don’t
            have an `id` or `uid`:
            EOD);

            $this->components->bulletList($unsavableLayouts->map(
                fn ($layout) => sprintf(' - %s', $this->layoutDescriptor($layout))
            )->all());

            return self::FAILURE;
        }

        // If any of them are single-instance fields, make sure there are no layouts that already 2+ of them
        if ($fields->contains(fn (FieldInterface $field) => ! $field::isMultiInstance())) {
            foreach ($layouts as $layout) {
                $includedFieldCount = 0;
                foreach ($layout->getCustomFields() as $layoutField) {
                    if ($fields->contains(fn (FieldInterface $field) => $field->id === $layoutField->id)) {
                        $includedFieldCount++;
                        if ($includedFieldCount > 1) {
                            break;
                        }
                    }
                }

                if ($includedFieldCount === 0) {
                    continue;
                }

                $singleInstanceFields = $fields
                    ->reject(fn (FieldInterface $field): bool => $field::isMultiInstance())
                    ->map(fn (FieldInterface $field) => sprintf('%s (%s)', $field->name, $field::displayName()))
                    ->all();

                $this->components->error(
                    sprintf(<<<'EOD'
                    These fields can’t be merged because %s %s support multiple instances,
                    and both fields are already in use by %s.
                    EOD,
                        implode(' and ', $singleInstanceFields),
                        count($singleInstanceFields) === 1 ? 'doesn’t' : 'don’t',
                        $this->layoutDescriptor($layout))
                );

                return self::FAILURE;
            }
        }

        /** @var Collection<string,bool> $canMergeByField */
        $canMergeByField = $fields->map(fn () => true);
        $reasons = [];

        foreach ($fields as $fieldA) {
            foreach ($fields as $fieldB) {
                $reason1 = $reason2 = null;
                /**
                 * @var MergeableFieldInterface $fieldA
                 * @var MergeableFieldInterface $fieldB
                 */
                $canMerge = $fieldB->canMergeInto($fieldA, $reason1) && $fieldA->canMergeFrom($fieldB, $reason2);
                $canMergeByField[$fieldA->handle] = $canMergeByField[$fieldA->handle] && $canMerge;

                if (! $canMerge) {
                    array_push($reasons, ...array_filter([$reason1, $reason2]));
                }
            }
        }

        /** @var Collection<string,string> $mergeableFields */
        $mergeableFields = $canMergeByField->filter()->map(fn (bool $value, string $handle) => $handle);

        if ($mergeableFields->isEmpty()) {
            $this->components->error(sprintf(
                'Not all of those fields support merging into/from the other ones%s',
                ! empty($reasons)
                    ? sprintf(":\n\n%s\n", implode("\n", array_map(fn (string $reason) => " - $reason", $reasons)))
                    : '.'
            ));

            return self::FAILURE;
        }

        $mergingRelationFields = $fields->first() instanceof BaseRelationField;

        if ($mergingRelationFields) {
            $this->warn('Merging relation fields should only be done after all elements using them have been resaved.');

            if (confirm('Resave them now?')) {
                $this->info(sprintf('Running `resave:all --with-fields=%s`', $handles->implode(',')));

                $this->call('craft:resave:all', [
                    '--with-fields' => $handles,
                ]);
            }
        }

        $persistingField = $this->choosePersistingField($fields, $layoutsByField, $mergeableFields);
        $outgoingFields = $fields->filter(fn (FieldInterface $field) => $field->handle !== $persistingField->handle);

        $migrationPaths = [];
        foreach ($outgoingFields as $field) {
            $this->mergeFields($fieldsService, $persistingField, $field, $layoutsByField[$field->handle], $migrationPaths);
            $this->newLine();
        }

        $this->components->success(<<<'EOD'
        Fields merged. Commit the new content migrations and your project config changes,
        and run `craft up` on other environments for the changes to take effect.
        EOD);

        if ($mergingRelationFields) {
            $this->components->warn(sprintf(<<<'MD'
            Be sure to run this command on other environments **before** deploying these changes:
            
            ```
            php craft:resave:all --with-fields=%s
            ```
            MD, $fields->keys()->join(',')));
        }

        return self::SUCCESS;
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

    private function choosePersistingField(
        Collection $fields,
        Collection $layoutsByField,
        Collection $mergeableFields,
    ): FieldInterface {
        if ($mergeableFields->count() <= 1) {
            return $fields[$mergeableFields->first()];
        }

        /** @var Collection<string,string> $infoByField */
        $infoByField = $mergeableFields->mapWithKeys(fn (string $handle) => [
            $handle => sprintf(
                '`%s` (%s)',
                $handle,
                Str::plural('usage', count($layoutsByField[$handle]), true),
            ),
        ]);

        $choice = select(
            'Which field should persist?',
            $infoByField->all(),
        );

        return $fields[$choice];
    }
}
