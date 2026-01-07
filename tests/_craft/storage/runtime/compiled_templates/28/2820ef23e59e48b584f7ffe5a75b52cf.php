<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__791380e632e8eac22b4526bb39f0e0d5 */
class __TwigTemplate_cff1f26b2e347f399c549797a9735a7d extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__791380e632e8eac22b4526bb39f0e0d5');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->indexOfFilter((isset($context['array']) || array_key_exists('array', $context) ? $context['array'] : (function () {
            throw new RuntimeError('Variable "array" does not exist.', 1, $this->source);
        })()), 'Doe');
        craft\helpers\Template::endProfile('template', '__string_template__791380e632e8eac22b4526bb39f0e0d5');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__791380e632e8eac22b4526bb39f0e0d5';
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
        return new Source('{{ array|indexOf("Doe") }}', '__string_template__791380e632e8eac22b4526bb39f0e0d5', '');
    }
}
