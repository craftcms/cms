<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__ed39a993b3cf12df1bdffa52d1651a76 */
class __TwigTemplate_ad0ba79a53358761f5868548cb4ecd6c extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__ed39a993b3cf12df1bdffa52d1651a76');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->jsonEncodeFilter($this->extensions['craft\web\twig\Extension']->mergeFilter(['f' => 'foo', 'b' => ['bar']], ['b' => ['baz']]));
        craft\helpers\Template::endProfile('template', '__string_template__ed39a993b3cf12df1bdffa52d1651a76');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__ed39a993b3cf12df1bdffa52d1651a76';
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
        return new Source('{{ {f: "foo", b: ["bar"]}|merge({b: ["baz"]})|json_encode }}', '__string_template__ed39a993b3cf12df1bdffa52d1651a76', '');
    }
}
