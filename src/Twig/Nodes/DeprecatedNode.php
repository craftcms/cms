<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Nodes;

use CraftCms\Cms\Deprecator\Deprecator;
use CraftCms\Cms\Support\Str;
use Override;
use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Node;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
#[YieldReady]
class DeprecatedNode extends Node
{
    /**
     * Constructor
     */
    public function __construct(AbstractExpression $expr, int $lineno)
    {
        parent::__construct(['expr' => $expr], [], $lineno);
    }

    /**
     * Compiles the node.
     */
    #[Override]
    public function compile(Compiler $compiler)
    {
        $compiler->addDebugInfo($this);

        $compiler
            ->write(sprintf('\app(%s::class)->log(\'template:%s\', ', Deprecator::class, Str::random()))
            ->subcompile($this->getNode('expr'))
            ->raw(sprintf(", '%s', %s);\n", $this->getTemplateName() ?: 'template', $this->getTemplateLine() ?: 'null'));
    }
}
