<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__297b84b057d19eda851899e028cdfa88 */
class __TwigTemplate_eeda4490d2d90d936696f7f3d22460a7 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__297b84b057d19eda851899e028cdfa88');
        // line 1
        yield 'different-uri/';
        yield ((craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'slug', [], 'any', true, true, false, 1) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'slug', [], 'any', false, false, false, 1) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'slug', [], 'any', false, false, false, 1)) : (craft\helpers\Template::attribute($this->env, $this->source, ($context['object'] ?? null), 'slug', [], 'any', false, false, false, 1));
        craft\helpers\Template::endProfile('template', '__string_template__297b84b057d19eda851899e028cdfa88');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__297b84b057d19eda851899e028cdfa88';
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
        return new Source('different-uri/{{ (_variables.slug ?? object.slug)|raw }}', '__string_template__297b84b057d19eda851899e028cdfa88', '');
    }
}
