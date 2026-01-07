<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Markup;
use Twig\Source;
use Twig\Template;

/* _ui/field.twig */
class __TwigTemplate_b37990df7b5336d8562ba2bcabe920bb extends Template
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
        craft\helpers\Template::beginProfile('template', '_ui/field.twig');
        // line 1
        ob_start();
        // line 2
        echo '    <div ';
        echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['attributes']) || array_key_exists('attributes', $context) ? $context['attributes'] : (function () {
            throw new RuntimeError('Variable "attributes" does not exist.', 2, $this->source);
        })()), 'merge', [0 => ['id' => (        // line 4
            (isset($context['id']) || array_key_exists('id', $context) ? $context['id'] : (function () {
                throw new RuntimeError('Variable "id" does not exist.', 4, $this->source);
            })()).'-field'), 'data-component' =>         // line 5
(isset($context['handle']) || array_key_exists('handle', $context) ? $context['handle'] : (function () {
    throw new RuntimeError('Variable "handle" does not exist.', 5, $this->source);
})()), 'data-attribute' =>         // line 6
(isset($context['attribute']) || array_key_exists('attribute', $context) ? $context['attribute'] : (function () {
    throw new RuntimeError('Variable "attribute" does not exist.', 6, $this->source);
})()), ]], 'method'), 'class', [0 => [0 => 'field', 1 => (($this->extensions['craft\web\twig\Extension']->lengthFilter($this->env,         // line 10
    (isset($context['errors']) || array_key_exists('errors', $context) ? $context['errors'] : (function () {
        throw new RuntimeError('Variable "errors" does not exist.', 10, $this->source);
    })()))) ? ('has-errors') : (''))]], 'method'), 'html', null, true);
        // line 11
        echo '>
        <div class="heading">
            ';
        // line 13
        echo isset($context['label']) || array_key_exists('label', $context) ? $context['label'] : (function () {
            throw new RuntimeError('Variable "label" does not exist.', 13, $this->source);
        })();
        echo '
            ';
        // line 14
        echo isset($context['requiredIndicator']) || array_key_exists('requiredIndicator', $context) ? $context['requiredIndicator'] : (function () {
            throw new RuntimeError('Variable "requiredIndicator" does not exist.', 14, $this->source);
        })();
        echo '
            ';
        // line 15
        echo isset($context['translationIndicator']) || array_key_exists('translationIndicator', $context) ? $context['translationIndicator'] : (function () {
            throw new RuntimeError('Variable "translationIndicator" does not exist.', 15, $this->source);
        })();
        echo '

            ';
        // line 17
        if ((isset($context['showAttribute']) || array_key_exists('showAttribute', $context) ? $context['showAttribute'] : (function () {
            throw new RuntimeError('Variable "showAttribute" does not exist.', 17, $this->source);
        })())) {
            // line 18
            echo '                <div class="flex-grow"></div>
                ';
            // line 19
            $this->loadTemplate('_includes/forms/copytextbtn.twig', '_ui/field.twig', 19)->display(twig_to_array(['id' => '$id-attribute', 'class' => [0 => 'code', 1 => 'small', 2 => 'light'], 'value' =>             // line 22
(isset($context['attribute']) || array_key_exists('attribute', $context) ? $context['attribute'] : (function () {
    throw new RuntimeError('Variable "attribute" does not exist.', 22, $this->source);
})()), ]));
            // line 24
            echo '            ';
        }
        // line 25
        echo '        </div>

        ';
        // line 27
        echo (((isset($context['instructionsPosition']) || array_key_exists('instructionsPosition', $context) ? $context['instructionsPosition'] : (function () {
            throw new RuntimeError('Variable "instructionsPosition" does not exist.', 27, $this->source);
        })()) == 'before')) ? ((isset($context['instructions']) || array_key_exists('instructions', $context) ? $context['instructions'] : (function () {
            throw new RuntimeError('Variable "instructions" does not exist.', 27, $this->source);
        })())) : ('');
        echo '
        <div class="input ';
        // line 28
        echo twig_escape_filter($this->env, ((array_key_exists('orientation', $context)) ? (_twig_default_filter((isset($context['orientation']) || array_key_exists('orientation', $context) ? $context['orientation'] : (function () {
            throw new RuntimeError('Variable "orientation" does not exist.', 28, $this->source);
        })()), 'ltr')) : ('ltr')), 'html', null, true);
        echo '">
            ';
        // line 29
        echo isset($context['input']) || array_key_exists('input', $context) ? $context['input'] : (function () {
            throw new RuntimeError('Variable "input" does not exist.', 29, $this->source);
        })();
        echo '
        </div>
        ';
        // line 31
        echo (((isset($context['instructionsPosition']) || array_key_exists('instructionsPosition', $context) ? $context['instructionsPosition'] : (function () {
            throw new RuntimeError('Variable "instructionsPosition" does not exist.', 31, $this->source);
        })()) == 'after')) ? ((isset($context['instructions']) || array_key_exists('instructions', $context) ? $context['instructions'] : (function () {
            throw new RuntimeError('Variable "instructions" does not exist.', 31, $this->source);
        })())) : ('');
        echo '
        ';
        // line 32
        echo isset($context['tip']) || array_key_exists('tip', $context) ? $context['tip'] : (function () {
            throw new RuntimeError('Variable "tip" does not exist.', 32, $this->source);
        })();
        echo '
        ';
        // line 33
        echo isset($context['warning']) || array_key_exists('warning', $context) ? $context['warning'] : (function () {
            throw new RuntimeError('Variable "warning" does not exist.', 33, $this->source);
        })();
        echo '
        ';
        // line 34
        echo isset($context['errorList']) || array_key_exists('errorList', $context) ? $context['errorList'] : (function () {
            throw new RuntimeError('Variable "errorList" does not exist.', 34, $this->source);
        })();
        echo '
    </div>
';
        $___internal_parse_0_ = ('' === $tmp = ob_get_clean()) ? '' : new Markup($tmp, $this->env->getCharset());
        // line 1
        echo twig_spaceless($___internal_parse_0_);
        // line 37
        echo '
';
        craft\helpers\Template::endProfile('template', '_ui/field.twig');
    }

    public function getTemplateName()
    {
        return '_ui/field.twig';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [112 => 37,  110 => 1,  104 => 34,  100 => 33,  96 => 32,  92 => 31,  87 => 29,  83 => 28,  79 => 27,  75 => 25,  72 => 24,  70 => 22,  69 => 19,  66 => 18,  64 => 17,  59 => 15,  55 => 14,  51 => 13,  47 => 11,  45 => 10,  44 => 6,  43 => 5,  42 => 4,  40 => 2,  38 => 1];
    }

    public function getSourceContext()
    {
        return new Source("{% apply spaceless %}
    <div {{ attributes
        .merge({
            id: id ~ '-field',
            'data-component': handle,
            'data-attribute': attribute,
        })
        .class([
            'field',
            (errors|length ? 'has-errors' : ''),
        ]) -}}>
        <div class=\"heading\">
            {{ label | raw }}
            {{ requiredIndicator | raw }}
            {{ translationIndicator | raw }}

            {% if showAttribute %}
                <div class=\"flex-grow\"></div>
                {% include '_includes/forms/copytextbtn.twig' with {
                    id: \"\$id-attribute\",
                    class: ['code', 'small', 'light'],
                    value: attribute,
                } only %}
            {% endif %}
        </div>

        {{ instructionsPosition == 'before' ? instructions | raw : '' }}
        <div class=\"input {{ orientation | default('ltr') }}\">
            {{ input | raw }}
        </div>
        {{ instructionsPosition == 'after' ? instructions | raw : '' }}
        {{ tip | raw }}
        {{ warning | raw }}
        {{ errorList | raw }}
    </div>
{% endapply %}

{#
<div
  id=\"fields-newField-field\"
  class=\"field width-100\"
  data-attribute=\"newField\"
  data-type=\"craft\\fields\\PlainText\"
  data-layout-element=\"79d108f5-a105-461e-bfd1-430f346642a3\"
>
  <div class=\"heading\"><label id=\"fields-newField-label\" for=\"fields-newField\">New Field<span
    class=\"t9n-indicator\"
    title=\"This field is translated for each language.\"
    data-icon=\"language\"
    aria-label=\"This field is translated for each language.\"
    role=\"img\"
  ></span></label>
    <div class=\"flex-grow\"></div>
    <div class=\"copytextbtn-wrapper\">
      <input type=\"text\" class=\"visually-hidden\" value=\"newField\" readonly=\"\" size=\"8\" tabindex=\"-1\" aria-hidden=\"true\">
      <div
        id=\"fields-newField-attribute\"
        class=\"code small light copytextbtn\"
        title=\"Copy to clipboard\"
        role=\"button\"
        tabindex=\"0\"
      ><span class=\"copytextbtn__value\">newField</span>
        <span class=\"visually-hidden\">Copy to clipboard</span>
        <span class=\"copytextbtn__icon\" data-icon=\"clipboard\" aria-hidden=\"true\"></span>
      </div>
    </div>

  </div>
  <div class=\"input ltr\"><input
    type=\"text\"
    id=\"fields-newField\"
    class=\"nicetext text fullwidth\"
    name=\"fields[newField]\"
    value=\"New Entry new field\"
    orientation=\"ltr\"
    data-component=\"input\"
  ></div>
</div>
#}", '_ui/field.twig', '/Users/brianhanson/Development/craft5/src/templates/_ui/field.twig');
    }
}
