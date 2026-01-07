<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__8de31afd956555f12fd8a9fee569962b */
class __TwigTemplate_7d838109d847b164467d8110ed24527d extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__8de31afd956555f12fd8a9fee569962b');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->numberFilter(null);
        craft\helpers\Template::endProfile('template', '__string_template__8de31afd956555f12fd8a9fee569962b');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__8de31afd956555f12fd8a9fee569962b';
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
        return new Source('{{ null|number }}', '__string_template__8de31afd956555f12fd8a9fee569962b', '');
    }
}
