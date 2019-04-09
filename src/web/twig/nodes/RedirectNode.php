<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\nodes;

use craft\helpers\UrlHelper;
use Twig\Compiler;
use Twig\Node\Node;

/**
 * Class RedirectNode
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class RedirectNode extends Node
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function compile(Compiler $compiler)
    {
        $compiler->addDebugInfo($this);

        if ($this->hasNode('error')) {
            $compiler
                ->write('\Craft::$app->getSession()->setError(')
                ->subcompile($this->getNode('error'))
                ->raw(");\n");
        }

        if ($this->hasNode('notice')) {
            $compiler
                ->write('\Craft::$app->getSession()->setNotice(')
                ->subcompile($this->getNode('notice'))
                ->raw(");\n");
        }

        $compiler
            ->write('\Craft::$app->getResponse()->redirect(' . UrlHelper::class . '::url(')
            ->subcompile($this->getNode('path'))
            ->raw('), ')
            ->subcompile($this->getNode('httpStatusCode'))
            ->raw(");\n")
            ->write('\Craft::$app->end();');
    }
}
