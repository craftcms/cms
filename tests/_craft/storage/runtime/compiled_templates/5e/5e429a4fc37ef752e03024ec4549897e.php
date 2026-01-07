<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__a2970a215e231d1347ef31a53ab2b1b4 */
class __TwigTemplate_d7dbc1e2d71a50b49bde6700cb08b5ab extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__a2970a215e231d1347ef31a53ab2b1b4');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->truncateFilter('', 8);
        craft\helpers\Template::endProfile('template', '__string_template__a2970a215e231d1347ef31a53ab2b1b4');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__a2970a215e231d1347ef31a53ab2b1b4';
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
        return new Source('{{ ""|truncate(8) }}', '__string_template__a2970a215e231d1347ef31a53ab2b1b4', '');
    }
}
