<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\Eloquent\EloquentElement;
use CraftCms\Cms\Element\Eloquent\EloquentElements;
use CraftCms\Cms\Element\Eloquent\ModelLinks;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Override;

/**
 * Creates elements for model rows that don't have one — rows written before their
 * element type was registered, or whose element couldn't be saved along with them.
 *
 * @since 6.0.0
 */
class SyncEloquentElementsCommand extends Command
{
    use CraftCommand;

    #[Override]
    protected $signature = 'craft:eloquent-elements:sync
        {type? : The handle of the element type to sync, such as `customer`. Defaults to every registered type.}
    ';

    #[Override]
    protected $description = 'Creates elements for rows of registered Eloquent models that don’t have one.';

    public function handle(EloquentElements $eloquentElements, ModelLinks $links, Elements $elements): int
    {
        $handle = $this->argument('type');
        $elementTypes = array_filter(
            $eloquentElements->types(),
            fn (string $elementType) => $handle === null || $elementType::refHandle() === $handle,
        );

        if ($elementTypes === []) {
            $this->components->error($handle === null ? 'No Eloquent element types are registered.' : "No Eloquent element type has the handle “{$handle}”.");

            return self::FAILURE;
        }

        $failed = 0;

        foreach ($elementTypes as $elementType) {
            $created = 0;

            $elementType::newModel()->newQueryWithoutScopes()->chunkById(100, function (Collection $models) use ($elementType, $links, $elements, &$created, &$failed): void {
                $linkedKeys = array_flip($links->linkedKeys($models->first(), $models->modelKeys()));

                foreach ($models as $model) {
                    if (isset($linkedKeys[(string) $model->getKey()]) || (method_exists($model, 'trashed') && $model->trashed())) {
                        continue;
                    }

                    if (EloquentElement::syncing($model, fn () => $elements->saveElement($elementType::fromModel($model), runValidation: false))) {
                        $created++;
                    } else {
                        $failed++;
                        $this->components->warn(sprintf('Couldn’t create an element for %s %s.', class_basename($model), $model->getKey()));
                    }
                }
            });

            $this->components->info(sprintf('Created %d %s.', $created, $created === 1 ? $elementType::lowerDisplayName() : $elementType::pluralLowerDisplayName()));
        }

        if ($failed > 0) {
            $this->components->error(sprintf('%d %s couldn’t be created.', $failed, $failed === 1 ? 'element' : 'elements'));

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
