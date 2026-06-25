<?php

declare(strict_types=1);

namespace Workbench\App\Providers;

use CraftCms\Cms\Route\Data\Route;
use CraftCms\Cms\Update\Data\Updates;
use DateTimeInterface;
use Spatie\LaravelTypeScriptTransformer\TypeScriptTransformerApplicationServiceProvider;
use Spatie\TypeScriptTransformer\Transformers\EnumTransformer;
use Spatie\TypeScriptTransformer\TypeScriptTransformerConfigFactory;
use Spatie\TypeScriptTransformer\Writers\GlobalNamespaceWriter;
use Workbench\App\TypeScript\ClassListClassTransformer;
use Workbench\App\TypeScript\ClassListTransformedProvider;

class TypeScriptTransformerServiceProvider extends TypeScriptTransformerApplicationServiceProvider
{
    protected function configure(TypeScriptTransformerConfigFactory $config): void
    {
        $config
            ->outputDirectory(dirname(__DIR__, 3).'/resources/js/generated')
            ->writer(new GlobalNamespaceWriter('types.d.ts'))
            ->replaceType(DateTimeInterface::class, 'string')
            ->provider(new ClassListTransformedProvider(
                [
                    Updates::class,
                    Route::class,
                ],
                [
                    new EnumTransformer,
                    new ClassListClassTransformer,
                ],
            ));
    }
}
