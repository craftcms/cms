<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__11da3a29037169f5133c763bb2011c7b */
class __TwigTemplate_f42828a480b339c95789ed9a8d8b684d extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__11da3a29037169f5133c763bb2011c7b');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->jsonEncodeFilter($this->extensions['craft\web\twig\Extension']->unshiftFilter(['baz'], 'foo', 'bar'));
        craft\helpers\Template::endProfile('template', '__string_template__11da3a29037169f5133c763bb2011c7b');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__11da3a29037169f5133c763bb2011c7b';
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
        return new Source('{{ ["baz"]|unshift("foo", "bar")|json_encode }}', '__string_template__11da3a29037169f5133c763bb2011c7b', '');
    }
}
