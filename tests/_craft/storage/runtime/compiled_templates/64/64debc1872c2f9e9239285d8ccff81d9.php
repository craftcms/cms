<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__28e2acdb19e26ef527d97f2c64d43665 */
class __TwigTemplate_84e08944eb9b726b91fca0220bd9c82c extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__28e2acdb19e26ef527d97f2c64d43665');
        // line 1
        yield 'some-uri/';
        yield ((craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'slug', [], 'any', true, true, false, 1) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'slug', [], 'any', false, false, false, 1) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'slug', [], 'any', false, false, false, 1)) : (craft\helpers\Template::attribute($this->env, $this->source, ($context['object'] ?? null), 'slug', [], 'any', false, false, false, 1));
        craft\helpers\Template::endProfile('template', '__string_template__28e2acdb19e26ef527d97f2c64d43665');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__28e2acdb19e26ef527d97f2c64d43665';
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
        return new Source('some-uri/{{ (_variables.slug ?? object.slug)|raw }}', '__string_template__28e2acdb19e26ef527d97f2c64d43665', '');
    }
}
