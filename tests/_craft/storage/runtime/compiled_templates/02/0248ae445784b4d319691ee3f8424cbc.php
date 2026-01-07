<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__efe4b42648e98daad5b21fe724aa7a08 */
class __TwigTemplate_91c0f8b27cf2560b2ce0b723dc2b52c9 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__efe4b42648e98daad5b21fe724aa7a08');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->timeFilter($this->env, (isset($context['d']) || array_key_exists('d', $context) ? $context['d'] : (function () {
            throw new RuntimeError('Variable "d" does not exist.', 1, $this->source);
        })()), 'icu:HH:mm:ss');
        craft\helpers\Template::endProfile('template', '__string_template__efe4b42648e98daad5b21fe724aa7a08');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__efe4b42648e98daad5b21fe724aa7a08';
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
        return new Source('{{ d|time("icu:HH:mm:ss") }}', '__string_template__efe4b42648e98daad5b21fe724aa7a08', '');
    }
}
