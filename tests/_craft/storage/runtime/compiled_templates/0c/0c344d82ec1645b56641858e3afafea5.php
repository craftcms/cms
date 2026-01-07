<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__ba6f171281e0c1134b70b04d1499e2fb */
class __TwigTemplate_604f065068b871bcb8bad05431e21c77 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__ba6f171281e0c1134b70b04d1499e2fb');
        // line 1
        yield craft\helpers\Html::actionInput('A URL');
        craft\helpers\Template::endProfile('template', '__string_template__ba6f171281e0c1134b70b04d1499e2fb');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__ba6f171281e0c1134b70b04d1499e2fb';
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
        return new Source('{{ actionInput("A URL") }}', '__string_template__ba6f171281e0c1134b70b04d1499e2fb', '');
    }
}
