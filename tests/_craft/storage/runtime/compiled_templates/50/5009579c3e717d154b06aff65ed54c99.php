<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Markup;
use Twig\Source;
use Twig\Template;

/* settings/users/settings */
class __TwigTemplate_e800798cac8c7b0ce48fa49a43c2a70d extends Template
{
    private $source;

    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->blocks = [
            'content' => $this->block_content(...),
        ];
        $macros['_self'] = $this->macros['_self'] = $this;
    }

    #[\Override]
    protected function doGetParent(array $context)
    {
        // line 3
        return 'settings/users/_layout';
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        craft\helpers\Template::beginProfile('template', 'settings/users/settings');
        // line 1
        Craft::$app->controller->requireAdmin();
        // line 4
        $context['selectedNavItem'] = 'settings';
        // line 5
        $context['fullPageForm'] = true;
        // line 7
        $macros['forms'] = $this->macros['forms'] = $this->loadTemplate('_includes/forms', 'settings/users/settings', 7)->unwrap();
        // line 10
        if (! array_key_exists('settings', $context)) {
            // line 11
            $context['settings'] = (((craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, ($context['craft'] ?? null), 'app', [], 'any', false, true), 'projectConfig', [], 'any', false, true), 'get', [0 => 'users'], 'method', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, ($context['craft'] ?? null), 'app', [], 'any', false, true), 'projectConfig', [], 'any', false, true), 'get', [0 => 'users'], 'method') === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, ($context['craft'] ?? null), 'app', [], 'any', false, true), 'projectConfig', [], 'any', false, true), 'get', [0 => 'users'], 'method')) : ([]));
        }
        // line 15
        $context['settings'] = $this->extensions['craft\web\twig\Extension']->mergeFilter(['photoVolumeUid' => null, 'photoSubpath' => null, 'requireEmailVerification' => true, 'allowPublicRegistration' => false, 'validateOnPublicRegistration' => false, 'deactivateByDefault' => false, 'defaultGroup' => null],         // line 23
            (isset($context['settings']) || array_key_exists('settings', $context) ? $context['settings'] : (function () {
                throw new RuntimeError('Variable "settings" does not exist.', 23, $this->source);
            })()));
        // line 25
        $context['hasVolumes'] = ($this->extensions['craft\web\twig\Extension']->lengthFilter($this->env, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 25, $this->source);
        })()), 'app', []), 'volumes', []), 'getAllVolumes', [])) != 0);
        // line 26
        $context['photoVolume'] = ((craft\helpers\Template::attribute($this->env, $this->source, (isset($context['settings']) || array_key_exists('settings', $context) ? $context['settings'] : (function () {
            throw new RuntimeError('Variable "settings" does not exist.', 26, $this->source);
        })()), 'photoVolumeUid', [])) ? (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 26, $this->source);
        })()), 'app', []), 'volumes', []), 'getVolumeByUid', [0 => craft\helpers\Template::attribute($this->env, $this->source, (isset($context['settings']) || array_key_exists('settings', $context) ? $context['settings'] : (function () {
            throw new RuntimeError('Variable "settings" does not exist.', 26, $this->source);
        })()), 'photoVolumeUid', [])], 'method')) : (null));
        // line 28
        $context['allVolumes'] = craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 28, $this->source);
        })()), 'app', []), 'volumes', []), 'getAllVolumes', [], 'method');
        // line 29
        $context['volumeList'] = [];
        // line 30
        $context['validVolumeUids'] = [];
        // line 32
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable((isset($context['allVolumes']) || array_key_exists('allVolumes', $context) ? $context['allVolumes'] : (function () {
            throw new RuntimeError('Variable "allVolumes" does not exist.', 32, $this->source);
        })()));
        foreach ($context['_seq'] as $context['_key'] => $context['volume']) {
            // line 33
            if (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, $context['volume'], 'getTransformFs', [], 'method'), 'hasUrls', [])) {
                // line 34
                $context['volumeList'] = $this->extensions['craft\web\twig\Extension']->pushFilter((isset($context['volumeList']) || array_key_exists('volumeList', $context) ? $context['volumeList'] : (function () {
                    throw new RuntimeError('Variable "volumeList" does not exist.', 34, $this->source);
                })()), ['label' => craft\helpers\Template::attribute($this->env, $this->source, $context['volume'], 'name', []), 'value' => craft\helpers\Template::attribute($this->env, $this->source, $context['volume'], 'uid', [])]);
                // line 35
                $context['validVolumeUids'] = $this->extensions['craft\web\twig\Extension']->pushFilter((isset($context['validVolumeUids']) || array_key_exists('validVolumeUids', $context) ? $context['validVolumeUids'] : (function () {
                    throw new RuntimeError('Variable "validVolumeUids" does not exist.', 35, $this->source);
                })()), craft\helpers\Template::attribute($this->env, $this->source, $context['volume'], 'uid', []));
            }
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['volume'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 3
        $this->parent = $this->loadTemplate('settings/users/_layout', 'settings/users/settings', 3);
        $this->parent->display($context, array_merge($this->blocks, $blocks));
        craft\helpers\Template::endProfile('template', 'settings/users/settings');
    }

    // line 62
    public function block_content($context, array $blocks = [])
    {
        $macros = $this->macros;
        craft\helpers\Template::beginProfile('block', 'content');
        // line 63
        echo '  ';
        echo craft\helpers\Html::actionInput('user-settings/save-user-settings');
        echo '
  ';
        // line 64
        echo craft\helpers\Html::csrfInput();
        echo '

  ';
        // line 66
        if (((isset($context['CraftEdition']) || array_key_exists('CraftEdition', $context) ? $context['CraftEdition'] : (function () {
            throw new RuntimeError('Variable "CraftEdition" does not exist.', 66, $this->source);
        })()) == (isset($context['CraftPro']) || array_key_exists('CraftPro', $context) ? $context['CraftPro'] : (function () {
            throw new RuntimeError('Variable "CraftPro" does not exist.', 66, $this->source);
        })()))) {
            // line 67
            echo '    <h2 class="first">';
            echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('User Photos', 'app'), 'html', null, true);
            echo '</h2>
  ';
        }
        // line 69
        echo '
  ';
        // line 70
        if ((isset($context['hasVolumes']) || array_key_exists('hasVolumes', $context) ? $context['hasVolumes'] : (function () {
            throw new RuntimeError('Variable "hasVolumes" does not exist.', 70, $this->source);
        })())) {
            // line 71
            echo '    ';
            $context['volumeOptions'] = craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                throw new RuntimeError('Variable "craft" does not exist.', 71, $this->source);
            })()), 'cp', []), 'getVolumeOptions', [], 'method');
            // line 72
            echo '    ';
            if (! (isset($context['photoVolume']) || array_key_exists('photoVolume', $context) ? $context['photoVolume'] : (function () {
                throw new RuntimeError('Variable "photoVolume" does not exist.', 72, $this->source);
            })())) {
                // line 73
                echo '      ';
                $context['volumeOptions'] = $this->extensions['craft\web\twig\Extension']->unshiftFilter((isset($context['volumeOptions']) || array_key_exists('volumeOptions', $context) ? $context['volumeOptions'] : (function () {
                    throw new RuntimeError('Variable "volumeOptions" does not exist.', 73, $this->source);
                })()), ['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Select a volume', 'app'), 'value' => null]);
                // line 74
                echo '    ';
            }
            // line 75
            echo '
    ';
            // line 76
            echo twig_call_macro($macros['forms'], 'macro_field', [['first' => true, 'label' => $this->extensions['craft\web\twig\Extension']->translateFilter('User Photo Location', 'app'), 'instructions' => $this->extensions['craft\web\twig\Extension']->translateFilter('Where do you want to store user photos? Note that the subfolder path can contain variables like <code>{username}</code>.', 'app')], twig_call_macro($macros['_self'], 'macro_assetLocationInput', [            // line 80
                (isset($context['volumeOptions']) || array_key_exists('volumeOptions', $context) ? $context['volumeOptions'] : (function () {
                    throw new RuntimeError('Variable "volumeOptions" does not exist.', 80, $this->source);
                })()), (isset($context['photoVolume']) || array_key_exists('photoVolume', $context) ? $context['photoVolume'] : (function () {
                    throw new RuntimeError('Variable "photoVolume" does not exist.', 80, $this->source);
                })()), craft\helpers\Template::attribute($this->env, $this->source, (isset($context['settings']) || array_key_exists('settings', $context) ? $context['settings'] : (function () {
                    throw new RuntimeError('Variable "settings" does not exist.', 80, $this->source);
                })()), 'photoSubpath', []), ], 80, $context, $this->getSourceContext())], 76, $context, $this->getSourceContext());
            echo '
  ';
        } else {
            // line 82
            echo '    ';
            echo twig_call_macro($macros['forms'], 'macro_field', [['first' => true, 'label' => $this->extensions['craft\web\twig\Extension']->translateFilter('User Photo Volume', 'app')], (('<p class="error">'.$this->extensions['craft\web\twig\Extension']->translateFilter('No volumes exist yet.', 'app')).'</p>')], 82, $context, $this->getSourceContext());
            // line 85
            echo '
  ';
        }
        // line 87
        echo '
  ';
        // line 88
        if (((isset($context['CraftEdition']) || array_key_exists('CraftEdition', $context) ? $context['CraftEdition'] : (function () {
            throw new RuntimeError('Variable "CraftEdition" does not exist.', 88, $this->source);
        })()) == (isset($context['CraftPro']) || array_key_exists('CraftPro', $context) ? $context['CraftPro'] : (function () {
            throw new RuntimeError('Variable "CraftPro" does not exist.', 88, $this->source);
        })()))) {
            // line 89
            echo '    <hr>
    <h2>';
            // line 90
            echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Public Registration', 'app'), 'html', null, true);
            echo '</h2>

    ';
            // line 92
            echo twig_call_macro($macros['forms'], 'macro_checkboxField', [['fieldClass' => 'first', 'label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Allow public registration', 'app'), 'name' => 'allowPublicRegistration', 'checked' => craft\helpers\Template::attribute($this->env, $this->source,             // line 96
                (isset($context['settings']) || array_key_exists('settings', $context) ? $context['settings'] : (function () {
                    throw new RuntimeError('Variable "settings" does not exist.', 96, $this->source);
                })()), 'allowPublicRegistration', []), 'toggle' => 'publicRegistrationSettings']], 92, $context, $this->getSourceContext());
            // line 98
            echo '

    <div id="publicRegistrationSettings" class="nested-fields';
            // line 100
            if (! craft\helpers\Template::attribute($this->env, $this->source, (isset($context['settings']) || array_key_exists('settings', $context) ? $context['settings'] : (function () {
                throw new RuntimeError('Variable "settings" does not exist.', 100, $this->source);
            })()), 'allowPublicRegistration', [])) {
                echo ' hidden';
            }
            echo '">
      ';
            // line 101
            echo twig_call_macro($macros['forms'], 'macro_checkboxField', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Validate custom fields on public registration', 'app'), 'instructions' => $this->extensions['craft\web\twig\Extension']->translateFilter('Whether custom fields should be validated during public registration.', 'app'), 'name' => 'validateOnPublicRegistration', 'checked' => craft\helpers\Template::attribute($this->env, $this->source,             // line 105
                (isset($context['settings']) || array_key_exists('settings', $context) ? $context['settings'] : (function () {
                    throw new RuntimeError('Variable "settings" does not exist.', 105, $this->source);
                })()), 'validateOnPublicRegistration', [])]], 101, $context, $this->getSourceContext());
            // line 106
            echo '

      ';
            // line 108
            echo twig_call_macro($macros['forms'], 'macro_checkboxField', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Deactivate users by default', 'app'), 'instructions' => $this->extensions['craft\web\twig\Extension']->translateFilter('Should users who register their own accounts be deactivated by default? This will prevent them from receiving an activation email or logging in.', 'app'), 'name' => 'deactivateByDefault', 'checked' => craft\helpers\Template::attribute($this->env, $this->source,             // line 112
                (isset($context['settings']) || array_key_exists('settings', $context) ? $context['settings'] : (function () {
                    throw new RuntimeError('Variable "settings" does not exist.', 112, $this->source);
                })()), 'deactivateByDefault', [])]], 108, $context, $this->getSourceContext());
            // line 113
            echo '

      ';
            // line 115
            $context['groups'] = [0 => ['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('None', 'app'), 'value' => '']];
            // line 116
            echo '      ';
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                throw new RuntimeError('Variable "craft" does not exist.', 116, $this->source);
            })()), 'app', []), 'userGroups', []), 'getAllGroups', [], 'method'));
            foreach ($context['_seq'] as $context['_key'] => $context['group']) {
                // line 117
                echo '        ';
                $context['groups'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['groups']) || array_key_exists('groups', $context) ? $context['groups'] : (function () {
                    throw new RuntimeError('Variable "groups" does not exist.', 117, $this->source);
                })()), [0 => ['label' => craft\helpers\Template::attribute($this->env, $this->source, $context['group'], 'name', []), 'value' => craft\helpers\Template::attribute($this->env, $this->source, $context['group'], 'uid', [])]]);
                // line 118
                echo '      ';
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['group'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 119
            echo '
      ';
            // line 120
            echo twig_call_macro($macros['forms'], 'macro_selectField', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Default User Group', 'app'), 'instructions' => $this->extensions['craft\web\twig\Extension']->translateFilter('Choose a user group that publicly-registered members will be added to by default.', 'app'), 'name' => 'defaultGroup', 'options' =>             // line 124
                (isset($context['groups']) || array_key_exists('groups', $context) ? $context['groups'] : (function () {
                    throw new RuntimeError('Variable "groups" does not exist.', 124, $this->source);
                })()), 'value' => craft\helpers\Template::attribute($this->env, $this->source,             // line 125
                    (isset($context['settings']) || array_key_exists('settings', $context) ? $context['settings'] : (function () {
                        throw new RuntimeError('Variable "settings" does not exist.', 125, $this->source);
                    })()), 'defaultGroup', []), ]], 120, $context, $this->getSourceContext());
            // line 126
            echo '
    </div>

    <hr>
    <h2>';
            // line 130
            echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Security', 'app'), 'html', null, true);
            echo '</h2>

    ';
            // line 132
            echo twig_call_macro($macros['forms'], 'macro_checkboxField', [['fieldClass' => 'first', 'label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Verify email addresses', 'app'), 'instructions' => $this->extensions['craft\web\twig\Extension']->translateFilter('Should new email addresses be verified before getting saved to user accounts? (This also affects new user registration.)', 'app'), 'name' => 'requireEmailVerification', 'checked' => craft\helpers\Template::attribute($this->env, $this->source,             // line 137
                (isset($context['settings']) || array_key_exists('settings', $context) ? $context['settings'] : (function () {
                    throw new RuntimeError('Variable "settings" does not exist.', 137, $this->source);
                })()), 'requireEmailVerification', [])]], 132, $context, $this->getSourceContext());
            // line 138
            echo '

    ';
            // line 140
            echo twig_call_macro($macros['forms'], 'macro_checkboxSelectField', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Require two-step verification', 'app'), 'instructions' => $this->extensions['craft\web\twig\Extension']->translateFilter('Choose which users must use two-step verification when accessing the control panel.', 'app'), 'name' => 'require2fa', 'options' => $this->extensions['craft\web\twig\Extension']->unshiftFilter($this->extensions['craft\web\twig\Extension']->mapFilter($this->env, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source,             // line 144
                (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                    throw new RuntimeError('Variable "craft" does not exist.', 144, $this->source);
                })()), 'app', []), 'userGroups', []), 'getAllGroups', [], 'method'),             // line 145
                function ($__g__) use ($context) {
                    $context['g'] = $__g__;

                    return ['value' => craft\helpers\Template::attribute($this->env, $this->source, (isset($context['g']) || array_key_exists('g', $context) ? $context['g'] : (function () {
                        throw new RuntimeError('Variable "g" does not exist.', 145, $this->source);
                    })()), 'uid', []), 'label' => $this->extensions['craft\web\twig\Extension']->translateFilter(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['g']) || array_key_exists('g', $context) ? $context['g'] : (function () {
                        throw new RuntimeError('Variable "g" does not exist.', 145, $this->source);
                    })()), 'name', []), 'site')];
                }), ['value' => 'admins', 'label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Admins', 'app')]), 'showAllOption' => true, 'allLabel' => $this->extensions['craft\web\twig\Extension']->translateFilter('All users', 'app'), 'allValue' => 'all', 'values' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 150
                    ($context['settings'] ?? null), 'require2fa', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['settings'] ?? null), 'require2fa', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['settings'] ?? null), 'require2fa', [])) : (false))]], 140, $context, $this->getSourceContext());
            // line 151
            echo '
  ';
        }
        craft\helpers\Template::endProfile('block', 'content');
    }

    // line 39
    public function macro_assetLocationInput($__volumeOptions__ = null, $__photoVolume__ = null, $__subpath__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = $this->env->mergeGlobals([
            'volumeOptions' => $__volumeOptions__,
            'photoVolume' => $__photoVolume__,
            'subpath' => $__subpath__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'assetLocationInput');
            // line 40
            echo '  ';
            $macros['forms'] = $this->loadTemplate('_includes/forms', 'settings/users/settings', 40)->unwrap();
            // line 41
            echo '  <div class="flex">
    <div>
      ';
            // line 43
            echo twig_call_macro($macros['forms'], 'macro_volume', [['id' => 'photoVolumeId', 'name' => 'photoVolumeId', 'options' =>             // line 46
(isset($context['volumeOptions']) || array_key_exists('volumeOptions', $context) ? $context['volumeOptions'] : (function () {
    throw new RuntimeError('Variable "volumeOptions" does not exist.', 46, $this->source);
})()), 'value' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 47
    ($context['photoVolume'] ?? null), 'id', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['photoVolume'] ?? null), 'id', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['photoVolume'] ?? null), 'id', [])) : (null)), ]], 43, $context, $this->getSourceContext());
            // line 48
            echo '
    </div>
    <div class="flex-grow">
      ';
            // line 51
            echo twig_call_macro($macros['forms'], 'macro_text', [['id' => 'photoSubpath', 'class' => 'ltr', 'name' => 'photoSubpath', 'value' =>             // line 55
(isset($context['subpath']) || array_key_exists('subpath', $context) ? $context['subpath'] : (function () {
    throw new RuntimeError('Variable "subpath" does not exist.', 55, $this->source);
})()), 'placeholder' => $this->extensions['craft\web\twig\Extension']->translateFilter('path/to/subfolder', 'app'), ]], 51, $context, $this->getSourceContext());
            // line 57
            echo '
    </div>
  </div>
';
            craft\helpers\Template::endProfile('macro', 'assetLocationInput');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    public function getTemplateName()
    {
        return 'settings/users/settings';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [275 => 57,  273 => 55,  272 => 51,  267 => 48,  265 => 47,  264 => 46,  263 => 43,  259 => 41,  256 => 40,  240 => 39,  233 => 151,  231 => 150,  230 => 145,  229 => 144,  228 => 140,  224 => 138,  222 => 137,  221 => 132,  216 => 130,  210 => 126,  208 => 125,  207 => 124,  206 => 120,  203 => 119,  197 => 118,  194 => 117,  189 => 116,  187 => 115,  183 => 113,  181 => 112,  180 => 108,  176 => 106,  174 => 105,  173 => 101,  167 => 100,  163 => 98,  161 => 96,  160 => 92,  155 => 90,  152 => 89,  150 => 88,  147 => 87,  143 => 85,  140 => 82,  135 => 80,  134 => 76,  131 => 75,  128 => 74,  125 => 73,  122 => 72,  119 => 71,  117 => 70,  114 => 69,  108 => 67,  106 => 66,  101 => 64,  96 => 63,  91 => 62,  85 => 3,  78 => 35,  76 => 34,  74 => 33,  70 => 32,  68 => 30,  66 => 29,  64 => 28,  62 => 26,  60 => 25,  58 => 23,  57 => 15,  54 => 11,  52 => 10,  50 => 7,  48 => 5,  46 => 4,  44 => 1,  36 => 3];
    }

    public function getSourceContext()
    {
        return new Source("{% requireAdmin %}

{% extends \"settings/users/_layout\" %}
{% set selectedNavItem = 'settings' %}
{% set fullPageForm = true %}

{% import \"_includes/forms\" as forms %}


{% if settings is not defined %}
  {% set settings = craft.app.projectConfig.get('users') ?? [] %}
{% endif %}

{# set defaults #}
{% set settings = {
  photoVolumeUid: null,
  photoSubpath: null,
  requireEmailVerification: true,
  allowPublicRegistration: false,
  validateOnPublicRegistration: false,
  deactivateByDefault: false,
  defaultGroup: null,
}|merge(settings) %}

{% set hasVolumes = craft.app.volumes.getAllVolumes|length != 0 %}
{% set photoVolume = settings.photoVolumeUid ? craft.app.volumes.getVolumeByUid(settings.photoVolumeUid) : null %}

{% set allVolumes = craft.app.volumes.getAllVolumes() %}
{% set volumeList = [] %}
{% set validVolumeUids = [] %}

{% for volume in allVolumes %}
  {% if volume.getTransformFs().hasUrls %}
    {% set volumeList = volumeList|push({label: volume.name, value: volume.uid}) %}
    {% set validVolumeUids = validVolumeUids|push(volume.uid) %}
  {% endif %}
{% endfor %}

{% macro assetLocationInput(volumeOptions, photoVolume, subpath) %}
  {% import '_includes/forms' as forms %}
  <div class=\"flex\">
    <div>
      {{ forms.volume({
        id: 'photoVolumeId',
        name: 'photoVolumeId',
        options: volumeOptions,
        value: photoVolume.id ?? null,
      }) }}
    </div>
    <div class=\"flex-grow\">
      {{ forms.text({
        id: 'photoSubpath',
        class: 'ltr',
        name: 'photoSubpath',
        value: subpath,
        placeholder: \"path/to/subfolder\"|t('app')
      }) }}
    </div>
  </div>
{% endmacro %}

{% block content %}
  {{ actionInput('user-settings/save-user-settings') }}
  {{ csrfInput() }}

  {% if CraftEdition == CraftPro %}
    <h2 class=\"first\">{{ 'User Photos'|t('app') }}</h2>
  {% endif %}

  {% if hasVolumes %}
    {% set volumeOptions = craft.cp.getVolumeOptions() %}
    {%  if not photoVolume %}
      {% set volumeOptions = volumeOptions|unshift({label: 'Select a volume'|t('app'), value: null}) %}
    {%  endif %}

    {{ forms.field({
      first: true,
      label: \"User Photo Location\"|t('app'),
      instructions: \"Where do you want to store user photos? Note that the subfolder path can contain variables like <code>{username}</code>.\"|t('app')
    }, _self.assetLocationInput(volumeOptions, photoVolume, settings.photoSubpath)) }}
  {% else %}
    {{ forms.field({
      first: true,
      label: \"User Photo Volume\"|t('app')
    }, '<p class=\"error\">' ~ \"No volumes exist yet.\"|t('app') ~ '</p>') }}
  {% endif %}

  {% if CraftEdition == CraftPro %}
    <hr>
    <h2>{{ 'Public Registration'|t('app') }}</h2>

    {{ forms.checkboxField({
      fieldClass: 'first',
      label: 'Allow public registration'|t('app'),
      name: 'allowPublicRegistration',
      checked: settings.allowPublicRegistration,
      toggle: 'publicRegistrationSettings'
    }) }}

    <div id=\"publicRegistrationSettings\" class=\"nested-fields{% if not settings.allowPublicRegistration %} hidden{% endif %}\">
      {{ forms.checkboxField({
        label: 'Validate custom fields on public registration'|t('app'),
        instructions: 'Whether custom fields should be validated during public registration.'|t('app'),
        name: 'validateOnPublicRegistration',
        checked: settings.validateOnPublicRegistration,
      }) }}

      {{ forms.checkboxField({
        label: 'Deactivate users by default'|t('app'),
        instructions: 'Should users who register their own accounts be deactivated by default? This will prevent them from receiving an activation email or logging in.'|t('app'),
        name: 'deactivateByDefault',
        checked: settings.deactivateByDefault,
      }) }}

      {% set groups = [{ label: \"None\"|t('app'), value: '' }] %}
      {% for group in craft.app.userGroups.getAllGroups() %}
        {% set groups = groups|merge([{ label: group.name, value: group.uid }]) %}
      {% endfor %}

      {{ forms.selectField({
        label: \"Default User Group\"|t('app'),
        instructions: \"Choose a user group that publicly-registered members will be added to by default.\"|t('app'),
        name: 'defaultGroup',
        options: groups,
        value: settings.defaultGroup
      }) }}
    </div>

    <hr>
    <h2>{{ 'Security'|t('app') }}</h2>

    {{ forms.checkboxField({
      fieldClass: 'first',
      label: 'Verify email addresses'|t('app'),
      instructions: 'Should new email addresses be verified before getting saved to user accounts? (This also affects new user registration.)'|t('app'),
      name: 'requireEmailVerification',
      checked: settings.requireEmailVerification,
    }) }}

    {{ forms.checkboxSelectField({
      label: 'Require two-step verification'|t('app'),
      instructions: 'Choose which users must use two-step verification when accessing the control panel.'|t('app'),
      name: 'require2fa',
      options: craft.app.userGroups.getAllGroups()
      |map(g => {value: g.uid, label: g.name|t('site')})
      |unshift({value: 'admins', label: 'Admins'|t('app')}),
      showAllOption: true,
      allLabel: 'All users'|t('app'),
      allValue: 'all',
      values: settings.require2fa ?? false,
    }) }}
  {% endif %}
{% endblock %}
", 'settings/users/settings', '/Users/brianhanson/Development/craft5/src/templates/settings/users/settings.twig');
    }
}
