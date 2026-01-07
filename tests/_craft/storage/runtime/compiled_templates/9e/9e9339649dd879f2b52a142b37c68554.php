<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__b0c7dc7a0cd27cfd4702bff30b04dedd */
class __TwigTemplate_b1905b1730d70987cd3012278f6544df extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__b0c7dc7a0cd27cfd4702bff30b04dedd');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->datetimeFilter($this->env, (isset($context['d']) || array_key_exists('d', $context) ? $context['d'] : (function () {
            throw new RuntimeError('Variable "d" does not exist.', 1, $this->source);
        })()), 'Y-m-d h:i:s');
        craft\helpers\Template::endProfile('template', '__string_template__b0c7dc7a0cd27cfd4702bff30b04dedd');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__b0c7dc7a0cd27cfd4702bff30b04dedd';
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
        return new Source('{{ d|datetime("Y-m-d h:i:s") }}', '__string_template__b0c7dc7a0cd27cfd4702bff30b04dedd', '');
    }
}
