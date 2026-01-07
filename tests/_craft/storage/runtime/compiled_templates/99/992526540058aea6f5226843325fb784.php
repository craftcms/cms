<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* _special/email.twig */
class __TwigTemplate_178d34d040d2838eef354ff5f0f2dbaa extends Template
{
    private $source;

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('template', '_special/email.twig');
        // line 1
        echo '<html lang="';
        echo twig_escape_filter($this->env, (isset($context['language']) || array_key_exists('language', $context) ? $context['language'] : (function () {
            throw new RuntimeError('Variable "language" does not exist.', 1, $this->source);
        })()), 'html', null, true);
        echo '">
<head><meta http-equiv="Content-Type" content="text/html charset=UTF-8" /></head>
<body>
    <div style="max-width: 500px; font-size: 13px; line-height: 18px; font-family: HelveticaNeue, sans-serif;">
        ';
        // line 5
        echo twig_escape_filter($this->env, (isset($context['body']) || array_key_exists('body', $context) ? $context['body'] : (function () {
            throw new RuntimeError('Variable "body" does not exist.', 5, $this->source);
        })()), 'html', null, true);
        echo '
    </div>
</body>
</html>
';
        craft\helpers\Template::endProfile('template', '_special/email.twig');
    }

    public function getTemplateName()
    {
        return '_special/email.twig';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [46 => 5,  38 => 1];
    }

    public function getSourceContext()
    {
        return new Source('<html lang="{{ language }}">
<head><meta http-equiv="Content-Type" content="text/html charset=UTF-8" /></head>
<body>
    <div style="max-width: 500px; font-size: 13px; line-height: 18px; font-family: HelveticaNeue, sans-serif;">
        {{ body }}
    </div>
</body>
</html>
', '_special/email.twig', '/Users/brianhanson/Development/craft5/src/templates/_special/email.twig');
    }
}
