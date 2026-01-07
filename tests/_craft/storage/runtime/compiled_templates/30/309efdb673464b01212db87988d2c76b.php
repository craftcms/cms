<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__dfe2b52465ec487a1b9bb6ef903c5268 */
class __TwigTemplate_5511ee7cd47fddffed45e955d12467da extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__dfe2b52465ec487a1b9bb6ef903c5268');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->datetimeFilter($this->env, (isset($context['d']) || array_key_exists('d', $context) ? $context['d'] : (function () {
            throw new RuntimeError('Variable "d" does not exist.', 1, $this->source);
        })()), 'php:Y-m-d h:i:s');
        craft\helpers\Template::endProfile('template', '__string_template__dfe2b52465ec487a1b9bb6ef903c5268');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__dfe2b52465ec487a1b9bb6ef903c5268';
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
        return new Source('{{ d|datetime("php:Y-m-d h:i:s") }}', '__string_template__dfe2b52465ec487a1b9bb6ef903c5268', '');
    }
}
