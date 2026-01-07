<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* _ui/nav.twig */
class __TwigTemplate_50ce308261f68dc957e782888b45fcd4 extends Template
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
        craft\helpers\Template::beginProfile('template', '_ui/nav.twig');
        // line 1
        echo '<nav ';
        echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['attributes']) || array_key_exists('attributes', $context) ? $context['attributes'] : (function () {
            throw new RuntimeError('Variable "attributes" does not exist.', 1, $this->source);
        })()), 'merge', [0 => ['id' =>         // line 2
(isset($context['id']) || array_key_exists('id', $context) ? $context['id'] : (function () {
    throw new RuntimeError('Variable "id" does not exist.', 2, $this->source);
})()), 'aria-label' =>         // line 3
(isset($context['label']) || array_key_exists('label', $context) ? $context['label'] : (function () {
    throw new RuntimeError('Variable "label" does not exist.', 3, $this->source);
})()), 'data-component' =>         // line 4
(isset($context['handle']) || array_key_exists('handle', $context) ? $context['handle'] : (function () {
    throw new RuntimeError('Variable "handle" does not exist.', 4, $this->source);
})()), ]], 'method'), 'html', null, true);
        // line 5
        echo '>
    <ul>
        ';
        // line 7
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable((isset($context['items']) || array_key_exists('items', $context) ? $context['items'] : (function () {
            throw new RuntimeError('Variable "items" does not exist.', 7, $this->source);
        })()));
        foreach ($context['_seq'] as $context['_key'] => $context['item']) {
            // line 8
            echo '            <li>
                ';
            // line 9
            echo $context['item'];
            echo '
            </li>
        ';
        }
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['item'], $context['_parent'], $context['loop']);
        // line 12
        echo '    </ul>
</nav>

';
        craft\helpers\Template::endProfile('template', '_ui/nav.twig');
    }

    public function getTemplateName()
    {
        return '_ui/nav.twig';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [64 => 12,  55 => 9,  52 => 8,  48 => 7,  44 => 5,  42 => 4,  41 => 3,  40 => 2,  38 => 1];
    }

    public function getSourceContext()
    {
        return new Source("<nav {{ attributes.merge({
    id: id,
    'aria-label': label,
    'data-component': handle
}) }}>
    <ul>
        {% for item in items %}
            <li>
                {{ item | raw }}
            </li>
        {% endfor %}
    </ul>
</nav>

", '_ui/nav.twig', '/Users/brianhanson/Development/craft5/src/templates/_ui/nav.twig');
    }
}
