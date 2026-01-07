<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__9fea54987b0ee6420c82a325bd9808f9 */
class __TwigTemplate_97eb6ca58f12240bb2727dffe3e05590 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__9fea54987b0ee6420c82a325bd9808f9');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->numberFilter(1000, 2);
        craft\helpers\Template::endProfile('template', '__string_template__9fea54987b0ee6420c82a325bd9808f9');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__9fea54987b0ee6420c82a325bd9808f9';
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
        return new Source('{{ 1000|number(decimals=2) }}', '__string_template__9fea54987b0ee6420c82a325bd9808f9', '');
    }
}
