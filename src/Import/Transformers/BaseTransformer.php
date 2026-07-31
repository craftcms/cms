<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Transformers;

use CraftCms\Cms\Import\Importers\BaseImporter;
use League\Fractal\TransformerAbstract;

abstract class BaseTransformer extends TransformerAbstract
{
    /**
     * No-op base hook for subclasses to supply extra match criteria beyond the UI/config-based ones.
     * Returns the additional match criteria.
     *
     * @param BaseImporter $importer The importer configuration.
     * @param array $data The data being imported.
     * @return array
     */
    public function additionalMatchCriteria(BaseImporter $importer, array $data): array
    {
        // you can set your match criteria here, instead of using the UI
        // by default, this doesn't do anything
        return [];
    }
}
