<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Transformers;

use CraftCms\Cms\Import\Importers\BaseImporter;
use League\Fractal\TransformerAbstract;

abstract class BaseTransformer extends TransformerAbstract
{
    /**
     * No-op base hook for subclasses to supply extra match criteria to complement/override
     * the ones coming from the UI/config file and the data.
     *
     * Returns already resolved additional match criteria.
     *
     * Meaning it needs to contain the key-value pairs where
     * the key is the property, field handle or column name in the system,
     * and the value is the actual value to use when matching
     * (NOT the handle used to locate the data in the incoming dataset).
     *
     * It can be used to do any sort of computations before deciding on what to return,
     * or, if needed for any reason, it could be a simple pass-through,
     * like: return ['myFieldHandle' => $data['myIncomingKey']];
     *
     * @param  BaseImporter  $importer  The importer configuration.
     * @param  array  $data  The data being imported.
     */
    public function additionalMatchCriteria(BaseImporter $importer, array $data): array
    {
        // you can set your match criteria here, instead of using the UI;
        // by default, this doesn't do anything
        return [];
    }
}
