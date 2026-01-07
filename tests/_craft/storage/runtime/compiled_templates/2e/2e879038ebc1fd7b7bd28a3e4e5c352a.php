<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__19c9506db3b99a800d20277359994159 */
class __TwigTemplate_87ef06b32519796c95734e5f20e3d0ee extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__19c9506db3b99a800d20277359994159');
        // line 1
        yield craft\helpers\App::parseEnv('$FROM_EMAIL_NAME');
        craft\helpers\Template::endProfile('template', '__string_template__19c9506db3b99a800d20277359994159');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__19c9506db3b99a800d20277359994159';
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
        return new Source('{{ parseEnv("$FROM_EMAIL_NAME") }}', '__string_template__19c9506db3b99a800d20277359994159', '');
    }
}
