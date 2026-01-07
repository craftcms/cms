<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__2b87412f94a998473bf7808f553e350c */
class __TwigTemplate_9e832a8d9489c3173da8339397c1e35d extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__2b87412f94a998473bf7808f553e350c');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->indexOfFilter((isset($context['array']) || array_key_exists('array', $context) ? $context['array'] : (function () {
            throw new RuntimeError('Variable "array" does not exist.', 1, $this->source);
        })()), 'Smith');
        craft\helpers\Template::endProfile('template', '__string_template__2b87412f94a998473bf7808f553e350c');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__2b87412f94a998473bf7808f553e350c';
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
        return new Source('{{ array|indexOf("Smith") }}', '__string_template__2b87412f94a998473bf7808f553e350c', '');
    }
}
