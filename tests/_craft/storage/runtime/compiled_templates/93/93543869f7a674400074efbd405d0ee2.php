<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__f6ffdc7bf1df3973435470ee15e1dc96 */
class __TwigTemplate_cff1ec2bd20e75303594420e4fc511f1 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__f6ffdc7bf1df3973435470ee15e1dc96');
        // line 1
        yield ((craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'username', [], 'any', true, true, false, 1) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'username', [], 'any', false, false, false, 1) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'username', [], 'any', false, false, false, 1)) : (craft\helpers\Template::attribute($this->env, $this->source, ($context['object'] ?? null), 'username', [], 'any', false, false, false, 1));
        craft\helpers\Template::endProfile('template', '__string_template__f6ffdc7bf1df3973435470ee15e1dc96');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__f6ffdc7bf1df3973435470ee15e1dc96';
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
        return new Source('{{ (_variables.username ?? object.username)|raw }}', '__string_template__f6ffdc7bf1df3973435470ee15e1dc96', '');
    }
}
