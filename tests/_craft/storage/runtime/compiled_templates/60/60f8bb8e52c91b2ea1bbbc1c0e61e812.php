<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__79d084889c1b890eaeccbd6d037b64eb */
class __TwigTemplate_84554eaf98bceb9185e8a97792a94c28 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__79d084889c1b890eaeccbd6d037b64eb');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->jsonEncodeFilter($this->extensions['craft\web\twig\Extension']->mergeFilter(['f' => 'foo', 'b' => ['bar']], ['b' => ['baz']], true));
        craft\helpers\Template::endProfile('template', '__string_template__79d084889c1b890eaeccbd6d037b64eb');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__79d084889c1b890eaeccbd6d037b64eb';
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
        return new Source('{{ {f: "foo", b: ["bar"]}|merge({b: ["baz"]}, recursive=true)|json_encode }}', '__string_template__79d084889c1b890eaeccbd6d037b64eb', '');
    }
}
