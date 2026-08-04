<?php

declare(strict_types=1);

namespace Workbench\App\TypeScript;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Spatie\TypeScriptTransformer\ClassPropertyProcessors\FixArrayLikeStructuresClassPropertyProcessor;
use Spatie\TypeScriptTransformer\PhpNodes\PhpClassNode;
use Spatie\TypeScriptTransformer\Transformers\ClassPropertyProcessors\ClassPropertyProcessor;
use Spatie\TypeScriptTransformer\Transformers\ClassTransformer;

class ClassListClassTransformer extends ClassTransformer
{
    public function __construct()
    {
        parent::__construct(
            transpilePhpStanTypeToTypeScriptTypeAction: new LiteralAwareTranspileAction,
        );
    }

    protected function shouldTransform(PhpClassNode $phpClassNode): bool
    {
        return true;
    }

    /** @return array<ClassPropertyProcessor> */
    #[\Override]
    protected function classPropertyProcessors(): array
    {
        $processors = parent::classPropertyProcessors();

        foreach ($processors as $processor) {
            if ($processor instanceof FixArrayLikeStructuresClassPropertyProcessor) {
                $processor->replaceArrayLikeClass(
                    Collection::class,
                    EloquentCollection::class,
                );
            }
        }

        return $processors;
    }
}
