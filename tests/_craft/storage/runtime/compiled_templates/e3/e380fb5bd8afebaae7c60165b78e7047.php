<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__1f7b9b1a44c367b20a102f8399b1370e */
class __TwigTemplate_7a66a6f3512048c75ea58f38f6f1763a extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__1f7b9b1a44c367b20a102f8399b1370e');
        // line 1
        yield Twig\Extension\CoreExtension::join(craft\helpers\ArrayHelper::getColumn($this->extensions['craft\web\twig\Extension']->multisortFilter([['k' => 'foo'], ['k' => 'bar'], ['k' => 'baz']], 'k'), 'k'), ' ');
        craft\helpers\Template::endProfile('template', '__string_template__1f7b9b1a44c367b20a102f8399b1370e');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__1f7b9b1a44c367b20a102f8399b1370e';
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
        return new Source('{{ [{k:"foo"},{k:"bar"},{k:"baz"}]|multisort("k")|column("k")|join(" ") }}', '__string_template__1f7b9b1a44c367b20a102f8399b1370e', '');
    }
}
