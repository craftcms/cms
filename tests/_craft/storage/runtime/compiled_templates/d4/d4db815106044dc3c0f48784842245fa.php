<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__04b0696b8e0ae6a264177f49e4a5c8a5 */
class __TwigTemplate_2f6d9cea9a38273b2b7d3dfad1caa6bd extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__04b0696b8e0ae6a264177f49e4a5c8a5');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->translateFilter('Source message with {var}', ['var' => (isset($context['myVar']) || array_key_exists('myVar', $context) ? $context['myVar'] : (function () {
            throw new RuntimeError('Variable "myVar" does not exist.', 1, $this->source);
        })())]);
        craft\helpers\Template::endProfile('template', '__string_template__04b0696b8e0ae6a264177f49e4a5c8a5');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__04b0696b8e0ae6a264177f49e4a5c8a5';
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
        return new Source('{{ "Source message with {var}"|t({var: myVar}) }}', '__string_template__04b0696b8e0ae6a264177f49e4a5c8a5', '');
    }
}
