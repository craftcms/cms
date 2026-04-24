<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Commands;

use CraftCms\Cms\Component\ComponentHelper;
use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Support\Facades\Sections;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Support\Facades\DB;
use Override;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;

final class DeleteAllOfTypeCommand extends Command implements PromptsForMissingInput
{
    use CraftCommand;

    #[Override]
    protected $signature = 'craft:elements:delete-all-of-type
        {type : The fully qualified element class name to delete.}
        {--dry-run : Report matching elements without deleting them.}
    ';

    #[Override]
    protected $description = 'Deletes all elements of a given type.';

    #[Override]
    protected $aliases = ['elements/delete-all-of-type'];

    public function handle(): int
    {
        $type = (string) $this->argument('type');
        $query = DB::table(Table::ELEMENTS)
            ->where('type', $type)
            ->select('id');

        if ($type === Entry::class) {
            $singleSections = Sections::getSectionsByType(SectionType::Single);

            if ($singleSections->isNotEmpty()) {
                $singleEntryIds = Entry::find()
                    ->sectionId($singleSections->pluck('id')->all())
                    ->status(null)
                    ->site('*')
                    ->unique()
                    ->ids();

                if ($singleEntryIds !== []) {
                    $query->whereNotIn('id', $singleEntryIds);
                }
            }
        }

        $total = $query->count();
        $isValidType = ComponentHelper::validateComponentClass($type, ElementInterface::class);
        $typeLabel = $this->typeLabel($type, $total, $isValidType);

        if (! $total) {
            $this->components->info("No $typeLabel found.");

            return self::SUCCESS;
        }

        $this->components->info("$total $typeLabel found.");

        if (! $this->option('dry-run') && $this->input->isInteractive() && ! confirm('Continue?')) {
            $this->components->info('Aborted.');

            return self::SUCCESS;
        }

        $query->eachById(function (object $element) use ($type, $isValidType): void {
            $message = sprintf(
                'Deleting %s %s',
                $isValidType ? $type::lowerDisplayName() : 'element',
                $element->id,
            );

            $this->components->task($message, function () use ($element): void {
                if ($this->option('dry-run')) {
                    return;
                }

                DB::table(Table::ELEMENTS)->delete($element->id);
                DB::table(Table::SEARCHINDEX)
                    ->where('elementId', $element->id)
                    ->delete();
            });
        });

        $prefix = $this->option('dry-run') ? '[DRY RUN] ' : '';
        $this->components->success("{$prefix}{$total} $typeLabel deleted.");

        return self::SUCCESS;
    }

    /**
     * @param  class-string<ElementInterface>  $type
     */
    private function typeLabel(string $type, int $total, bool $isValidType, bool $includeClass = false): string
    {
        if (! $isValidType) {
            return $total === 1 ? "`$type` element" : "`$type` elements";
        }

        if ($includeClass) {
            return sprintf('%s (%s)', $type::pluralDisplayName(), $type);
        }

        return $total === 1 ? $type::lowerDisplayName() : $type::pluralLowerDisplayName();
    }

    /** @return array<string, \Closure(): mixed> */
    #[Override]
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'type' => function () {
                $options = DB::table(Table::ELEMENTS)
                    ->distinct()
                    ->orderBy('type')
                    ->pluck('type')
                    ->mapWithKeys(fn (string $type) => [
                        $this->typeLabel(
                            type: $type,
                            total: 2,
                            isValidType: ComponentHelper::validateComponentClass($type, ElementInterface::class),
                            includeClass: true,
                        ) => $type,
                    ])
                    ->all();

                if ($options === []) {
                    return null;
                }

                $selection = select(
                    label: 'Select an element type',
                    options: array_keys($options),
                );

                return $options[$selection];
            },
        ];
    }
}
