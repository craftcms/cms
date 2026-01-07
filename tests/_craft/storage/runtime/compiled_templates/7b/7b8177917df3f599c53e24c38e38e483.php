<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__e542a6b1cdfb78df9705502f93389c34 */
class __TwigTemplate_ae43821e48300d5bb55b54d2884f6f66 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__e542a6b1cdfb78df9705502f93389c34');
        // line 1
        yield isset($context['aGlobalSet']) || array_key_exists('aGlobalSet', $context) ? $context['aGlobalSet'] : (function () {
            throw new RuntimeError('Variable "aGlobalSet" does not exist.', 1, $this->source);
        })();
        yield ' | ';
        yield isset($context['aDifferentGlobalSet']) || array_key_exists('aDifferentGlobalSet', $context) ? $context['aDifferentGlobalSet'] : (function () {
            throw new RuntimeError('Variable "aDifferentGlobalSet" does not exist.', 1, $this->source);
        })();
        craft\helpers\Template::endProfile('template', '__string_template__e542a6b1cdfb78df9705502f93389c34');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__e542a6b1cdfb78df9705502f93389c34';
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
        return new Source('{{ aGlobalSet }} | {{ aDifferentGlobalSet }}', '__string_template__e542a6b1cdfb78df9705502f93389c34', '');
    }
}
