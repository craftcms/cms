<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__9c9436dfa46c5ed56a61f798f74de1c7 */
class __TwigTemplate_4c816a59052eff433d660f162b071e15 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__9c9436dfa46c5ed56a61f798f74de1c7');
        // line 1
        yield Twig\Extension\CoreExtension::join([(isset($context['CraftSolo']) || array_key_exists('CraftSolo', $context) ? $context['CraftSolo'] : (function () {
            throw new RuntimeError('Variable "CraftSolo" does not exist.', 1, $this->source);
        })()), (isset($context['CraftTeam']) || array_key_exists('CraftTeam', $context) ? $context['CraftTeam'] : (function () {
            throw new RuntimeError('Variable "CraftTeam" does not exist.', 1, $this->source);
        })()), (isset($context['CraftPro']) || array_key_exists('CraftPro', $context) ? $context['CraftPro'] : (function () {
            throw new RuntimeError('Variable "CraftPro" does not exist.', 1, $this->source);
        })())], ',');
        craft\helpers\Template::endProfile('template', '__string_template__9c9436dfa46c5ed56a61f798f74de1c7');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__9c9436dfa46c5ed56a61f798f74de1c7';
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
        return new Source('{{ [CraftSolo, CraftTeam, CraftPro]|join(",") }}', '__string_template__9c9436dfa46c5ed56a61f798f74de1c7', '');
    }
}
