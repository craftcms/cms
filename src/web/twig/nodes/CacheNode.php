<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\nodes;

use Craft;
use craft\helpers\DateTimeHelper;
use craft\helpers\StringHelper;
use Twig\Compiler;
use Twig\Node\Node;

/**
 * Cache twig node.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class CacheNode extends Node
{
    /**
     * @var int
     */
    private static int $_cacheCount = 1;

    /**
     * @inheritdoc
     */
    public function compile(Compiler $compiler): void
    {
        $n = self::$_cacheCount++;

        $conditions = $this->hasNode('conditions') ? $this->getNode('conditions') : null;
        $ignoreConditions = $this->hasNode('ignoreConditions') ? $this->getNode('ignoreConditions') : null;
        $key = $this->hasNode('key') ? $this->getNode('key') : null;
        $expiration = $this->hasNode('expiration') ? $this->getNode('expiration') : null;

        $durationNum = $this->getAttribute('durationNum');
        $durationUnit = $this->getAttribute('durationUnit');
        $global = $this->getAttribute('global') ? 'true' : 'false';

        $compiler
            ->addDebugInfo($this)
            ->write('$cacheService = ' . Craft::class . "::\$app->getTemplateCaches();\n")
            ->write('$request = ' . Craft::class . "::\$app->getRequest();\n")
            ->write("\$ignoreCache$n = (\$request->getIsLivePreview() || \$request->getToken()");

        if ($conditions) {
            $compiler
                ->raw(' || !(')
                ->subcompile($conditions)
                ->raw(')');
        } elseif ($ignoreConditions) {
            $compiler
                ->raw(' || (')
                ->subcompile($ignoreConditions)
                ->raw(')');
        }

        $compiler
            ->raw(");\n")
            ->write("if (!\$ignoreCache$n) {\n")
            ->indent()
            ->write("\$cacheKey$n = ");

        if ($key) {
            $compiler->subcompile($key);
        } else {
            $compiler->raw('"' . StringHelper::randomString() . '"');
        }

        $compiler
            ->raw(";\n")
            ->write("\$cacheBody$n = \$cacheService->getTemplateCache(\$cacheKey$n, $global, true);\n")
            ->outdent()
            ->write("} else {\n")
            ->indent()
            ->write("\$cacheBody$n = null;\n")
            ->outdent()
            ->write("}\n")
            ->write("if (\$cacheBody$n === null) {\n")
            ->indent()
            ->write("if (!\$ignoreCache$n) {\n")
            ->indent()
            ->write("\$cacheService->startTemplateCache(true, $global);\n")
            ->outdent()
            ->write("}\n")
            ->write("ob_start();\n")
            ->subcompile($this->getNode('body'))
            ->write("\$cacheBody$n = ob_get_clean();\n")
            ->write("if (!\$ignoreCache$n) {\n")
            ->indent()
            ->write("\$cacheService->endTemplateCache(\$cacheKey$n, $global, ");

        if ($durationNum) {
            $duration = DateTimeHelper::relativeTimeStatement($durationNum, $durationUnit);
            $compiler->raw("'$duration'");
        } else {
            $compiler->raw('null');
        }

        $compiler->raw(', ');

        if ($expiration) {
            $compiler->subcompile($expiration);
        } else {
            $compiler->raw('null');
        }

        $compiler
            ->raw(", \$cacheBody$n, true);\n")
            ->outdent()
            ->write("}\n")
            ->outdent()
            ->write("}\n")
            ->write("echo \$cacheBody$n;\n");
    }
}
