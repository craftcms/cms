<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__b512df1cfcd5e613033cad0039b2ab28 */
class __TwigTemplate_b5b47520188d6f7fdb4c8b6263ee2543 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__b512df1cfcd5e613033cad0039b2ab28');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->httpdateFilter($this->env, (isset($context['d']) || array_key_exists('d', $context) ? $context['d'] : (function () {
            throw new RuntimeError('Variable "d" does not exist.', 1, $this->source);
        })()));
        craft\helpers\Template::endProfile('template', '__string_template__b512df1cfcd5e613033cad0039b2ab28');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__b512df1cfcd5e613033cad0039b2ab28';
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
        return new Source('{{ d|httpdate }}', '__string_template__b512df1cfcd5e613033cad0039b2ab28', '');
    }
}
