<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Markup;
use Twig\Source;
use Twig\Template;
use Twig\TemplateWrapper;

/* settings/users/settings */
class __TwigTemplate_676a4e36e2ec4029c63d63de5d436024 extends Template
{
    private readonly Source $source;

    /**
     * @var array<string, Template>
     */
    private array $macros = [];

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
    protected function doGetParent(array $context): bool|string|Template|TemplateWrapper
    {
        // line 3
        return 'settings/users/_layout';
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
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
            $context['settings'] = (((craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, ($context['craft'] ?? null), 'app', [], 'any', false, true, false, 11), 'projectConfig', [], 'any', false, true, false, 11), 'get', ['users'], 'method', true, true, false, 11) && ! (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, ($context['craft'] ?? null), 'app', [], 'any', false, true, false, 11), 'projectConfig', [], 'any', false, true, false, 11), 'get', ['users'], 'method', false, false, false, 11) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, ($context['craft'] ?? null), 'app', [], 'any', false, true, false, 11), 'projectConfig', [], 'any', false, true, false, 11), 'get', ['users'], 'method', false, false, false, 11)) : ([]));
        }
        // line 15
        $context['settings'] = $this->extensions['craft\web\twig\Extension']->mergeFilter(['photoVolumeUid' => null, 'photoSubpath' => null, 'requireEmailVerification' => true, 'allowPublicRegistration' => false, 'validateOnPublicRegistration' => false, 'deactivateByDefault' => false, 'defaultGroup' => null],         // line 23
            (isset($context['settings']) || array_key_exists('settings', $context) ? $context['settings'] : (function () {
                throw new RuntimeError('Variable "settings" does not exist.', 23, $this->source);
            })()));
        // line 25
        $context['hasVolumes'] = ($this->extensions['craft\web\twig\Extension']->lengthFilter($this->env, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 25, $this->source);
        })()), 'app', [], 'any', false, false, false, 25), 'volumes', [], 'any', false, false, false, 25), 'getAllVolumes', [], 'any', false, false, false, 25)) != 0);
        // line 26
        $context['photoVolume'] = ((craft\helpers\Template::attribute($this->env, $this->source, (isset($context['settings']) || array_key_exists('settings', $context) ? $context['settings'] : (function () {
            throw new RuntimeError('Variable "settings" does not exist.', 26, $this->source);
        })()), 'photoVolumeUid', [], 'any', false, false, false, 26)) ? (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 26, $this->source);
        })()), 'app', [], 'any', false, false, false, 26), 'volumes', [], 'any', false, false, false, 26), 'getVolumeByUid', [craft\helpers\Template::attribute($this->env, $this->source, (isset($context['settings']) || array_key_exists('settings', $context) ? $context['settings'] : (function () {
            throw new RuntimeError('Variable "settings" does not exist.', 26, $this->source);
        })()), 'photoVolumeUid', [], 'any', false, false, false, 26)], 'method', false, false, false, 26)) : (null));
        // line 28
        $context['allVolumes'] = craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 28, $this->source);
        })()), 'app', [], 'any', false, false, false, 28), 'volumes', [], 'any', false, false, false, 28), 'getAllVolumes', [], 'method', false, false, false, 28);
        // line 29
        $context['volumeList'] = [];
        // line 30
        $context['validVolumeUids'] = [];
        // line 32
        $context['_parent'] = $context;
        $context['_seq'] = CoreExtension::ensureTraversable((isset($context['allVolumes']) || array_key_exists('allVolumes', $context) ? $context['allVolumes'] : (function () {
            throw new RuntimeError('Variable "allVolumes" does not exist.', 32, $this->source);
        })()));
        foreach ($context['_seq'] as $context['_key'] => $context['volume']) {
            // line 33
            if (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, $context['volume'], 'getTransformFs', [], 'method', false, false, false, 33), 'hasUrls', [], 'any', false, false, false, 33)) {
                // line 34
                $context['volumeList'] = $this->extensions['craft\web\twig\Extension']->pushFilter((isset($context['volumeList']) || array_key_exists('volumeList', $context) ? $context['volumeList'] : (function () {
                    throw new RuntimeError('Variable "volumeList" does not exist.', 34, $this->source);
                })()), ['label' => craft\helpers\Template::attribute($this->env, $this->source, $context['volume'], 'name', [], 'any', false, false, false, 34), 'value' => craft\helpers\Template::attribute($this->env, $this->source, $context['volume'], 'uid', [], 'any', false, false, false, 34)]);
                // line 35
                $context['validVolumeUids'] = $this->extensions['craft\web\twig\Extension']->pushFilter((isset($context['validVolumeUids']) || array_key_exists('validVolumeUids', $context) ? $context['validVolumeUids'] : (function () {
                    throw new RuntimeError('Variable "validVolumeUids" does not exist.', 35, $this->source);
                })()), craft\helpers\Template::attribute($this->env, $this->source, $context['volume'], 'uid', [], 'any', false, false, false, 35));
            }
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_key'], $context['volume'], $context['_parent']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 3
        $this->parent = $this->loadTemplate('settings/users/_layout', 'settings/users/settings', 3);
        yield from $this->parent->unwrap()->yield($context, array_merge($this->blocks, $blocks));
        craft\helpers\Template::endProfile('template', 'settings/users/settings');
    }

    // line 62
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_content(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        craft\helpers\Template::beginProfile('block', 'content');
        // line 63
        yield '  ';
        yield craft\helpers\Html::actionInput('user-settings/save-user-settings');
        yield '
  ';
        // line 64
        yield craft\helpers\Html::csrfInput();
        yield '

  ';
        // line 66
        if (((isset($context['CraftEdition']) || array_key_exists('CraftEdition', $context) ? $context['CraftEdition'] : (function () {
            throw new RuntimeError('Variable "CraftEdition" does not exist.', 66, $this->source);
        })()) >= (isset($context['CraftTeam']) || array_key_exists('CraftTeam', $context) ? $context['CraftTeam'] : (function () {
            throw new RuntimeError('Variable "CraftTeam" does not exist.', 66, $this->source);
        })()))) {
            // line 67
            yield '    <h2 class="first">';
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('User Photos', 'app'), 'html', null, true);
            yield '</h2>
  ';
        }
        // line 69
        yield '
  ';
        // line 70
        if ((isset($context['hasVolumes']) || array_key_exists('hasVolumes', $context) ? $context['hasVolumes'] : (function () {
            throw new RuntimeError('Variable "hasVolumes" does not exist.', 70, $this->source);
        })())) {
            // line 71
            yield '    ';
            $context['volumeOptions'] = craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                throw new RuntimeError('Variable "craft" does not exist.', 71, $this->source);
            })()), 'cp', [], 'any', false, false, false, 71), 'getVolumeOptions', [], 'method', false, false, false, 71);
            // line 72
            yield '    ';
            if (! (isset($context['photoVolume']) || array_key_exists('photoVolume', $context) ? $context['photoVolume'] : (function () {
                throw new RuntimeError('Variable "photoVolume" does not exist.', 72, $this->source);
            })())) {
                // line 73
                yield '      ';
                $context['volumeOptions'] = $this->extensions['craft\web\twig\Extension']->unshiftFilter((isset($context['volumeOptions']) || array_key_exists('volumeOptions', $context) ? $context['volumeOptions'] : (function () {
                    throw new RuntimeError('Variable "volumeOptions" does not exist.', 73, $this->source);
                })()), ['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Select a volume', 'app'), 'value' => null]);
                // line 74
                yield '    ';
            }
            // line 75
            yield '
    ';
            // line 76
            yield CoreExtension::callMacro($macros['forms'], 'macro_field', [['first' => true, 'label' => $this->extensions['craft\web\twig\Extension']->translateFilter('User Photo Location', 'app'), 'instructions' => $this->extensions['craft\web\twig\Extension']->translateFilter('Where do you want to store user photos? Note that the subfolder path can contain variables like <code>{username}</code>.', 'app')], CoreExtension::callMacro($macros['_self'], 'macro_assetLocationInput', [            // line 80
                (isset($context['volumeOptions']) || array_key_exists('volumeOptions', $context) ? $context['volumeOptions'] : (function () {
                    throw new RuntimeError('Variable "volumeOptions" does not exist.', 80, $this->source);
                })()), (isset($context['photoVolume']) || array_key_exists('photoVolume', $context) ? $context['photoVolume'] : (function () {
                    throw new RuntimeError('Variable "photoVolume" does not exist.', 80, $this->source);
                })()), craft\helpers\Template::attribute($this->env, $this->source, (isset($context['settings']) || array_key_exists('settings', $context) ? $context['settings'] : (function () {
                    throw new RuntimeError('Variable "settings" does not exist.', 80, $this->source);
                })()), 'photoSubpath', [], 'any', false, false, false, 80)], 80, $context, $this->getSourceContext())], 76, $context, $this->getSourceContext());
            yield '
  ';
        } else {
            // line 82
            yield '    ';
            yield CoreExtension::callMacro($macros['forms'], 'macro_field', [['first' => true, 'label' => $this->extensions['craft\web\twig\Extension']->translateFilter('User Photo Volume', 'app')], (('<p class="error">'.$this->extensions['craft\web\twig\Extension']->translateFilter('No volumes exist yet.', 'app')).'</p>')], 82, $context, $this->getSourceContext());
            // line 85
            yield '
  ';
        }
        // line 87
        yield '
  ';
        // line 88
        if (((isset($context['CraftEdition']) || array_key_exists('CraftEdition', $context) ? $context['CraftEdition'] : (function () {
            throw new RuntimeError('Variable "CraftEdition" does not exist.', 88, $this->source);
        })()) >= (isset($context['CraftTeam']) || array_key_exists('CraftTeam', $context) ? $context['CraftTeam'] : (function () {
            throw new RuntimeError('Variable "CraftTeam" does not exist.', 88, $this->source);
        })()))) {
            // line 89
            yield '    <hr>
    <h2>';
            // line 90
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Security', 'app'), 'html', null, true);
            yield '</h2>

    ';
            // line 92
            yield CoreExtension::callMacro($macros['forms'], 'macro_checkboxSelectField', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Require two-step verification', 'app'), 'instructions' => $this->extensions['craft\web\twig\Extension']->translateFilter('Choose which users must use two-step verification when accessing the control panel.', 'app'), 'name' => 'require2fa', 'options' => $this->extensions['craft\web\twig\Extension']->unshiftFilter($this->extensions['craft\web\twig\Extension']->mapFilter($this->env, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source,             // line 96
                (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                    throw new RuntimeError('Variable "craft" does not exist.', 96, $this->source);
                })()), 'app', [], 'any', false, false, false, 96), 'userGroups', [], 'any', false, false, false, 96), 'getAllGroups', [], 'method', false, false, false, 96),             // line 97
                function ($__g__) use ($context) {
                    $context['g'] = $__g__;

                    return ['value' => craft\helpers\Template::attribute($this->env, $this->source, (isset($context['g']) || array_key_exists('g', $context) ? $context['g'] : (function () {
                        throw new RuntimeError('Variable "g" does not exist.', 97, $this->source);
                    })()), 'uid', [], 'any', false, false, false, 97), 'label' => $this->extensions['craft\web\twig\Extension']->translateFilter(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['g']) || array_key_exists('g', $context) ? $context['g'] : (function () {
                        throw new RuntimeError('Variable "g" does not exist.', 97, $this->source);
                    })()), 'name', [], 'any', false, false, false, 97), 'site')];
                }), ['value' => 'admins', 'label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Admins', 'app')]), 'showAllOption' => true, 'allLabel' => $this->extensions['craft\web\twig\Extension']->translateFilter('All users', 'app'), 'allValue' => 'all', 'values' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 102
                    ($context['settings'] ?? null), 'require2fa', [], 'any', true, true, false, 102) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['settings'] ?? null), 'require2fa', [], 'any', false, false, false, 102) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['settings'] ?? null), 'require2fa', [], 'any', false, false, false, 102)) : (false))]], 92, $context, $this->getSourceContext());
            // line 103
            yield '
  ';
        }
        // line 105
        yield '
  ';
        // line 106
        if (((isset($context['CraftEdition']) || array_key_exists('CraftEdition', $context) ? $context['CraftEdition'] : (function () {
            throw new RuntimeError('Variable "CraftEdition" does not exist.', 106, $this->source);
        })()) >= (isset($context['CraftPro']) || array_key_exists('CraftPro', $context) ? $context['CraftPro'] : (function () {
            throw new RuntimeError('Variable "CraftPro" does not exist.', 106, $this->source);
        })()))) {
            // line 107
            yield '    ';
            yield CoreExtension::callMacro($macros['forms'], 'macro_lightswitchField', [['fieldClass' => 'first', 'label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Verify email addresses', 'app'), 'instructions' => $this->extensions['craft\web\twig\Extension']->translateFilter('Should new email addresses be verified before getting saved to user accounts? (This also affects new user registration.)', 'app'), 'name' => 'requireEmailVerification', 'on' => craft\helpers\Template::attribute($this->env, $this->source,             // line 112
                (isset($context['settings']) || array_key_exists('settings', $context) ? $context['settings'] : (function () {
                    throw new RuntimeError('Variable "settings" does not exist.', 112, $this->source);
                })()), 'requireEmailVerification', [], 'any', false, false, false, 112)]], 107, $context, $this->getSourceContext());
            // line 113
            yield '

    <hr>
    <h2>';
            // line 116
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Public Registration', 'app'), 'html', null, true);
            yield '</h2>

    ';
            // line 118
            yield CoreExtension::callMacro($macros['forms'], 'macro_lightswitchField', [['fieldClass' => 'first', 'label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Allow public registration', 'app'), 'name' => 'allowPublicRegistration', 'on' => craft\helpers\Template::attribute($this->env, $this->source,             // line 122
                (isset($context['settings']) || array_key_exists('settings', $context) ? $context['settings'] : (function () {
                    throw new RuntimeError('Variable "settings" does not exist.', 122, $this->source);
                })()), 'allowPublicRegistration', [], 'any', false, false, false, 122), 'toggle' => 'publicRegistrationSettings']], 118, $context, $this->getSourceContext());
            // line 124
            yield '

    <div id="publicRegistrationSettings" class="nested-fields';
            // line 126
            if (! craft\helpers\Template::attribute($this->env, $this->source, (isset($context['settings']) || array_key_exists('settings', $context) ? $context['settings'] : (function () {
                throw new RuntimeError('Variable "settings" does not exist.', 126, $this->source);
            })()), 'allowPublicRegistration', [], 'any', false, false, false, 126)) {
                yield ' hidden';
            }
            yield '">
      ';
            // line 127
            yield CoreExtension::callMacro($macros['forms'], 'macro_lightswitchField', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Validate custom fields on public registration', 'app'), 'instructions' => $this->extensions['craft\web\twig\Extension']->translateFilter('Whether custom fields should be validated during public registration.', 'app'), 'name' => 'validateOnPublicRegistration', 'on' => craft\helpers\Template::attribute($this->env, $this->source,             // line 131
                (isset($context['settings']) || array_key_exists('settings', $context) ? $context['settings'] : (function () {
                    throw new RuntimeError('Variable "settings" does not exist.', 131, $this->source);
                })()), 'validateOnPublicRegistration', [], 'any', false, false, false, 131)]], 127, $context, $this->getSourceContext());
            // line 132
            yield '

      ';
            // line 134
            yield CoreExtension::callMacro($macros['forms'], 'macro_lightswitchField', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Deactivate users by default', 'app'), 'instructions' => $this->extensions['craft\web\twig\Extension']->translateFilter('Should users who register their own accounts be deactivated by default? This will prevent them from receiving an activation email or logging in.', 'app'), 'name' => 'deactivateByDefault', 'on' => craft\helpers\Template::attribute($this->env, $this->source,             // line 138
                (isset($context['settings']) || array_key_exists('settings', $context) ? $context['settings'] : (function () {
                    throw new RuntimeError('Variable "settings" does not exist.', 138, $this->source);
                })()), 'deactivateByDefault', [], 'any', false, false, false, 138)]], 134, $context, $this->getSourceContext());
            // line 139
            yield '

      ';
            // line 141
            $context['groups'] = [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('None', 'app'), 'value' => '']];
            // line 142
            yield '      ';
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable(craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                throw new RuntimeError('Variable "craft" does not exist.', 142, $this->source);
            })()), 'app', [], 'any', false, false, false, 142), 'userGroups', [], 'any', false, false, false, 142), 'getAllGroups', [], 'method', false, false, false, 142));
            foreach ($context['_seq'] as $context['_key'] => $context['group']) {
                // line 143
                yield '        ';
                $context['groups'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['groups']) || array_key_exists('groups', $context) ? $context['groups'] : (function () {
                    throw new RuntimeError('Variable "groups" does not exist.', 143, $this->source);
                })()), [['label' => craft\helpers\Template::attribute($this->env, $this->source, $context['group'], 'name', [], 'any', false, false, false, 143), 'value' => craft\helpers\Template::attribute($this->env, $this->source, $context['group'], 'uid', [], 'any', false, false, false, 143)]]);
                // line 144
                yield '      ';
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_key'], $context['group'], $context['_parent']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 145
            yield '
      ';
            // line 146
            yield CoreExtension::callMacro($macros['forms'], 'macro_selectField', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Default User Group', 'app'), 'instructions' => $this->extensions['craft\web\twig\Extension']->translateFilter('Choose a user group that publicly-registered members will be added to by default.', 'app'), 'name' => 'defaultGroup', 'options' =>             // line 150
                (isset($context['groups']) || array_key_exists('groups', $context) ? $context['groups'] : (function () {
                    throw new RuntimeError('Variable "groups" does not exist.', 150, $this->source);
                })()), 'value' => craft\helpers\Template::attribute($this->env, $this->source,             // line 151
                    (isset($context['settings']) || array_key_exists('settings', $context) ? $context['settings'] : (function () {
                        throw new RuntimeError('Variable "settings" does not exist.', 151, $this->source);
                    })()), 'defaultGroup', [], 'any', false, false, false, 151)]], 146, $context, $this->getSourceContext());
            // line 152
            yield '
    </div>
  ';
        }
        craft\helpers\Template::endProfile('block', 'content');
        yield from [];
    }

    // line 39
    public function macro_assetLocationInput($__volumeOptions__ = null, $__photoVolume__ = null, $__subpath__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = [
            'volumeOptions' => $__volumeOptions__,
            'photoVolume' => $__photoVolume__,
            'subpath' => $__subpath__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context, $macros) {
            craft\helpers\Template::beginProfile('macro', 'assetLocationInput');
            // line 40
            yield '  ';
            $macros['forms'] = $this->loadTemplate('_includes/forms', 'settings/users/settings', 40)->unwrap();
            // line 41
            yield '  <div class="flex">
    <div>
      ';
            // line 43
            yield CoreExtension::callMacro($macros['forms'], 'macro_volume', [['id' => 'photoVolumeId', 'name' => 'photoVolumeId', 'options' =>             // line 46
(isset($context['volumeOptions']) || array_key_exists('volumeOptions', $context) ? $context['volumeOptions'] : (function () {
    throw new RuntimeError('Variable "volumeOptions" does not exist.', 46, $this->source);
})()), 'value' => (((craft\helpers\Template::attribute($this->env, $this->source,             // line 47
    ($context['photoVolume'] ?? null), 'id', [], 'any', true, true, false, 47) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['photoVolume'] ?? null), 'id', [], 'any', false, false, false, 47) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['photoVolume'] ?? null), 'id', [], 'any', false, false, false, 47)) : (null))]], 43, $context, $this->getSourceContext());
            // line 48
            yield '
    </div>
    <div class="flex-grow">
      ';
            // line 51
            yield CoreExtension::callMacro($macros['forms'], 'macro_text', [['id' => 'photoSubpath', 'class' => 'ltr', 'name' => 'photoSubpath', 'value' =>             // line 55
(isset($context['subpath']) || array_key_exists('subpath', $context) ? $context['subpath'] : (function () {
    throw new RuntimeError('Variable "subpath" does not exist.', 55, $this->source);
})()), 'placeholder' => $this->extensions['craft\web\twig\Extension']->translateFilter('path/to/subfolder', 'app')]], 51, $context, $this->getSourceContext());
            // line 57
            yield '
    </div>
  </div>
';
            craft\helpers\Template::endProfile('macro', 'assetLocationInput');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return 'settings/users/settings';
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
        return [289 => 57,  287 => 55,  286 => 51,  281 => 48,  279 => 47,  278 => 46,  277 => 43,  273 => 41,  270 => 40,  255 => 39,  246 => 152,  244 => 151,  243 => 150,  242 => 146,  239 => 145,  233 => 144,  230 => 143,  225 => 142,  223 => 141,  219 => 139,  217 => 138,  216 => 134,  212 => 132,  210 => 131,  209 => 127,  203 => 126,  199 => 124,  197 => 122,  196 => 118,  191 => 116,  186 => 113,  184 => 112,  182 => 107,  180 => 106,  177 => 105,  173 => 103,  171 => 102,  170 => 97,  169 => 96,  168 => 92,  163 => 90,  160 => 89,  158 => 88,  155 => 87,  151 => 85,  148 => 82,  143 => 80,  142 => 76,  139 => 75,  136 => 74,  133 => 73,  130 => 72,  127 => 71,  125 => 70,  122 => 69,  116 => 67,  114 => 66,  109 => 64,  104 => 63,  96 => 62,  90 => 3,  83 => 35,  81 => 34,  79 => 33,  75 => 32,  73 => 30,  71 => 29,  69 => 28,  67 => 26,  65 => 25,  63 => 23,  62 => 15,  59 => 11,  57 => 10,  55 => 7,  53 => 5,  51 => 4,  49 => 1,  41 => 3];
    }

    public function getSourceContext(): Source
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

  {% if CraftEdition >= CraftTeam %}
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

  {% if CraftEdition >= CraftTeam %}
    <hr>
    <h2>{{ 'Security'|t('app') }}</h2>

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

  {% if CraftEdition >= CraftPro %}
    {{ forms.lightswitchField({
      fieldClass: 'first',
      label: 'Verify email addresses'|t('app'),
      instructions: 'Should new email addresses be verified before getting saved to user accounts? (This also affects new user registration.)'|t('app'),
      name: 'requireEmailVerification',
      on: settings.requireEmailVerification,
    }) }}

    <hr>
    <h2>{{ 'Public Registration'|t('app') }}</h2>

    {{ forms.lightswitchField({
      fieldClass: 'first',
      label: 'Allow public registration'|t('app'),
      name: 'allowPublicRegistration',
      on: settings.allowPublicRegistration,
      toggle: 'publicRegistrationSettings'
    }) }}

    <div id=\"publicRegistrationSettings\" class=\"nested-fields{% if not settings.allowPublicRegistration %} hidden{% endif %}\">
      {{ forms.lightswitchField({
        label: 'Validate custom fields on public registration'|t('app'),
        instructions: 'Whether custom fields should be validated during public registration.'|t('app'),
        name: 'validateOnPublicRegistration',
        on: settings.validateOnPublicRegistration,
      }) }}

      {{ forms.lightswitchField({
        label: 'Deactivate users by default'|t('app'),
        instructions: 'Should users who register their own accounts be deactivated by default? This will prevent them from receiving an activation email or logging in.'|t('app'),
        name: 'deactivateByDefault',
        on: settings.deactivateByDefault,
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
  {% endif %}
{% endblock %}
", 'settings/users/settings', '/tmp/packages/craft5/src/templates/settings/users/settings.twig');
    }
}
