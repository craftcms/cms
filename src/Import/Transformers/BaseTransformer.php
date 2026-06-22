<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Transformers;

use CraftCms\Cms\Import\Importers\BaseImporter;
use League\Fractal\TransformerAbstract;

abstract class BaseTransformer extends TransformerAbstract
{
    public function matchCriteria(BaseImporter $config, array $data, mixed $item): array
    {
        // you can set your match criteria here, instead of using the UI
        // by default, this doesn't do anything
        return [];
    }
}
