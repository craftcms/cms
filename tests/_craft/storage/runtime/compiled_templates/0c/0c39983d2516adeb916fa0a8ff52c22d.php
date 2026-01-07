<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__8d5089cfff73fc686f08498a6e455c16 */
class __TwigTemplate_0c9304d602c9922b67d0a410ed6373ce extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__8d5089cfff73fc686f08498a6e455c16');
        // line 1
        yield ((((isset($context['array']) || array_key_exists('array', $context) ? $context['array'] : (function () {
            throw new RuntimeError('Variable "array" does not exist.', 1, $this->source);
        })()) != $this->extensions['craft\web\twig\Extension']->shuffleFunction((isset($context['array']) || array_key_exists('array', $context) ? $context['array'] : (function () {
            throw new RuntimeError('Variable "array" does not exist.', 1, $this->source);
        })()))) || ((isset($context['array']) || array_key_exists('array', $context) ? $context['array'] : (function () {
            throw new RuntimeError('Variable "array" does not exist.', 1, $this->source);
        })()) != $this->extensions['craft\web\twig\Extension']->shuffleFunction((isset($context['array']) || array_key_exists('array', $context) ? $context['array'] : (function () {
            throw new RuntimeError('Variable "array" does not exist.', 1, $this->source);
        })()))))) ? ('yes') : ('no');
        craft\helpers\Template::endProfile('template', '__string_template__8d5089cfff73fc686f08498a6e455c16');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__8d5089cfff73fc686f08498a6e455c16';
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
        return new Source('{{ array != shuffle(array) or array != shuffle(array) ? "yes" : "no" }}', '__string_template__8d5089cfff73fc686f08498a6e455c16', '');
    }
}
