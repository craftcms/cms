<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__fe8963dc71a2948dddc2ae0418d1f690 */
class __TwigTemplate_7be7b420098ee401485301b536286490 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__fe8963dc71a2948dddc2ae0418d1f690');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->translateFilter('Source message', 'site');
        craft\helpers\Template::endProfile('template', '__string_template__fe8963dc71a2948dddc2ae0418d1f690');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__fe8963dc71a2948dddc2ae0418d1f690';
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
        return new Source('{{ "Source message"|t("site") }}', '__string_template__fe8963dc71a2948dddc2ae0418d1f690', '');
    }
}
