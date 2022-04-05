<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\nodes;

use craft\helpers\StringHelper;
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
class DeprecatedNode extends Node
{
    /**
     * Constructor
     *
     * @param AbstractExpression $expr
     * @param int $lineno
     * @param string|null $tag
     */
    public function __construct(AbstractExpression $expr, int $lineno, string $tag = null)
    {
        parent::__construct(['expr' => $expr], [], $lineno, $tag);
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
            ->write(sprintf('\Craft::$app->getDeprecator()->log(\'template:%s\', ', StringHelper::randomString()))
            ->subcompile($this->getNode('expr'))
            ->raw(sprintf(", '%s', %s);\n", $this->getTemplateName() ?: 'template', $this->getTemplateLine() ?: 'null'));
    }
}
