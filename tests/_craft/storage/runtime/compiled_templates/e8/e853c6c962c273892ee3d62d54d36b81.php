<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__60caae63c30221334b0c9dcef73ec8af */
class __TwigTemplate_8796977546125c3f82e1e41b989b0552 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__60caae63c30221334b0c9dcef73ec8af');
        // line 1
        yield Twig\Extension\CoreExtension::join($this->extensions['craft\web\twig\Extension']->filterFilter($this->env, ['foo', '', 'bar', '', 'baz']), ' ');
        craft\helpers\Template::endProfile('template', '__string_template__60caae63c30221334b0c9dcef73ec8af');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__60caae63c30221334b0c9dcef73ec8af';
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
        return new Source('{{ ["foo", "", "bar", "", "baz"]|filter|join(" ") }}', '__string_template__60caae63c30221334b0c9dcef73ec8af', '');
    }
}
