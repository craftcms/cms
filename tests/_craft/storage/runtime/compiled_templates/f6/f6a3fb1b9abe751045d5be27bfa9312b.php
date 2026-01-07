<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__4e83fcc8047a877726edf650070523fd */
class __TwigTemplate_8e63ef2dd8b7eb0294ea4d9fe7927fe4 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__4e83fcc8047a877726edf650070523fd');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->dataUrlFunction((isset($context['path']) || array_key_exists('path', $context) ? $context['path'] : (function () {
            throw new RuntimeError('Variable "path" does not exist.', 1, $this->source);
        })()));
        craft\helpers\Template::endProfile('template', '__string_template__4e83fcc8047a877726edf650070523fd');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__4e83fcc8047a877726edf650070523fd';
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
        return new Source('{{ dataUrl(path) }}', '__string_template__4e83fcc8047a877726edf650070523fd', '');
    }
}
