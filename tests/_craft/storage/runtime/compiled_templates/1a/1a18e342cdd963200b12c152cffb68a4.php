<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__cc6911df81e7cec0f02f1c0da0e85c94 */
class __TwigTemplate_1e4019c62af4e0535bbd4a0581032101 extends Template
{
    private readonly Source $source;

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('template', '__string_template__cc6911df81e7cec0f02f1c0da0e85c94');
        // line 1
        yield $context['exampleParam'] ?? null;
        yield craft\helpers\Template::attribute($this->env, $this->source, ($context['object'] ?? null), 'exampleParam', [], 'any', false, false, false, 1);
        craft\helpers\Template::endProfile('template', '__string_template__cc6911df81e7cec0f02f1c0da0e85c94');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__cc6911df81e7cec0f02f1c0da0e85c94';
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
        return new Source('{{ exampleParam }}{{ object.exampleParam }}', '__string_template__cc6911df81e7cec0f02f1c0da0e85c94', '');
    }
}
