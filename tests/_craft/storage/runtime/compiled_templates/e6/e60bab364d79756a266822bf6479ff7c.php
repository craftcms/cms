<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__6434ffa50fb41992da9a01f1099a9d41 */
class __TwigTemplate_6ccbeebc1c15bf25e2bdc3f5e1224d56 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__6434ffa50fb41992da9a01f1099a9d41');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->encencFilter('foo');
        craft\helpers\Template::endProfile('template', '__string_template__6434ffa50fb41992da9a01f1099a9d41');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__6434ffa50fb41992da9a01f1099a9d41';
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
        return new Source('{{ "foo"|encenc }}', '__string_template__6434ffa50fb41992da9a01f1099a9d41', '');
    }
}
