<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__9ba7a7b981bb6dec1843282c75f078d8 */
class __TwigTemplate_c4a35eb6f6f727d4d623ff3d01733dbd extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__9ba7a7b981bb6dec1843282c75f078d8');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->replaceFilter('foo bar baz', 'bar', 'qux');
        craft\helpers\Template::endProfile('template', '__string_template__9ba7a7b981bb6dec1843282c75f078d8');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__9ba7a7b981bb6dec1843282c75f078d8';
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
        return new Source('{{ "foo bar baz"|replace("bar", "qux") }}', '__string_template__9ba7a7b981bb6dec1843282c75f078d8', '');
    }
}
