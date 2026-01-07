<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* _layouts/components/header-photo.twig */
class __TwigTemplate_79ed1b8cc0524c0af2c9716d4318c63b extends Template
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
        craft\helpers\Template::beginProfile('template', '_layouts/components/header-photo.twig');
        // line 1
        yield '<div class="header-photo">
  ';
        // line 2
        yield craft\helpers\Template::attribute($this->env, $this->source, (isset($context['currentUser']) || array_key_exists('currentUser', $context) ? $context['currentUser'] : (function () {
            throw new RuntimeError('Variable "currentUser" does not exist.', 2, $this->source);
        })()), 'getThumbHtml', [30], 'method', false, false, false, 2);
        yield '
</div>
';
        craft\helpers\Template::endProfile('template', '_layouts/components/header-photo.twig');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '_layouts/components/header-photo.twig';
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
        return new Source('<div class="header-photo">
  {{ currentUser.getThumbHtml(30)|raw }}
</div>
', '_layouts/components/header-photo.twig', '/tmp/packages/craft5/src/templates/_layouts/components/header-photo.twig');
    }
}
