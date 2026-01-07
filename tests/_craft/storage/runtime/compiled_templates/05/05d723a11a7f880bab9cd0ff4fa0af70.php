<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* template.twig */
class __TwigTemplate_120275e1a53c79af7bfec642a2dfff56 extends Template
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
        craft\helpers\Template::beginProfile('template', 'template.twig');
        // line 1
        yield 'Im a template!';
        craft\helpers\Template::endProfile('template', 'template.twig');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return 'template.twig';
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
        return new Source('Im a template!', 'template.twig', '/tmp/packages/craft5/tests/_craft/templates/template.twig');
    }
}
