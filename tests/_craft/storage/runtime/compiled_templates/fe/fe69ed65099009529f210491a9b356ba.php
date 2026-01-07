<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__5dfd68c47647aaaeebfe95b7c393af11 */
class __TwigTemplate_9a4c4fe6832710f91efba79ad07a1b55 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__5dfd68c47647aaaeebfe95b7c393af11');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->translateFilter('Source message');
        craft\helpers\Template::endProfile('template', '__string_template__5dfd68c47647aaaeebfe95b7c393af11');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__5dfd68c47647aaaeebfe95b7c393af11';
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
        return new Source('{{ "Source message"|t }}', '__string_template__5dfd68c47647aaaeebfe95b7c393af11', '');
    }
}
