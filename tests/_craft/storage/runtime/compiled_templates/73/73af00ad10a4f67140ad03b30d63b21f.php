<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__6b46341588fb109523d6cef1bc0db880 */
class __TwigTemplate_42d215f15d354c2c9c485c9c1c03a291 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__6b46341588fb109523d6cef1bc0db880');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->tagFunction('p', ['text' => 'Hello', 'class' => 'foo']);
        craft\helpers\Template::endProfile('template', '__string_template__6b46341588fb109523d6cef1bc0db880');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__6b46341588fb109523d6cef1bc0db880';
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
        return new Source('{{ tag("p", {text: "Hello", class: "foo"}) }}', '__string_template__6b46341588fb109523d6cef1bc0db880', '');
    }
}
