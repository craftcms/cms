<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__54651f9a9c737e95cf15868c5aa0a444 */
class __TwigTemplate_7ec6897d4cadeb0094ba9667af24a7ec extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__54651f9a9c737e95cf15868c5aa0a444');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->purifyFilter('<p bad-attr="bad-value">foo</p>');
        craft\helpers\Template::endProfile('template', '__string_template__54651f9a9c737e95cf15868c5aa0a444');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__54651f9a9c737e95cf15868c5aa0a444';
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
        return new Source("{{ '<p bad-attr=\"bad-value\">foo</p>'|purify }}", '__string_template__54651f9a9c737e95cf15868c5aa0a444', '');
    }
}
