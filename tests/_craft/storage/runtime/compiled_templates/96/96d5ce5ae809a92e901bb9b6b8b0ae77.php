<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* _includes/fallback-icon.svg.twig */
class __TwigTemplate_5bc04e86b0f3da76edeec6eb091d38e2 extends Template
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
        craft\helpers\Template::beginProfile('template', '_includes/fallback-icon.svg.twig');
        // line 1
        yield '<svg version="1.1" baseProfile="full" width="20" height="20" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
    <title>';
        // line 2
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['label']) || array_key_exists('label', $context) ? $context['label'] : (function () {
            throw new RuntimeError('Variable "label" does not exist.', 2, $this->source);
        })()), 'html', null, true);
        yield '</title>
    <circle cx="10" cy="10" r="10" fill="#000" fill-opacity="0.35"/>
    <text x="10" y="15" font-size="15" font-family="sans-serif" font-weight="bold" text-anchor="middle" fill="#000">';
        // line 4
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(Twig\Extension\CoreExtension::upper($this->env->getCharset(), Twig\Extension\CoreExtension::slice($this->env->getCharset(), (isset($context['label']) || array_key_exists('label', $context) ? $context['label'] : (function () {
            throw new RuntimeError('Variable "label" does not exist.', 4, $this->source);
        })()), 0, 1)), 'html', null, true);
        yield '</text>
</svg>
';
        craft\helpers\Template::endProfile('template', '_includes/fallback-icon.svg.twig');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '_includes/fallback-icon.svg.twig';
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
        return [51 => 4,  46 => 2,  43 => 1];
    }

    public function getSourceContext(): Source
    {
        return new Source('<svg version="1.1" baseProfile="full" width="20" height="20" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
    <title>{{ label }}</title>
    <circle cx="10" cy="10" r="10" fill="#000" fill-opacity="0.35"/>
    <text x="10" y="15" font-size="15" font-family="sans-serif" font-weight="bold" text-anchor="middle" fill="#000">{{ label[0:1]|upper }}</text>
</svg>
', '_includes/fallback-icon.svg.twig', '/tmp/packages/craft5/src/templates/_includes/fallback-icon.svg.twig');
    }
}
