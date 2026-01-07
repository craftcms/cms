<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__bf9e84c229de9bca6c400bc852f92aa9 */
class __TwigTemplate_f9a417cb9c9379f56326c6f0ed0eac06 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__bf9e84c229de9bca6c400bc852f92aa9');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->datetimeFilter($this->env, (isset($context['d']) || array_key_exists('d', $context) ? $context['d'] : (function () {
            throw new RuntimeError('Variable "d" does not exist.', 1, $this->source);
        })()), 'icu:YYYY-MM-dd HH:mm:ss');
        craft\helpers\Template::endProfile('template', '__string_template__bf9e84c229de9bca6c400bc852f92aa9');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__bf9e84c229de9bca6c400bc852f92aa9';
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
        return new Source('{{ d|datetime("icu:YYYY-MM-dd HH:mm:ss") }}', '__string_template__bf9e84c229de9bca6c400bc852f92aa9', '');
    }
}
