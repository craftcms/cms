<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__06b42c9af74c277ac0652a600546e4aa */
class __TwigTemplate_49170434b8eec59f4c9ef946c45175cf extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__06b42c9af74c277ac0652a600546e4aa');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->numberFilter('foo');
        craft\helpers\Template::endProfile('template', '__string_template__06b42c9af74c277ac0652a600546e4aa');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__06b42c9af74c277ac0652a600546e4aa';
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
        return new Source('{{ "foo"|number }}', '__string_template__06b42c9af74c277ac0652a600546e4aa', '');
    }
}
