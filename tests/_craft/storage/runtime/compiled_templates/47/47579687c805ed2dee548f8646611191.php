<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Markup;
use Twig\Source;
use Twig\Template;

/* _includes/forms/fld/field-settings.twig */
class __TwigTemplate_98745006af289bc652a9016e1f229ef3 extends Template
{
    private $source;

    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
            'fieldSettings' => $this->block_fieldSettings(...),
            'labelField' => $this->block_labelField(...),
            'instructionsField' => $this->block_instructionsField(...),
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        craft\helpers\Template::beginProfile('template', '_includes/forms/fld/field-settings.twig');
        // line 1
        $macros['forms'] = $this->macros['forms'] = $this->loadTemplate('_includes/forms', '_includes/forms/fld/field-settings.twig', 1)->unwrap();
        // line 2
        echo '
';
        // line 3
        $context['hideLabelChangeJs'] = ('' === $tmp = "if (this.checked) {
    \$(this).closest('.field').find('.text').addClass('disabled').prop('disabled', true);
  } else {
    \$(this).closest('.field').find('.text').removeClass('disabled').prop('disabled', false);
  }") ? '' : new Markup($tmp, $this->env->getCharset());
        // line 10
        echo '
';
        // line 11
        $this->displayBlock('fieldSettings', $context, $blocks);
        craft\helpers\Template::endProfile('template', '_includes/forms/fld/field-settings.twig');
    }

    public function block_fieldSettings($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'fieldSettings');
        // line 12
        echo '  ';
        $this->displayBlock('labelField', $context, $blocks);
        // line 41
        echo '
  ';
        // line 42
        $this->displayBlock('instructionsField', $context, $blocks);
        craft\helpers\Template::endProfile('block', 'fieldSettings');
    }

    // line 12
    public function block_labelField($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'labelField');
        // line 13
        echo '    ';
        $this->loadTemplate('_includes/forms/fld/field-settings.twig', '_includes/forms/fld/field-settings.twig', 13, '1555225789')->display(twig_array_merge($context, ['id' => 'label', 'label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Label', 'app')]));
        // line 40
        echo '  ';
        craft\helpers\Template::endProfile('block', 'labelField');
    }

    // line 42
    public function block_instructionsField($context, array $blocks = [])
    {
        $macros = $this->macros;
        craft\helpers\Template::beginProfile('block', 'instructionsField');
        // line 43
        echo '    ';
        echo twig_call_macro($macros['forms'], 'macro_textareaField', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Instructions', 'app'), 'id' => 'instructions', 'class' => 'nicetext', 'name' => 'instructions', 'value' => craft\helpers\Template::attribute($this->env, $this->source,         // line 48
            (isset($context['field']) || array_key_exists('field', $context) ? $context['field'] : (function () {
                throw new RuntimeError('Variable "field" does not exist.', 48, $this->source);
            })()), 'instructions', []), 'placeholder' =>         // line 49
(isset($context['defaultInstructions']) || array_key_exists('defaultInstructions', $context) ? $context['defaultInstructions'] : (function () {
    throw new RuntimeError('Variable "defaultInstructions" does not exist.', 49, $this->source);
})()), ]], 43, $context, $this->getSourceContext());
        // line 50
        echo '
  ';
        craft\helpers\Template::endProfile('block', 'instructionsField');
    }

    public function getTemplateName()
    {
        return '_includes/forms/fld/field-settings.twig';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [98 => 50,  96 => 49,  95 => 48,  93 => 43,  88 => 42,  83 => 40,  80 => 13,  75 => 12,  70 => 42,  67 => 41,  64 => 12,  55 => 11,  52 => 10,  46 => 3,  43 => 2,  41 => 1];
    }

    public function getSourceContext()
    {
        return new Source("{% import '_includes/forms' as forms %}

{% set hideLabelChangeJs -%}
  if (this.checked) {
    \$(this).closest('.field').find('.text').addClass('disabled').prop('disabled', true);
  } else {
    \$(this).closest('.field').find('.text').removeClass('disabled').prop('disabled', false);
  }
{%- endset %}

{% block fieldSettings %}
  {% block labelField %}
    {% embed '_includes/forms/field' with {
      id: 'label',
      label: 'Label'|t('app'),
    } %}
      {% block heading %}
        {{ parent() }}
        <div class=\"flex-grow\"></div>
        {% include '_includes/forms/checkbox' with {
          id: 'label-toggle',
          name: 'labelHidden',
          label: 'Hide'|t('app'),
          checked: labelHidden,
          inputAttributes: {
            onchange: hideLabelChangeJs,
          },
        } %}
      {% endblock %}
      {% block input %}
        {% include '_includes/forms/text' with {
          id: 'label',
          name: 'label',
          value: not labelHidden ? field.label,
          placeholder: defaultLabel,
          disabled: labelHidden,
        } %}
      {% endblock %}
    {% endembed %}
  {% endblock %}

  {% block instructionsField %}
    {{ forms.textareaField({
      label: 'Instructions'|t('app'),
      id: 'instructions',
      class: 'nicetext',
      name: 'instructions',
      value: field.instructions,
      placeholder: defaultInstructions,
    }) }}
  {% endblock %}
{% endblock %}
", '_includes/forms/fld/field-settings.twig', '/Users/brianhanson/Development/craft5/src/templates/_includes/forms/fld/field-settings.twig');
    }
}

/* _includes/forms/fld/field-settings.twig */
class __TwigTemplate_98745006af289bc652a9016e1f229ef3___1555225789 extends Template
{
    private $source;

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->blocks = [
            'heading' => $this->block_heading(...),
            'input' => $this->block_input(...),
        ];
    }

    #[\Override]
    protected function doGetParent(array $context)
    {
        // line 13
        return '_includes/forms/field';
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('template', '_includes/forms/fld/field-settings.twig');
        $this->parent = $this->loadTemplate('_includes/forms/field', '_includes/forms/fld/field-settings.twig', 13);
        $this->parent->display($context, array_merge($this->blocks, $blocks));
        craft\helpers\Template::endProfile('template', '_includes/forms/fld/field-settings.twig');
    }

    // line 17
    public function block_heading($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'heading');
        // line 18
        echo '        ';
        $this->displayParentBlock('heading', $context, $blocks);
        echo '
        <div class="flex-grow"></div>
        ';
        // line 20
        $this->loadTemplate('_includes/forms/checkbox', '_includes/forms/fld/field-settings.twig', 20)->display(twig_array_merge($context, ['id' => 'label-toggle', 'name' => 'labelHidden', 'label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Hide', 'app'), 'checked' =>         // line 24
(isset($context['labelHidden']) || array_key_exists('labelHidden', $context) ? $context['labelHidden'] : (function () {
    throw new RuntimeError('Variable "labelHidden" does not exist.', 24, $this->source);
})()), 'inputAttributes' => ['onchange' =>         // line 26
(isset($context['hideLabelChangeJs']) || array_key_exists('hideLabelChangeJs', $context) ? $context['hideLabelChangeJs'] : (function () {
    throw new RuntimeError('Variable "hideLabelChangeJs" does not exist.', 26, $this->source);
})()), ], ]));
        // line 29
        echo '      ';
        craft\helpers\Template::endProfile('block', 'heading');
    }

    // line 30
    public function block_input($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'input');
        // line 31
        echo '        ';
        $this->loadTemplate('_includes/forms/text', '_includes/forms/fld/field-settings.twig', 31)->display(twig_array_merge($context, ['id' => 'label', 'name' => 'label', 'value' => ((! // line 34
(isset($context['labelHidden']) || array_key_exists('labelHidden', $context) ? $context['labelHidden'] : (function () {
    throw new RuntimeError('Variable "labelHidden" does not exist.', 34, $this->source);
})())) ? (craft\helpers\Template::attribute($this->env, $this->source, (isset($context['field']) || array_key_exists('field', $context) ? $context['field'] : (function () {
    throw new RuntimeError('Variable "field" does not exist.', 34, $this->source);
})()), 'label', [])) : ('')), 'placeholder' =>         // line 35
(isset($context['defaultLabel']) || array_key_exists('defaultLabel', $context) ? $context['defaultLabel'] : (function () {
    throw new RuntimeError('Variable "defaultLabel" does not exist.', 35, $this->source);
})()), 'disabled' =>         // line 36
(isset($context['labelHidden']) || array_key_exists('labelHidden', $context) ? $context['labelHidden'] : (function () {
    throw new RuntimeError('Variable "labelHidden" does not exist.', 36, $this->source);
})()), ]));
        // line 38
        echo '      ';
        craft\helpers\Template::endProfile('block', 'input');
    }

    public function getTemplateName()
    {
        return '_includes/forms/fld/field-settings.twig';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [242 => 38,  240 => 36,  239 => 35,  238 => 34,  236 => 31,  231 => 30,  226 => 29,  224 => 26,  223 => 24,  222 => 20,  216 => 18,  211 => 17,  198 => 13,  98 => 50,  96 => 49,  95 => 48,  93 => 43,  88 => 42,  83 => 40,  80 => 13,  75 => 12,  70 => 42,  67 => 41,  64 => 12,  55 => 11,  52 => 10,  46 => 3,  43 => 2,  41 => 1];
    }

    public function getSourceContext()
    {
        return new Source("{% import '_includes/forms' as forms %}

{% set hideLabelChangeJs -%}
  if (this.checked) {
    \$(this).closest('.field').find('.text').addClass('disabled').prop('disabled', true);
  } else {
    \$(this).closest('.field').find('.text').removeClass('disabled').prop('disabled', false);
  }
{%- endset %}

{% block fieldSettings %}
  {% block labelField %}
    {% embed '_includes/forms/field' with {
      id: 'label',
      label: 'Label'|t('app'),
    } %}
      {% block heading %}
        {{ parent() }}
        <div class=\"flex-grow\"></div>
        {% include '_includes/forms/checkbox' with {
          id: 'label-toggle',
          name: 'labelHidden',
          label: 'Hide'|t('app'),
          checked: labelHidden,
          inputAttributes: {
            onchange: hideLabelChangeJs,
          },
        } %}
      {% endblock %}
      {% block input %}
        {% include '_includes/forms/text' with {
          id: 'label',
          name: 'label',
          value: not labelHidden ? field.label,
          placeholder: defaultLabel,
          disabled: labelHidden,
        } %}
      {% endblock %}
    {% endembed %}
  {% endblock %}

  {% block instructionsField %}
    {{ forms.textareaField({
      label: 'Instructions'|t('app'),
      id: 'instructions',
      class: 'nicetext',
      name: 'instructions',
      value: field.instructions,
      placeholder: defaultInstructions,
    }) }}
  {% endblock %}
{% endblock %}
", '_includes/forms/fld/field-settings.twig', '/Users/brianhanson/Development/craft5/src/templates/_includes/forms/fld/field-settings.twig');
    }
}
