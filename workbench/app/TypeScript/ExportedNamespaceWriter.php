<?php

declare(strict_types=1);

namespace Workbench\App\TypeScript;

use Spatie\TypeScriptTransformer\Data\Location;
use Spatie\TypeScriptTransformer\Data\WritingContext;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptNamespace;
use Spatie\TypeScriptTransformer\Writers\GlobalNamespaceWriter;

/**
 * A {@see GlobalNamespaceWriter} that writes nested namespaces with the
 * `export` keyword (`declare namespace A { export namespace B { … } }`).
 *
 * Vue's compiler-sfc resolves `defineProps<CraftCms.…>()` type references at
 * build time with its own resolver, and its namespace walker only registers
 * *exported* members — the bare `namespace B {` nesting the stock writer
 * produces is invisible to it, failing the build with "Unresolvable type
 * reference". TypeScript itself treats both forms identically in an ambient
 * declaration file.
 */
class ExportedNamespaceWriter extends GlobalNamespaceWriter
{
    #[\Override]
    protected function buildNamespace(Location $location): TypeScriptNamespace
    {
        $children = [];

        foreach ($location->children as $child) {
            $children[] = $this->buildExportedNamespace($child);
        }

        return new TypeScriptNamespace(
            $location->name,
            $location->transformed,
            $children,
        );
    }

    private function buildExportedNamespace(Location $location): TypeScriptNamespace
    {
        $children = [];

        foreach ($location->children as $child) {
            $children[] = $this->buildExportedNamespace($child);
        }

        return new class($location->name, $location->transformed, $children) extends TypeScriptNamespace
        {
            public function write(WritingContext $context): string
            {
                return 'export '.parent::write($context);
            }
        };
    }
}
