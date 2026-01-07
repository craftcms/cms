<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__ffcf0d17f5166ee0f0d4b92e35c588f4 */
class __TwigTemplate_2ee89f923c6485721a4db37c750fa4fa extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__ffcf0d17f5166ee0f0d4b92e35c588f4');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->attrFilter('<p>Hey</p>', ['class' => 'foo']);
        craft\helpers\Template::endProfile('template', '__string_template__ffcf0d17f5166ee0f0d4b92e35c588f4');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__ffcf0d17f5166ee0f0d4b92e35c588f4';
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
        return new Source('{{ "<p>Hey</p>"|attr({class: "foo"}) }}', '__string_template__ffcf0d17f5166ee0f0d4b92e35c588f4', '');
    }
}
