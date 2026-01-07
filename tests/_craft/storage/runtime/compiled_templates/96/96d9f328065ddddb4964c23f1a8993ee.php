<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* withvar */
class __TwigTemplate_1a4f7d7c6e13bfa1ae907f4cf2e3b5be extends Template
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
        craft\helpers\Template::beginProfile('template', 'withvar');
        // line 1
        yield 'Hello iam ';
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['name']) || array_key_exists('name', $context) ? $context['name'] : (function () {
            throw new RuntimeError('Variable "name" does not exist.', 1, $this->source);
        })()), 'html', null, true);
        craft\helpers\Template::endProfile('template', 'withvar');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return 'withvar';
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
        return new Source('Hello iam {{ name }}', 'withvar', '/tmp/packages/craft5/tests/_craft/templates/withvar.twig');
    }
}
