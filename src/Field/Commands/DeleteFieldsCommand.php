<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Field\Fields;
use Illuminate\Console\Command;

class DeleteFieldsCommand extends Command
{
    use CraftCommand;

    #[\Override]
    protected $signature = 'craft:fields:delete {handles* : The field handles to delete}';

    #[\Override]
    protected $description = 'Deletes custom fields.';

    #[\Override]
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
