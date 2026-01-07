<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* _elements/footer */
class __TwigTemplate_2b70d5a001983f03404bb4e560662368 extends Template
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
        craft\helpers\Template::beginProfile('template', '_elements/footer');
        // line 1
        yield '<div id="count-spinner" class="spinner small hidden"></div>
<div id="count-container" class="light">&nbsp;</div>
<div id="actions-container" class="flex"></div>
<div class="flex flex-nowrap">
  <button type="button" id="export-btn" class="btn hidden" aria-expanded="false">';
        // line 5
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Export…', 'app'), 'html', null, true);
        yield '</button>
</div>
';
        craft\helpers\Template::endProfile('template', '_elements/footer');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '_elements/footer';
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
        return [49 => 5,  43 => 1];
    }

    public function getSourceContext(): Source
    {
        return new Source("<div id=\"count-spinner\" class=\"spinner small hidden\"></div>
<div id=\"count-container\" class=\"light\">&nbsp;</div>
<div id=\"actions-container\" class=\"flex\"></div>
<div class=\"flex flex-nowrap\">
  <button type=\"button\" id=\"export-btn\" class=\"btn hidden\" aria-expanded=\"false\">{{ 'Export…'|t('app') }}</button>
</div>
", '_elements/footer', '/tmp/packages/craft5/src/templates/_elements/footer.twig');
    }
}
