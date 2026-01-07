<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__215ed6dcfb3aec4dc1a718dbd7ed7ca4 */
class __TwigTemplate_a72053289ffc3e73142fd85bc3e3d145 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__215ed6dcfb3aec4dc1a718dbd7ed7ca4');
        // line 1
        $context['q2'] = $this->extensions['craft\web\twig\Extension']->cloneFunction((isset($context['q']) || array_key_exists('q', $context) ? $context['q'] : (function () {
            throw new RuntimeError('Variable "q" does not exist.', 1, $this->source);
        })()));
        yield (((craft\helpers\Template::attribute($this->env, $this->source, (isset($context['q2']) || array_key_exists('q2', $context) ? $context['q2'] : (function () {
            throw new RuntimeError('Variable "q2" does not exist.', 1, $this->source);
        })()), 'sectionId', [], 'any', false, false, false, 1) == craft\helpers\Template::attribute($this->env, $this->source, (isset($context['q']) || array_key_exists('q', $context) ? $context['q'] : (function () {
            throw new RuntimeError('Variable "q" does not exist.', 1, $this->source);
        })()), 'sectionId', [], 'any', false, false, false, 1)) && ! ((isset($context['q2']) || array_key_exists('q2', $context) ? $context['q2'] : (function () {
            throw new RuntimeError('Variable "q2" does not exist.', 1, $this->source);
        })()) === (isset($context['q']) || array_key_exists('q', $context) ? $context['q'] : (function () {
            throw new RuntimeError('Variable "q" does not exist.', 1, $this->source);
        })())))) ? ('yes') : ('no');
        craft\helpers\Template::endProfile('template', '__string_template__215ed6dcfb3aec4dc1a718dbd7ed7ca4');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__215ed6dcfb3aec4dc1a718dbd7ed7ca4';
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
        return new Source('{% set q2 = clone(q) %}{{ q2.sectionId == q.sectionId and q2 is not same as(q) ? "yes" : "no" }}', '__string_template__215ed6dcfb3aec4dc1a718dbd7ed7ca4', '');
    }
}
