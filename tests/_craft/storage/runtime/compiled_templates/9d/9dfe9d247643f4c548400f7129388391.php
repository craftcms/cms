<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__b7a33b00616a41c1995277504053b8da */
class __TwigTemplate_2b02637ffc46037b9824b9c88b7d0ef0 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__b7a33b00616a41c1995277504053b8da');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->percentageFilter('foo');
        craft\helpers\Template::endProfile('template', '__string_template__b7a33b00616a41c1995277504053b8da');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__b7a33b00616a41c1995277504053b8da';
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
        return new Source('{{ "foo"|percentage }}', '__string_template__b7a33b00616a41c1995277504053b8da', '');
    }
}
