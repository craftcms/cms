<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__0a24ef2567079e13d4ddb9adb76f8947 */
class __TwigTemplate_e71ffb390a1da75fd3d2d4ba0618015c extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__0a24ef2567079e13d4ddb9adb76f8947');
        // line 1
        yield craft\helpers\Html::actionInput('A URL WITH CHARS !@#$%^&*()😋');
        craft\helpers\Template::endProfile('template', '__string_template__0a24ef2567079e13d4ddb9adb76f8947');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__0a24ef2567079e13d4ddb9adb76f8947';
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
        return new Source('{{ actionInput("A URL WITH CHARS !@#$%^&*()😋") }}', '__string_template__0a24ef2567079e13d4ddb9adb76f8947', '');
    }
}
