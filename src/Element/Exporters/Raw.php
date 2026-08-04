<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Exporters;

use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;

use function CraftCms\Cms\t;

class Raw extends ElementExporter
{
    #[\Override]
    public static function displayName(): string
    {
        return t('Raw data (fastest)');
    }

    public function export(ElementQueryInterface $query): mixed
    {
        return $query->asArray()->all();
    }
}
