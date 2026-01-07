<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__2252c80dd66c19e78c44e4b072cb7aa4 */
class __TwigTemplate_d1463d5513ac43dae322743a9079a619 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__2252c80dd66c19e78c44e4b072cb7aa4');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->translateFilter('Source message', 'invalidCategory');
        craft\helpers\Template::endProfile('template', '__string_template__2252c80dd66c19e78c44e4b072cb7aa4');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__2252c80dd66c19e78c44e4b072cb7aa4';
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
        return new Source('{{ "Source message"|t("invalidCategory") }}', '__string_template__2252c80dd66c19e78c44e4b072cb7aa4', '');
    }
}
