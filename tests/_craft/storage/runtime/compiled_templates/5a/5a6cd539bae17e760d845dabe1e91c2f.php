<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__fbe6b597421323ac1fd4486cf1a0909f */
class __TwigTemplate_327b93e5dbb411b841eefe7fdbfa3ba0 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__fbe6b597421323ac1fd4486cf1a0909f');
        // line 1
        yield ($this->env->getTest('missing')->getCallable()((isset($context['foo']) || array_key_exists('foo', $context) ? $context['foo'] : (function () {
            throw new RuntimeError('Variable "foo" does not exist.', 1, $this->source);
        })()))) ? ('yes') : ('no');
        craft\helpers\Template::endProfile('template', '__string_template__fbe6b597421323ac1fd4486cf1a0909f');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__fbe6b597421323ac1fd4486cf1a0909f';
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
        return new Source('{{ foo is missing ? "yes" : "no" }}', '__string_template__fbe6b597421323ac1fd4486cf1a0909f', '');
    }
}
