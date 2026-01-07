<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* _layouts/components/global-live-region */
class __TwigTemplate_7866cb6a89239b70d605583b634b6c81 extends Template
{
    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('template', '_layouts/components/global-live-region');
        // line 1
        yield '<div id="global-live-region" class="visually-hidden" role="status"></div>';
        craft\helpers\Template::endProfile('template', '_layouts/components/global-live-region');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '_layouts/components/global-live-region';
    }

    /**
     * @codeCoverageIgnore
     */
    public function getDebugInfo(): array
    {
        return [43 => 1];
    }

    public function getSourceContext(): Source
    {
        return new Source('<div id="global-live-region" class="visually-hidden" role="status"></div>', '_layouts/components/global-live-region', '/tmp/packages/craft5/src/templates/_layouts/components/global-live-region.twig');
    }
}
