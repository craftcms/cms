<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__1d14c964180a029297b8eb43f13c44eb */
class __TwigTemplate_961e687e33fb2ae4af0a650e2e3ca9c1 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__1d14c964180a029297b8eb43f13c44eb');
        // line 1
        yield isset($context['systemName']) || array_key_exists('systemName', $context) ? $context['systemName'] : (function () {
            throw new RuntimeError('Variable "systemName" does not exist.', 1, $this->source);
        })();
        yield ' | ';
        yield craft\helpers\Template::attribute($this->env, $this->source, (isset($context['currentSite']) || array_key_exists('currentSite', $context) ? $context['currentSite'] : (function () {
            throw new RuntimeError('Variable "currentSite" does not exist.', 1, $this->source);
        })()), 'handle', [], 'any', false, false, false, 1);
        yield ' ';
        yield isset($context['currentSite']) || array_key_exists('currentSite', $context) ? $context['currentSite'] : (function () {
            throw new RuntimeError('Variable "currentSite" does not exist.', 1, $this->source);
        })();
        yield ' ';
        yield isset($context['siteUrl']) || array_key_exists('siteUrl', $context) ? $context['siteUrl'] : (function () {
            throw new RuntimeError('Variable "siteUrl" does not exist.', 1, $this->source);
        })();
        craft\helpers\Template::endProfile('template', '__string_template__1d14c964180a029297b8eb43f13c44eb');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__1d14c964180a029297b8eb43f13c44eb';
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
        return new Source('{{ systemName }} | {{ currentSite.handle }} {{ currentSite }} {{ siteUrl }}', '__string_template__1d14c964180a029297b8eb43f13c44eb', '');
    }
}
