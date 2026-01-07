<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__37d2b74c929304b6aadbbe84f440130a */
class __TwigTemplate_023298ea46d277c9d3beaaf424b09164 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__37d2b74c929304b6aadbbe84f440130a');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->dateFilter($this->env, (isset($context['d']) || array_key_exists('d', $context) ? $context['d'] : (function () {
            throw new RuntimeError('Variable "d" does not exist.', 1, $this->source);
        })()), 'icu:YYYY-MM-dd');
        craft\helpers\Template::endProfile('template', '__string_template__37d2b74c929304b6aadbbe84f440130a');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__37d2b74c929304b6aadbbe84f440130a';
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
        return new Source('{{ d|date("icu:YYYY-MM-dd") }}', '__string_template__37d2b74c929304b6aadbbe84f440130a', '');
    }
}
