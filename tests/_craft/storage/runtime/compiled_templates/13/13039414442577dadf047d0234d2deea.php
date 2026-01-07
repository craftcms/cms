<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* settings/general/_images/image */
class __TwigTemplate_2c4bd151e651204768d02fa949348538 extends Template
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
        craft\helpers\Template::beginProfile('template', 'settings/general/_images/image');
        // line 1
        if (\Craft::$app->getEdition() < (isset($context['CraftPro']) || array_key_exists('CraftPro', $context) ? $context['CraftPro'] : (function () {
            throw new RuntimeError('Variable "CraftPro" does not exist.', 1, $this->source);
        })())) {
            throw new yii\web\NotFoundHttpException;
        }
        // line 2
        echo '
';
        // line 3
        $context['isImageUploaded'] = craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 3, $this->source);
        })()), 'rebrand', []), 'isImageUploaded', [0 => (isset($context['imageType']) || array_key_exists('imageType', $context) ? $context['imageType'] : (function () {
            throw new RuntimeError('Variable "imageType" does not exist.', 3, $this->source);
        })())], 'method');
        // line 4
        echo '
<div class="cp-image cp-image-';
        // line 5
        echo twig_escape_filter($this->env, (isset($context['imageType']) || array_key_exists('imageType', $context) ? $context['imageType'] : (function () {
            throw new RuntimeError('Variable "imageType" does not exist.', 5, $this->source);
        })()), 'html', null, true);
        echo '" data-type="';
        echo twig_escape_filter($this->env, (isset($context['imageType']) || array_key_exists('imageType', $context) ? $context['imageType'] : (function () {
            throw new RuntimeError('Variable "imageType" does not exist.', 5, $this->source);
        })()), 'html', null, true);
        echo '">
    <div class="cp-current-image';
        // line 6
        if ((isset($context['isImageUploaded']) || array_key_exists('isImageUploaded', $context) ? $context['isImageUploaded'] : (function () {
            throw new RuntimeError('Variable "isImageUploaded" does not exist.', 6, $this->source);
        })())) {
            echo ' checkered';
        }
        echo '"
        ';
        // line 7
        if ((isset($context['isImageUploaded']) || array_key_exists('isImageUploaded', $context) ? $context['isImageUploaded'] : (function () {
            throw new RuntimeError('Variable "isImageUploaded" does not exist.', 7, $this->source);
        })())) {
            // line 8
            $context['image'] = craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                throw new RuntimeError('Variable "craft" does not exist.', 8, $this->source);
            })()), 'rebrand', []), 'getImageVariable', [0 => (isset($context['imageType']) || array_key_exists('imageType', $context) ? $context['imageType'] : (function () {
                throw new RuntimeError('Variable "imageType" does not exist.', 8, $this->source);
            })())], 'method');
            // line 9
            echo '            ';
            if (((craft\helpers\Template::attribute($this->env, $this->source, (isset($context['image']) || array_key_exists('image', $context) ? $context['image'] : (function () {
                throw new RuntimeError('Variable "image" does not exist.', 9, $this->source);
            })()), 'width', []) > 0) && (craft\helpers\Template::attribute($this->env, $this->source, (isset($context['image']) || array_key_exists('image', $context) ? $context['image'] : (function () {
                throw new RuntimeError('Variable "image" does not exist.', 9, $this->source);
            })()), 'height', []) > 0))) {
                // line 10
                echo '                 style="width: ';
                echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['image']) || array_key_exists('image', $context) ? $context['image'] : (function () {
                    throw new RuntimeError('Variable "image" does not exist.', 10, $this->source);
                })()), 'width', []), 'html', null, true);
                echo 'px; height: ';
                echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['image']) || array_key_exists('image', $context) ? $context['image'] : (function () {
                    throw new RuntimeError('Variable "image" does not exist.', 10, $this->source);
                })()), 'height', []), 'html', null, true);
                echo 'px;"
                 data-url="';
                // line 11
                echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['image']) || array_key_exists('image', $context) ? $context['image'] : (function () {
                    throw new RuntimeError('Variable "image" does not exist.', 11, $this->source);
                })()), 'url', []), 'html', null, true);
                echo '"
             ';
            }
        }
        // line 14
        echo '        >
        ';
        // line 15
        if ((array_key_exists('image', $context) && ! twig_test_empty((isset($context['image']) || array_key_exists('image', $context) ? $context['image'] : (function () {
            throw new RuntimeError('Variable "image" does not exist.', 15, $this->source);
        })())))) {
            // line 16
            echo '<img src="';
            echo twig_escape_filter($this->env, craft\helpers\UrlHelper::url(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['image']) || array_key_exists('image', $context) ? $context['image'] : (function () {
                throw new RuntimeError('Variable "image" does not exist.', 16, $this->source);
            })()), 'url', [])), 'html', null, true);
            echo '" alt="';
            if (((isset($context['imageType']) || array_key_exists('imageType', $context) ? $context['imageType'] : (function () {
                throw new RuntimeError('Variable "imageType" does not exist.', 16, $this->source);
            })()) == 'logo')) {
                echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Login Page Logo', 'app'), 'html', null, true);
            } else {
                echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Site Icon', 'app'), 'html', null, true);
            }
            echo '" />';
        }
        // line 18
        echo '    </div>

    <div class="cp-image-controls">
        <input type="file" name="image" class="hidden" />
        ';
        // line 22
        if ((isset($context['isImageUploaded']) || array_key_exists('isImageUploaded', $context) ? $context['isImageUploaded'] : (function () {
            throw new RuntimeError('Variable "isImageUploaded" does not exist.', 22, $this->source);
        })())) {
            // line 23
            echo '            <div class="flex flex-nowrap">
                <button type="button" class="btn upload">';
            // line 24
            if (((isset($context['imageType']) || array_key_exists('imageType', $context) ? $context['imageType'] : (function () {
                throw new RuntimeError('Variable "imageType" does not exist.', 24, $this->source);
            })()) == 'logo')) {
                $this->displayBlock('changeLogoLabel', $context, $blocks);
            } else {
                $this->displayBlock('changeIconLabel', $context, $blocks);
            }
            echo '</button>
                <button type="button" class="btn delete">';
            // line 25
            if (((isset($context['imageType']) || array_key_exists('imageType', $context) ? $context['imageType'] : (function () {
                throw new RuntimeError('Variable "imageType" does not exist.', 25, $this->source);
            })()) == 'logo')) {
                $this->displayBlock('deleteLogoLabel', $context, $blocks);
            } else {
                $this->displayBlock('deleteIconLabel', $context, $blocks);
            }
            echo '</button>
            </div>
        ';
        } else {
            // line 28
            echo '            <div class="flex flex-nowrap">
                <button type="button" class="btn upload">';
            // line 29
            if (((isset($context['imageType']) || array_key_exists('imageType', $context) ? $context['imageType'] : (function () {
                throw new RuntimeError('Variable "imageType" does not exist.', 29, $this->source);
            })()) == 'logo')) {
                $this->displayBlock('uploadLogoLabel', $context, $blocks);
            } else {
                $this->displayBlock('uploadIconLabel', $context, $blocks);
            }
            echo '</button>
            </div>
        ';
        }
        // line 32
        echo '    </div>
</div>
';
        craft\helpers\Template::endProfile('template', 'settings/general/_images/image');
    }

    public function getTemplateName()
    {
        return 'settings/general/_images/image';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [141 => 32,  131 => 29,  128 => 28,  118 => 25,  110 => 24,  107 => 23,  105 => 22,  99 => 18,  88 => 16,  86 => 15,  83 => 14,  77 => 11,  70 => 10,  67 => 9,  65 => 8,  63 => 7,  57 => 6,  51 => 5,  48 => 4,  46 => 3,  43 => 2,  38 => 1];
    }

    public function getSourceContext()
    {
        return new Source("{% requireEdition CraftPro %}

{% set isImageUploaded = craft.rebrand.isImageUploaded(imageType) %}

<div class=\"cp-image cp-image-{{ imageType }}\" data-type=\"{{ imageType }}\">
    <div class=\"cp-current-image{% if isImageUploaded %} checkered{% endif %}\"
        {% if isImageUploaded -%}
            {% set image = craft.rebrand.getImageVariable(imageType) %}
            {% if image.width > 0 and image.height > 0 %}
                 style=\"width: {{ image.width }}px; height: {{ image.height }}px;\"
                 data-url=\"{{ image.url }}\"
             {% endif %}
        {%- endif %}
        >
        {% if image is defined and image is not empty -%}
            <img src=\"{{ url(image.url) }}\" alt=\"{% if imageType == 'logo' %}{{ \"Login Page Logo\"|t('app') }}{% else %}{{ \"Site Icon\"|t('app') }}{% endif %}\" />
        {%- endif %}
    </div>

    <div class=\"cp-image-controls\">
        <input type=\"file\" name=\"image\" class=\"hidden\" />
        {% if isImageUploaded %}
            <div class=\"flex flex-nowrap\">
                <button type=\"button\" class=\"btn upload\">{% if imageType == 'logo' %}{{ block('changeLogoLabel') }}{% else %}{{ block('changeIconLabel') }}{% endif %}</button>
                <button type=\"button\" class=\"btn delete\">{% if imageType == 'logo' %}{{ block('deleteLogoLabel') }}{% else %}{{ block('deleteIconLabel') }}{% endif %}</button>
            </div>
        {% else %}
            <div class=\"flex flex-nowrap\">
                <button type=\"button\" class=\"btn upload\">{% if imageType == 'logo' %}{{ block('uploadLogoLabel') }}{% else %}{{ block('uploadIconLabel') }}{% endif %}</button>
            </div>
        {% endif %}
    </div>
</div>
", 'settings/general/_images/image', '/Users/brianhanson/Development/craft5/src/templates/settings/general/_images/image.twig');
    }
}
