<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__74b92a79c5385b76e43188d07275e3d2 */
class __TwigTemplate_d931b94483747d622b91072e82064c17 extends Template
{
    private readonly Source $source;

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('template', '__string_template__74b92a79c5385b76e43188d07275e3d2');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->svgFunction((isset($context['contents']) || array_key_exists('contents', $context) ? $context['contents'] : (function () {
            throw new RuntimeError('Variable "contents" does not exist.', 1, $this->source);
        })()));
        craft\helpers\Template::endProfile('template', '__string_template__74b92a79c5385b76e43188d07275e3d2');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__74b92a79c5385b76e43188d07275e3d2';
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
        return new Source('{{ svg(contents) }}', '__string_template__74b92a79c5385b76e43188d07275e3d2', '');
    }
}
