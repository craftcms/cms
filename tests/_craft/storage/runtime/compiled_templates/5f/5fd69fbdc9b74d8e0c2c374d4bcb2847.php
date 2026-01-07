<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__ad004f80d2d29c661780f9887d04e1a3 */
class __TwigTemplate_f6aceec3f3b186ea3804c723d448378e extends Template
{
    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('template', '__string_template__ad004f80d2d29c661780f9887d04e1a3');
        // line 1
        yield craft\helpers\Html::redirectInput('A URL WITH CHARS !@#$%^*()😋');
        craft\helpers\Template::endProfile('template', '__string_template__ad004f80d2d29c661780f9887d04e1a3');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__ad004f80d2d29c661780f9887d04e1a3';
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
        return new Source('{{ redirectInput("A URL WITH CHARS !@#$%^*()😋") }}', '__string_template__ad004f80d2d29c661780f9887d04e1a3', '');
    }
}
