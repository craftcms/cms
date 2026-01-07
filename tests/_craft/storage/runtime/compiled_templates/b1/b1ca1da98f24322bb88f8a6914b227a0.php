<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__cd8ed90f34bb6981b9d9b6e7dc9a737b */
class __TwigTemplate_156e0bf4e75ff837dfddba73a5f75840 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__cd8ed90f34bb6981b9d9b6e7dc9a737b');
        // line 1
        yield craft\helpers\App::parseEnv('FROM_EMAIL_NAME');
        craft\helpers\Template::endProfile('template', '__string_template__cd8ed90f34bb6981b9d9b6e7dc9a737b');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__cd8ed90f34bb6981b9d9b6e7dc9a737b';
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
        return new Source('{{ parseEnv("FROM_EMAIL_NAME") }}', '__string_template__cd8ed90f34bb6981b9d9b6e7dc9a737b', '');
    }
}
