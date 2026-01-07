<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__648910db5a1f3541732e894594b9f9c8 */
class __TwigTemplate_96ed5c40cbbdeb02d11a97fe60b3cf7d extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__648910db5a1f3541732e894594b9f9c8');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->fieldValueSqlFunction($this->extensions['craft\web\twig\Extension']->entryTypeFunction('test1'), 'plainTextField');
        craft\helpers\Template::endProfile('template', '__string_template__648910db5a1f3541732e894594b9f9c8');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__648910db5a1f3541732e894594b9f9c8';
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
        return new Source("{{ fieldValueSql(entryType('test1'), 'plainTextField') }}", '__string_template__648910db5a1f3541732e894594b9f9c8', '');
    }
}
