<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__6183f3fa3d308ac0bc2b734fc3b3e7e1 */
class __TwigTemplate_322fc33ebe57fa914798843f3d47e3c1 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__6183f3fa3d308ac0bc2b734fc3b3e7e1');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->percentageFilter(0.8, 1);
        craft\helpers\Template::endProfile('template', '__string_template__6183f3fa3d308ac0bc2b734fc3b3e7e1');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__6183f3fa3d308ac0bc2b734fc3b3e7e1';
    }

    /**
     * @codeCoverageIgnore
     */
    #[\Override]
    public function isTraitable(): bool
    {
        return false;
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
        return new Source('{{ 0.8|percentage(decimals=1) }}', '__string_template__6183f3fa3d308ac0bc2b734fc3b3e7e1', '');
    }
}
