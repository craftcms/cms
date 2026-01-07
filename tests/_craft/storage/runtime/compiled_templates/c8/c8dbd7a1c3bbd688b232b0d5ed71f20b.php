<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__8850fad039b57c44192c36bdd0ad3d9c */
class __TwigTemplate_e27084e5b04d2eb8f4c5af6a8ef8564b extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__8850fad039b57c44192c36bdd0ad3d9c');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->numberFilter(1000);
        craft\helpers\Template::endProfile('template', '__string_template__8850fad039b57c44192c36bdd0ad3d9c');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__8850fad039b57c44192c36bdd0ad3d9c';
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
        return new Source('{{ 1000|number }}', '__string_template__8850fad039b57c44192c36bdd0ad3d9c', '');
    }
}
