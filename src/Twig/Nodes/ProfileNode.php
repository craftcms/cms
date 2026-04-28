<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Nodes;

use CraftCms\Cms\View\TemplateProfiler;
use Override;
use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\Node;

#[YieldReady]
class ProfileNode extends Node
{
    /**
     * Constructor.
     *
     * @param  string  $stage  The profiling stage ('begin' or 'end')
     * @param  string  $type  The type of template element being profiled ('template', 'block', or 'macro')
     * @param  string  $name  The name of the template element
     */
    public function __construct(string $stage, string $type, string $name)
    {
        parent::__construct([], compact('stage', 'type', 'name'));
    }

    #[Override]
    public function compile(Compiler $compiler): void
    {
        $compiler
            ->write(sprintf("app('%s')->%sProfile(", TemplateProfiler::class, $this->getAttribute('stage')))
            ->repr($this->getAttribute('type'))
            ->raw(', ')
            ->repr($this->getAttribute('name'))
            ->raw(");\n");
    }
}
