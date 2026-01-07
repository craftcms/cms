<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* settings/general/_images/image */
class __TwigTemplate_28e60dd86914f63d9fcb75ede109479e extends Template
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
        craft\helpers\Template::beginProfile('template', 'settings/general/_images/image');
        // line 1
        if (\Craft::$app->edition->value < (isset($context['CraftPro']) || array_key_exists('CraftPro', $context) ? $context['CraftPro'] : (function () {
            throw new RuntimeError('Variable "CraftPro" does not exist.', 1, $this->source);
        })())) {
            throw new yii\web\NotFoundHttpException;
        }
        // line 2
        yield '
';
        // line 3
        $context['isImageUploaded'] = craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 3, $this->source);
        })()), 'rebrand', [], 'any', false, false, false, 3), 'isImageUploaded', [(isset($context['imageType']) || array_key_exists('imageType', $context) ? $context['imageType'] : (function () {
            throw new RuntimeError('Variable "imageType" does not exist.', 3, $this->source);
        })())], 'method', false, false, false, 3);
        // line 4
        yield '
<div class="cp-image cp-image-';
        // line 5
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['imageType']) || array_key_exists('imageType', $context) ? $context['imageType'] : (function () {
            throw new RuntimeError('Variable "imageType" does not exist.', 5, $this->source);
        })()), 'html', null, true);
        yield '" data-type="';
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['imageType']) || array_key_exists('imageType', $context) ? $context['imageType'] : (function () {
            throw new RuntimeError('Variable "imageType" does not exist.', 5, $this->source);
        })()), 'html', null, true);
        yield '">
    <div class="cp-current-image';
        // line 6
        if ((isset($context['isImageUploaded']) || array_key_exists('isImageUploaded', $context) ? $context['isImageUploaded'] : (function () {
            throw new RuntimeError('Variable "isImageUploaded" does not exist.', 6, $this->source);
        })())) {
            yield ' checkered';
        }
        yield '"
        ';
        // line 7
        if ((isset($context['isImageUploaded']) || array_key_exists('isImageUploaded', $context) ? $context['isImageUploaded'] : (function () {
            throw new RuntimeError('Variable "isImageUploaded" does not exist.', 7, $this->source);
        })())) {
            // line 8
            $context['image'] = craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                throw new RuntimeError('Variable "craft" does not exist.', 8, $this->source);
            })()), 'rebrand', [], 'any', false, false, false, 8), 'getImageVariable', [(isset($context['imageType']) || array_key_exists('imageType', $context) ? $context['imageType'] : (function () {
                throw new RuntimeError('Variable "imageType" does not exist.', 8, $this->source);
            })())], 'method', false, false, false, 8);
            // line 9
            yield '            ';
            if (((craft\helpers\Template::attribute($this->env, $this->source, (isset($context['image']) || array_key_exists('image', $context) ? $context['image'] : (function () {
                throw new RuntimeError('Variable "image" does not exist.', 9, $this->source);
            })()), 'width', [], 'any', false, false, false, 9) > 0) && (craft\helpers\Template::attribute($this->env, $this->source, (isset($context['image']) || array_key_exists('image', $context) ? $context['image'] : (function () {
                throw new RuntimeError('Variable "image" does not exist.', 9, $this->source);
            })()), 'height', [], 'any', false, false, false, 9) > 0))) {
                // line 10
                yield '                 style="width: ';
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['image']) || array_key_exists('image', $context) ? $context['image'] : (function () {
                    throw new RuntimeError('Variable "image" does not exist.', 10, $this->source);
                })()), 'width', [], 'any', false, false, false, 10), 'html', null, true);
                yield 'px; height: ';
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['image']) || array_key_exists('image', $context) ? $context['image'] : (function () {
                    throw new RuntimeError('Variable "image" does not exist.', 10, $this->source);
                })()), 'height', [], 'any', false, false, false, 10), 'html', null, true);
                yield 'px;"
                 data-url="';
                // line 11
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['image']) || array_key_exists('image', $context) ? $context['image'] : (function () {
                    throw new RuntimeError('Variable "image" does not exist.', 11, $this->source);
                })()), 'url', [], 'any', false, false, false, 11), 'html', null, true);
                yield '"
             ';
            }
        }
        // line 14
        yield '        >
        ';
        // line 15
        if ((array_key_exists('image', $context) && ! Twig\Extension\CoreExtension::testEmpty((isset($context['image']) || array_key_exists('image', $context) ? $context['image'] : (function () {
            throw new RuntimeError('Variable "image" does not exist.', 15, $this->source);
        })())))) {
            // line 16
            yield '<img src="';
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\UrlHelper::url(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['image']) || array_key_exists('image', $context) ? $context['image'] : (function () {
                throw new RuntimeError('Variable "image" does not exist.', 16, $this->source);
            })()), 'url', [], 'any', false, false, false, 16)), 'html', null, true);
            yield '" alt="';
            if (((isset($context['imageType']) || array_key_exists('imageType', $context) ? $context['imageType'] : (function () {
                throw new RuntimeError('Variable "imageType" does not exist.', 16, $this->source);
            })()) == 'logo')) {
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Login Page Logo', 'app'), 'html', null, true);
            } else {
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Site Icon', 'app'), 'html', null, true);
            }
            yield '" />';
        }
        // line 18
        yield '    </div>

    <div class="cp-image-controls">
        <input type="file" name="image" class="hidden" />
        ';
        // line 22
        if ((isset($context['isImageUploaded']) || array_key_exists('isImageUploaded', $context) ? $context['isImageUploaded'] : (function () {
            throw new RuntimeError('Variable "isImageUploaded" does not exist.', 22, $this->source);
        })())) {
            // line 23
            yield '            <div class="flex flex-nowrap">
                <button type="button" class="btn upload">';
            // line 24
            if (((isset($context['imageType']) || array_key_exists('imageType', $context) ? $context['imageType'] : (function () {
                throw new RuntimeError('Variable "imageType" does not exist.', 24, $this->source);
            })()) == 'logo')) {
                yield from $this->unwrap()->yieldBlock('changeLogoLabel', $context, $blocks);
            } else {
                yield from $this->unwrap()->yieldBlock('changeIconLabel', $context, $blocks);
            }
            yield '</button>
                <button type="button" class="btn delete">';
            // line 25
            if (((isset($context['imageType']) || array_key_exists('imageType', $context) ? $context['imageType'] : (function () {
                throw new RuntimeError('Variable "imageType" does not exist.', 25, $this->source);
            })()) == 'logo')) {
                yield from $this->unwrap()->yieldBlock('deleteLogoLabel', $context, $blocks);
            } else {
                yield from $this->unwrap()->yieldBlock('deleteIconLabel', $context, $blocks);
            }
            yield '</button>
            </div>
        ';
        } else {
            // line 28
            yield '            <div class="flex flex-nowrap">
                <button type="button" class="btn upload">';
            // line 29
            if (((isset($context['imageType']) || array_key_exists('imageType', $context) ? $context['imageType'] : (function () {
                throw new RuntimeError('Variable "imageType" does not exist.', 29, $this->source);
            })()) == 'logo')) {
                yield from $this->unwrap()->yieldBlock('uploadLogoLabel', $context, $blocks);
            } else {
                yield from $this->unwrap()->yieldBlock('uploadIconLabel', $context, $blocks);
            }
            yield '</button>
            </div>
        ';
        }
        // line 32
        yield '    </div>
</div>
';
        craft\helpers\Template::endProfile('template', 'settings/general/_images/image');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return 'settings/general/_images/image';
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
        return [146 => 32,  136 => 29,  133 => 28,  123 => 25,  115 => 24,  112 => 23,  110 => 22,  104 => 18,  93 => 16,  91 => 15,  88 => 14,  82 => 11,  75 => 10,  72 => 9,  70 => 8,  68 => 7,  62 => 6,  56 => 5,  53 => 4,  51 => 3,  48 => 2,  43 => 1];
    }

    public function getSourceContext(): Source
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
", 'settings/general/_images/image', '/tmp/packages/craft5/src/templates/settings/general/_images/image.twig');
    }
}
