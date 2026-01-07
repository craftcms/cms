<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__b956ce0908ac648172b1e755f20ad818 */
class __TwigTemplate_536096109b7856fc8d65fcfecb8fe12f extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__b956ce0908ac648172b1e755f20ad818');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->renderObjectTemplate('{{ object.firstName}}', ['firstName' => 'John']);
        craft\helpers\Template::endProfile('template', '__string_template__b956ce0908ac648172b1e755f20ad818');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__b956ce0908ac648172b1e755f20ad818';
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
        return new Source('{{ renderObjectTemplate("{{ object.firstName}}", {firstName: "John"}) }}', '__string_template__b956ce0908ac648172b1e755f20ad818', '');
    }
}
