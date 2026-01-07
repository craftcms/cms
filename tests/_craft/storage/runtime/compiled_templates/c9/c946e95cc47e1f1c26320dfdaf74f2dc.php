<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__cc1f49cbb5ab76f6aa6efad9548cde79 */
class __TwigTemplate_275c777941a64a00a0fb8bf402fad083 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__cc1f49cbb5ab76f6aa6efad9548cde79');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->svgFunction((isset($context['contents']) || array_key_exists('contents', $context) ? $context['contents'] : (function () {
            throw new RuntimeError('Variable "contents" does not exist.', 1, $this->source);
        })()), null, null, 'foobar');
        craft\helpers\Template::endProfile('template', '__string_template__cc1f49cbb5ab76f6aa6efad9548cde79');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__cc1f49cbb5ab76f6aa6efad9548cde79';
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
        return new Source('{{ svg(contents, class="foobar") }}', '__string_template__cc1f49cbb5ab76f6aa6efad9548cde79', '');
    }
}
