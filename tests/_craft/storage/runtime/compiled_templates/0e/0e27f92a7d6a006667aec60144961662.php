<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__b6b756dcc3e73146f07577de04a4fc98 */
class __TwigTemplate_4cc1957b5b21582730a4747cf62534d4 extends Template
{
    private readonly Source $source;

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('template', '__string_template__b6b756dcc3e73146f07577de04a4fc98');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->svgFunction((isset($context['contents']) || array_key_exists('contents', $context) ? $context['contents'] : (function () {
            throw new RuntimeError('Variable "contents" does not exist.', 1, $this->source);
        })()), null, false);
        craft\helpers\Template::endProfile('template', '__string_template__b6b756dcc3e73146f07577de04a4fc98');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__b6b756dcc3e73146f07577de04a4fc98';
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
        return new Source('{{ svg(contents, namespace=false) }}', '__string_template__b6b756dcc3e73146f07577de04a4fc98', '');
    }
}
