<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Nodes;

use Craft;
use craft\helpers\DateTimeHelper;
use CraftCms\Cms\Support\Str;
use Override;
use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\Node;

#[YieldReady]
final class CacheNode extends Node
{
    /**
     * {@inheritdoc}
     */
    #[Override]
    public function compile(Compiler $compiler): void
    {
        $n = $compiler->getVarName();

        $compiler
            ->addDebugInfo($this)
            ->write('$cacheService = '.Craft::class."::\$app->getTemplateCaches();\n")
            ->write("\$request = request();\n")
            ->write("\$ignoreCache_$n = (\$request->isPreview() || \$request->getToken()");

        if ($this->hasNode('conditions')) {
            $compiler
                ->raw(' || !(')
                ->subcompile($this->getNode('conditions'))
                ->raw(')');
        } elseif ($this->hasNode('ignoreConditions')) {
            $compiler
                ->raw(' || (')
                ->subcompile($this->getNode('ignoreConditions'))
                ->raw(')');
        }

        $compiler
            ->raw(");\n")
            ->write("if (!\$ignoreCache_$n) {\n")
            ->indent()
            ->write("\$cacheKey_$n = ");

        if ($this->hasNode('key')) {
            $compiler->subcompile($this->getNode('key'));
        } else {
            $compiler->raw('"'.Str::random(36).'"');
        }

        $global = $this->getAttribute('global') ? 'true' : 'false';

        $compiler
            ->raw(";\n")
            ->write("\$cacheBody_$n = \$cacheService->getTemplateCache(\$cacheKey_$n, $global, true);\n")
            ->outdent()
            ->write("} else {\n")
            ->indent()
            ->write("\$cacheBody_$n = null;\n")
            ->outdent()
            ->write("}\n")
            ->write("if (\$cacheBody_$n === null) {\n")
            ->indent()
            ->write("if (!\$ignoreCache_$n) {\n")
            ->indent()
            ->write("\$cacheService->startTemplateCache(true, $global);\n")
            ->outdent()
            ->write("}\n")
            ->write("ob_start();\n")
            ->subcompile($this->getNode('body'))
            ->write("\$cacheBody_$n = ob_get_clean();\n")
            ->write("if (!\$ignoreCache_$n) {\n")
            ->indent()
            ->write("\$cacheService->endTemplateCache(\$cacheKey_$n, $global, ");

        if ($this->hasNode('durationNum')) {
            $durationUnit = $this->getAttribute('durationUnit');

            $compiler
                ->raw(sprintf('%s::relativeTimeStatement(', DateTimeHelper::class))
                ->subcompile($this->getNode('durationNum'))
                ->raw(", '$durationUnit')");
        } else {
            $compiler->raw('null');
        }

        $compiler->raw(', ');

        if ($this->hasNode('expiration')) {
            $compiler->subcompile($this->getNode('expiration'));
        } else {
            $compiler->raw('null');
        }

        $compiler
            ->raw(", \$cacheBody_$n, true);\n")
            ->outdent()
            ->write("}\n")
            ->outdent()
            ->write("}\n")
            ->write("yield \$cacheBody_$n;\n");
    }
}
