<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__3b96a59f4dc2581a2dd2f5bc93e777f8 */
class __TwigTemplate_2f22e10cbc08ec577c7f0c549f51864c extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__3b96a59f4dc2581a2dd2f5bc93e777f8');
        // line 1
        yield base64_decode('Zm9v');
        craft\helpers\Template::endProfile('template', '__string_template__3b96a59f4dc2581a2dd2f5bc93e777f8');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__3b96a59f4dc2581a2dd2f5bc93e777f8';
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
        return new Source("{{ 'Zm9v'|base64_decode }}", '__string_template__3b96a59f4dc2581a2dd2f5bc93e777f8', '');
    }
}
