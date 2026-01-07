<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__22ad0f5043cf61ff4d35c5cb4a134ae6 */
class __TwigTemplate_c4a5e84c514eb05bf328a496a38231b6 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__22ad0f5043cf61ff4d35c5cb4a134ae6');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->ucfirstFilter('foo bar');
        craft\helpers\Template::endProfile('template', '__string_template__22ad0f5043cf61ff4d35c5cb4a134ae6');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__22ad0f5043cf61ff4d35c5cb4a134ae6';
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
        return new Source('{{ "foo bar"|ucfirst }}', '__string_template__22ad0f5043cf61ff4d35c5cb4a134ae6', '');
    }
}
