<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__4f60c4785a42669631623c4422c42daf */
class __TwigTemplate_b88cf6172ea268a0455a10bace8600ed extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__4f60c4785a42669631623c4422c42daf');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->percentageFilter(null);
        craft\helpers\Template::endProfile('template', '__string_template__4f60c4785a42669631623c4422c42daf');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__4f60c4785a42669631623c4422c42daf';
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
        return new Source('{{ null|percentage }}', '__string_template__4f60c4785a42669631623c4422c42daf', '');
    }
}
