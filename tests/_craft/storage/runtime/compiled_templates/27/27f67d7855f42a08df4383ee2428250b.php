<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* _components/utilities/Updates.twig */
class __TwigTemplate_a6f04712916148d7e56a4ded1ecbec6a extends Template
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
        craft\helpers\Template::beginProfile('template', '_components/utilities/Updates.twig');
        // line 1
        yield '<div id="graphic" class="spinner big"></div>
<div id="status">';
        // line 2
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Checking for updates…', 'app'), 'html', null, true);
        yield '</div>
';
        craft\helpers\Template::endProfile('template', '_components/utilities/Updates.twig');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '_components/utilities/Updates.twig';
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
        return [46 => 2,  43 => 1];
    }

    public function getSourceContext(): Source
    {
        return new Source("<div id=\"graphic\" class=\"spinner big\"></div>
<div id=\"status\">{{ \"Checking for updates…\"|t('app') }}</div>
", '_components/utilities/Updates.twig', '/tmp/packages/craft5/src/templates/_components/utilities/Updates.twig');
    }
}
