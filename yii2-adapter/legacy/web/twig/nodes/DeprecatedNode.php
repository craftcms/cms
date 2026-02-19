<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\nodes;

use CraftCms\Cms\Deprecator\Deprecator;
use CraftCms\Cms\Support\Str;
use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Node;

/**
 * Represents a deprecated node.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 * @since 3.7.24
 */
#[YieldReady]
class DeprecatedNode extends Node
{
    /**
     * Constructor
     *
     * @param AbstractExpression $expr
     * @param int $lineno
     */
    public function __construct(AbstractExpression $expr, int $lineno)
    {
        parent::__construct(['expr' => $expr], [], $lineno);
    }

    /**
     * Compiles the node.
     *
     * @param Compiler $compiler
     */
    public function compile(Compiler $compiler)
    {
        $compiler->addDebugInfo($this);

        $compiler
            ->write(sprintf('\app(%s::class)->log(\'template:%s\', ', Deprecator::class, Str::random()))
            ->subcompile($this->getNode('expr'))
            ->raw(sprintf(", '%s', %s);\n", $this->getTemplateName() ?: 'template', $this->getTemplateLine() ?: 'null'));
    }
}
