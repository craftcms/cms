<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__2493166636ea1d5fa5f2130064b3fd0f */
class __TwigTemplate_2db2644bb6da345cbfb9ea291b14dbd9 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__2493166636ea1d5fa5f2130064b3fd0f');
        // line 1
        yield Twig\Extension\CoreExtension::join($this->extensions['craft\web\twig\Extension']->withoutFilter(['foo', 'bar', 'baz'], 'baz'), ',');
        craft\helpers\Template::endProfile('template', '__string_template__2493166636ea1d5fa5f2130064b3fd0f');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__2493166636ea1d5fa5f2130064b3fd0f';
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
        return new Source('{{ ["foo","bar","baz"]|without("baz")|join(",") }}', '__string_template__2493166636ea1d5fa5f2130064b3fd0f', '');
    }
}
