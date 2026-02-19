<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Nodes;

use Craft;
use craft\helpers\UrlHelper;
use Override;
use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\Node;

#[YieldReady]
final class RedirectNode extends Node
{
    /**
     * {@inheritdoc}
     */
    #[Override]
    public function compile(Compiler $compiler): void
    {
        $compiler->addDebugInfo($this);

        if ($this->hasNode('error')) {
            $compiler
                ->write(Craft::class.'::$app->getSession()->setError(')
                ->subcompile($this->getNode('error'))
                ->raw(");\n");
        }

        if ($this->hasNode('notice')) {
            $compiler
                ->write(Craft::class.'::$app->getSession()->setNotice(')
                ->subcompile($this->getNode('notice'))
                ->raw(");\n");
        }

        $compiler
            ->write(Craft::class.'::$app->getResponse()->redirect('.UrlHelper::class.'::url(')
            ->subcompile($this->getNode('path'))
            ->raw('), ')
            ->subcompile($this->getNode('httpStatusCode'))
            ->raw(");\n")
            ->write(Craft::class."::\$app->end();\n");
    }
}
