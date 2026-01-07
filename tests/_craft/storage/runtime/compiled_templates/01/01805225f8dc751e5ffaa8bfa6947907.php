<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__8209d632d245e96352f91cf77d12420e */
class __TwigTemplate_8301f291da33ae309c806f7f9fd3c965 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__8209d632d245e96352f91cf77d12420e');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->replaceFilter('/foo/bar/', ['/foo/' => 'baz'], null, true);
        craft\helpers\Template::endProfile('template', '__string_template__8209d632d245e96352f91cf77d12420e');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__8209d632d245e96352f91cf77d12420e';
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
        return new Source('{{ "/foo/bar/"|replace({"/foo/": "baz"}, regex=true) }}', '__string_template__8209d632d245e96352f91cf77d12420e', '');
    }
}
