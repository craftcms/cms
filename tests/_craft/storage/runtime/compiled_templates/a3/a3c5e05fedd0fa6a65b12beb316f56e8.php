<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__9956c5b969b3801f96827a0cfe63289b */
class __TwigTemplate_34fe7fd2522cac4d41f5199a44e0047f extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__9956c5b969b3801f96827a0cfe63289b');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->filesizeFilter(null);
        craft\helpers\Template::endProfile('template', '__string_template__9956c5b969b3801f96827a0cfe63289b');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__9956c5b969b3801f96827a0cfe63289b';
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
        return new Source('{{ null|filesize }}', '__string_template__9956c5b969b3801f96827a0cfe63289b', '');
    }
}
