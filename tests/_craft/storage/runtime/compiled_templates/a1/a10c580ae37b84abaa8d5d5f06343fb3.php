<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__48c8b474a7ef078b1cee3aecdfa4364e */
class __TwigTemplate_8d830e453ee9213f4b2228a006c5ca5c extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__48c8b474a7ef078b1cee3aecdfa4364e');
        // line 1
        yield Twig\Extension\CoreExtension::join($this->extensions['craft\web\twig\Extension']->filterFilter($this->env, ['foo', 'bar', 'baz'], function ($__i__) use ($context) {
            $context['i'] = $__i__;

            return (isset($context['i']) || array_key_exists('i', $context) ? $context['i'] : (function () {
                throw new RuntimeError('Variable "i" does not exist.', 1, $this->source);
            })()) != 'baz';
        }), ' ');
        craft\helpers\Template::endProfile('template', '__string_template__48c8b474a7ef078b1cee3aecdfa4364e');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__48c8b474a7ef078b1cee3aecdfa4364e';
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
        return new Source('{{ ["foo", "bar", "baz"]|filter(i => i != "baz")|join(" ") }}', '__string_template__48c8b474a7ef078b1cee3aecdfa4364e', '');
    }
}
