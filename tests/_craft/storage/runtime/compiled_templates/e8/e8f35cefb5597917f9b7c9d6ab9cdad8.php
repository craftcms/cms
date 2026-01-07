<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__ad7517b1647c8ac809588f218b3317f5 */
class __TwigTemplate_8e7d7e810ae61a298d284db47ac0b6dc extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__ad7517b1647c8ac809588f218b3317f5');
        // line 1
        yield Twig\Extension\CoreExtension::join($this->extensions['craft\web\twig\Extension']->withoutKeyFilter(['a' => 'foo', 'b' => 'bar', 'c' => 'baz'], ['b', 'c']), ',');
        craft\helpers\Template::endProfile('template', '__string_template__ad7517b1647c8ac809588f218b3317f5');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__ad7517b1647c8ac809588f218b3317f5';
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
        return new Source('{{ {a:"foo",b:"bar",c:"baz"}|withoutKey(["b","c"])|join(",") }}', '__string_template__ad7517b1647c8ac809588f218b3317f5', '');
    }
}
