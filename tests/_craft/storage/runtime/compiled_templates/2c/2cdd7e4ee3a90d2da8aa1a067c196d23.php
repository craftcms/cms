<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__e5baa3cda4b41a484c8a3e37e971b8c2 */
class __TwigTemplate_ad76b80af73628427b9822bc012f1e4d extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__e5baa3cda4b41a484c8a3e37e971b8c2');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->addressFilter((isset($context['myAddress']) || array_key_exists('myAddress', $context) ? $context['myAddress'] : (function () {
            throw new RuntimeError('Variable "myAddress" does not exist.', 1, $this->source);
        })()));
        craft\helpers\Template::endProfile('template', '__string_template__e5baa3cda4b41a484c8a3e37e971b8c2');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__e5baa3cda4b41a484c8a3e37e971b8c2';
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
        return new Source('{{ myAddress|address }}', '__string_template__e5baa3cda4b41a484c8a3e37e971b8c2', '');
    }
}
