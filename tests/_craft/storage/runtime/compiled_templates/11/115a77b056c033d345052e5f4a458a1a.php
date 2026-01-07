<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__fd4f83460730af830e03599a58df1ffc */
class __TwigTemplate_09532defdcdc7879caac65a0899f823f extends Template
{
    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('template', '__string_template__fd4f83460730af830e03599a58df1ffc');
        // line 1
        yield (($this->extensions['craft\web\twig\Extension']->pluginFunction('no-a-real-plugin') === null)) ? ('invalid') : ('');
        craft\helpers\Template::endProfile('template', '__string_template__fd4f83460730af830e03599a58df1ffc');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__fd4f83460730af830e03599a58df1ffc';
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
        return new Source('{{ plugin("no-a-real-plugin") is same as(null) ? "invalid" }}', '__string_template__fd4f83460730af830e03599a58df1ffc', '');
    }
}
