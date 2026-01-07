<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* event-tags */
class __TwigTemplate_a13ab39ea53e6440adc4fe9ca86f61a2 extends Template
{
    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('template', 'event-tags');
        // line 1
        echo '<html>
<head>
</head>
<body
  x-data="';
        // line 5
        echo 'testing';
        echo "\"
  x-init=\" () => { data.match(/<(.*?)>/) ? alert('wat') }\"
>";
        // line 7
        ob_start();
        echo 'Hello World';
        Craft::$app->getView()->registerHtml(ob_get_clean(), 2);
        // line 8
        echo '
</body>
</html>
';
        craft\helpers\Template::endProfile('template', 'event-tags');
    }

    public function getTemplateName()
    {
        return 'event-tags';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [53 => 8,  49 => 7,  44 => 5,  38 => 1];
    }

    public function getSourceContext()
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
", 'event-tags', '/Users/brianhanson/Development/craft5/tests/_craft/templates/event-tags.twig');
    }
}
