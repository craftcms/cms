<?php

namespace CraftCms\Cms\Field\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Field\Fields;
use Illuminate\Console\Command;

/** @since 6.0.0 */
final class DeleteFieldsCommand extends Command
{
    use CraftCommand;

    protected $signature = 'craft:fields:delete {handles* : The field handles to delete}';

    protected $description = 'Deletes custom fields.';

    protected $aliases = ['fields/delete'];

    public function handle(Fields $fieldsService): int
    {
        $fields = [];

        foreach ($this->argument('handles') as $handle) {
            $field = $fieldsService->getFieldByHandle($handle);

            if (! $field) {
                $this->components->error("Invalid field handle: $handle");

                return self::FAILURE;
            }

            $fields[] = $field;
        }

        foreach ($fields as $field) {
            $this->components->task(
                "Deleting `$field->name`",
                fn () => $fieldsService->deleteField($field),
            );
        }

        return self::SUCCESS;
    }
}
