<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__0e993abc2a8fe0a1bcb766f6d024d7af */
class __TwigTemplate_1369f6ed96b3fc6c87772d2a4745ec48 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__0e993abc2a8fe0a1bcb766f6d024d7af');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->dateFilter($this->env, (isset($context['d']) || array_key_exists('d', $context) ? $context['d'] : (function () {
            throw new RuntimeError('Variable "d" does not exist.', 1, $this->source);
        })()), 'Y-m-d');
        craft\helpers\Template::endProfile('template', '__string_template__0e993abc2a8fe0a1bcb766f6d024d7af');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__0e993abc2a8fe0a1bcb766f6d024d7af';
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
        return new Source('{{ d|date("Y-m-d") }}', '__string_template__0e993abc2a8fe0a1bcb766f6d024d7af', '');
    }
}
