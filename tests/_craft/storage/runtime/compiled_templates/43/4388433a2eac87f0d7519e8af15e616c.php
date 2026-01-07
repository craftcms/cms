<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__3a6699f57e72b7f59509bc38b8b25a7f */
class __TwigTemplate_59b4ec53a11ff78db36e1c073037a357 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__3a6699f57e72b7f59509bc38b8b25a7f');
        // line 1
        yield craft\helpers\Html::csrfInput();
        craft\helpers\Template::endProfile('template', '__string_template__3a6699f57e72b7f59509bc38b8b25a7f');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__3a6699f57e72b7f59509bc38b8b25a7f';
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
        return new Source('{{ csrfInput() }}', '__string_template__3a6699f57e72b7f59509bc38b8b25a7f', '');
    }
}
