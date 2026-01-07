<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* event-tags */
class __TwigTemplate_b4c102feef2dc456c907378c7570c681 extends Template
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
        craft\helpers\Template::beginProfile('template', 'event-tags');
        // line 1
        yield '<html>
<head>
</head>
<body
  x-data="';
        // line 5
        yield 'testing';
        yield "\"
  x-init=\" () => { data.match(/<(.*?)>/) ? alert('wat') }\"
>";
        // line 7
        ob_start();
        yield 'Hello World';
        Craft::$app->getView()->registerHtml(ob_get_clean(), 2);
        // line 8
        yield '
</body>
</html>
';
        craft\helpers\Template::endProfile('template', 'event-tags');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return 'event-tags';
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
        return [58 => 8,  54 => 7,  49 => 5,  43 => 1];
    }

    public function getSourceContext(): Source
    {
        return new Source("<html>
<head>
</head>
<body
  x-data=\"{{ 'testing' }}\"
  x-init=\" () => { data.match(/<(.*?)>/) ? alert('wat') }\"
>{% html at beginBody %}Hello World{% endhtml %}

</body>
</html>
", 'event-tags', '/tmp/packages/craft5/tests/_craft/templates/event-tags.twig');
    }
}
