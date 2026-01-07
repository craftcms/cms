<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__5b344c7397f6ebeaaf184dc514d60b53 */
class __TwigTemplate_f9a7136b2cdf8eae994e9ab1d3ad5eae extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__5b344c7397f6ebeaaf184dc514d60b53');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->replaceFilter('foo bar baz', ['/b(\\w+)/' => 'z$1', 'zaz' => 'zazzy']);
        craft\helpers\Template::endProfile('template', '__string_template__5b344c7397f6ebeaaf184dc514d60b53');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__5b344c7397f6ebeaaf184dc514d60b53';
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
        return new Source('{{ "foo bar baz"|replace({"/b(\\\\w+)/": "z$1", zaz: "zazzy"}) }}', '__string_template__5b344c7397f6ebeaaf184dc514d60b53', '');
    }
}
