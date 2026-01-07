<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__38c67b506da7180906afed815c381a50 */
class __TwigTemplate_538c522efb66dc7e1855e2514ada3664 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__38c67b506da7180906afed815c381a50');
        // line 1
        yield isset($context['systemName']) || array_key_exists('systemName', $context) ? $context['systemName'] : (function () {
            throw new RuntimeError('Variable "systemName" does not exist.', 1, $this->source);
        })();
        yield ' | ';
        yield isset($context['currentSite']) || array_key_exists('currentSite', $context) ? $context['currentSite'] : (function () {
            throw new RuntimeError('Variable "currentSite" does not exist.', 1, $this->source);
        })();
        yield ' | ';
        yield isset($context['siteName']) || array_key_exists('siteName', $context) ? $context['siteName'] : (function () {
            throw new RuntimeError('Variable "siteName" does not exist.', 1, $this->source);
        })();
        yield ' | ';
        yield isset($context['siteUrl']) || array_key_exists('siteUrl', $context) ? $context['siteUrl'] : (function () {
            throw new RuntimeError('Variable "siteUrl" does not exist.', 1, $this->source);
        })();
        craft\helpers\Template::endProfile('template', '__string_template__38c67b506da7180906afed815c381a50');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__38c67b506da7180906afed815c381a50';
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
        return new Source('{{ systemName }} | {{ currentSite }} | {{ siteName }} | {{ siteUrl }}', '__string_template__38c67b506da7180906afed815c381a50', '');
    }
}
