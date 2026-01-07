<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__7a610aedeab7f50dc5ba41df95be8c44 */
class __TwigTemplate_8d3f3231447dc6f53d600d19ebe78903 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__7a610aedeab7f50dc5ba41df95be8c44');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->replaceFilter('/foo/bar/', ['/foo/' => 'baz'], null, false);
        craft\helpers\Template::endProfile('template', '__string_template__7a610aedeab7f50dc5ba41df95be8c44');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__7a610aedeab7f50dc5ba41df95be8c44';
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
        return new Source('{{ "/foo/bar/"|replace({"/foo/": "baz"}, regex=false) }}', '__string_template__7a610aedeab7f50dc5ba41df95be8c44', '');
    }
}
