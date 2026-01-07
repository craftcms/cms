<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__95b8adfd3060e800daec6ff87f850525 */
class __TwigTemplate_89a57abd2a2cc3b7cc75bb9914cf3dca extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__95b8adfd3060e800daec6ff87f850525');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->filesizeFilter(1000);
        craft\helpers\Template::endProfile('template', '__string_template__95b8adfd3060e800daec6ff87f850525');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__95b8adfd3060e800daec6ff87f850525';
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
        return new Source('{{ 1000|filesize }}', '__string_template__95b8adfd3060e800daec6ff87f850525', '');
    }
}
