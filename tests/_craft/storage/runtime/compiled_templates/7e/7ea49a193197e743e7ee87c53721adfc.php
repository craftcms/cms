<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* novar.twig */
class __TwigTemplate_b5c480cbdb129495068b3f3455859518 extends Template
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
        craft\helpers\Template::beginProfile('template', 'novar.twig');
        // line 1
        yield 'I have no vars';
        craft\helpers\Template::endProfile('template', 'novar.twig');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return 'novar.twig';
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
        return new Source('I have no vars', 'novar.twig', '/tmp/packages/craft5/tests/_craft/templates/novar.twig');
    }
}
