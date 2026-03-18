<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp;

use CommerceGuys\Addressing\Subdivision\SubdivisionRepository as BaseSubdivisionRepository;
use CraftCms\Cms\Address\Addresses;
use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Cp\Html\ContentHtml;
use CraftCms\Cms\Cp\Html\MenuHtml;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use yii\helpers\Markdown;
use yii\validators\RequiredValidator;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

readonly class FormFields
{
    public static function fieldHtml(string|callable $input, array $config = []): string
    {
        $attribute = $config['attribute'] ?? $config['id'] ?? null;
        $id = $config['id'] ??= 'field'.mt_rand();
        $labelId = $config['labelId'] ?? "$id-label";
        $instructionsId = $config['instructionsId'] ?? "$id-instructions";
        $tipId = $config['tipId'] ?? "$id-tip";
        $warningId = $config['warningId'] ?? "$id-warning";
        $errorsId = $config['errorsId'] ?? "$id-errors";
        $statusId = $config['statusId'] ?? "$id-status";

        $instructions = $config['instructions'] ?? null;
        $tip = $config['tip'] ?? null;
        $warning = $config['warning'] ?? null;
        $errors = $config['errors'] ?? null;
        $status = $config['status'] ?? null;
        $disabled = $config['disabled'] ?? false;
        $static = $config['static'] ?? false;

        $fieldset = $config['fieldset'] ?? false;
        $fieldId = $config['fieldId'] ?? "$id-field";
        $label = $config['fieldLabel'] ?? $config['label'] ?? null;

        $data = $config['data'] ?? [];

        if ($label === '__blank__') {
            $label = null;
        }

        $siteId = Sites::isMultiSite() && isset($config['siteId']) ? (int) $config['siteId'] : null;

        if (is_callable($input) || str_starts_with($input, 'template:')) {
            // Set labelledBy and describedBy values in case the input template supports it
            if (! isset($config['labelledBy']) && $label) {
                $config['labelledBy'] = $labelId;
            }
            if (! isset($config['describedBy'])) {
                $descriptorIds = array_filter([
                    $errors ? $errorsId : null,
                    $status ? $statusId : null,
                    $instructions ? $instructionsId : null,
                    $tip ? $tipId : null,
                    $warning ? $warningId : null,
                ]);
                $config['describedBy'] = $descriptorIds ? implode(' ', $descriptorIds) : null;
            }

            if (is_callable($input)) {
                $input = $input($config);
            } else {
                $input = self::renderTemplate(substr($input, 9), $config);
            }
        }

        if ($siteId) {
            $site = Sites::getSiteById($siteId);
            if (! $site) {
                throw new InvalidArgumentException("Invalid site ID: $siteId");
            }
        } else {
            $site = null;
        }

        $required = (bool) ($config['required'] ?? false);
        $instructionsPosition = $config['instructionsPosition'] ?? 'before';
        $orientation = $config['orientation'] ?? ($site ? $site->getLocale() : I18N::getLocale())->getOrientation();
        $translatable = Sites::isMultiSite() ? ($config['translatable'] ?? ($site !== null)) : false;

        $fieldClass = array_merge(array_filter([
            'field',
            ($config['first'] ?? false) ? 'first' : null,
            $errors ? 'has-errors' : null,
        ]), Html::explodeClass($config['fieldClass'] ?? []));

        $showAttribute = (
            ($config['showAttribute'] ?? false) &&
            Auth::user()->isAdmin() &&
            Auth::user()->getPreference('showFieldHandles')
        );
        $showActionMenu = (
            ! empty($config['actionMenuItems']) &&
            ($label || $showAttribute || isset($config['labelExtra']))
        );
        $showLabelExtra = $showAttribute || $showActionMenu || isset($config['labelExtra']);

        $instructionsHtml = $instructions
            ? Html::tag('div', app(ContentHtml::class)->parseMarkdown($instructions), [
                'id' => $instructionsId,
                'class' => ['instructions'],
            ])
            : '';

        $translationDescription = $config['translationDescription'] ?? t('This field is translatable.');
        $translationIconHtml = Html::button('', [
            'class' => ['t9n-indicator', 'prevent-autofocus'],
            'data' => [
                'icon' => 'language',
            ],
            'aria' => [
                'label' => $translationDescription,
            ],
        ]);

        $translationIconHtml = Html::tag('craft-tooltip', $translationIconHtml, [
            'placement' => 'bottom',
            'max-width' => '200px',
            'text' => $translationDescription,
            'delay' => '1000',
        ]);

        if ($label) {
            $labelHtml = $label.(
                ($required
                    ? Html::tag('span', t('Required'), [
                        'class' => ['visually-hidden'],
                    ]).
                    Html::tag('span', '', [
                        'class' => ['required'],
                        'aria' => [
                            'hidden' => 'true',
                        ],
                    ])
                    : '').
                ($translatable ? $translationIconHtml : '')
            );
        } else {
            $labelHtml = '';
        }

        return
            Html::beginTag('div', Arr::merge(
                [
                    'class' => $fieldClass,
                    'id' => $fieldId,
                    'aria' => [
                        'labelledby' => $fieldset ? $labelId : null,
                    ],
                    'role' => $fieldset ? 'group' : null,
                    'data' => [
                        'attribute' => $attribute,
                    ] + $data,
                ],
                $config['fieldAttributes'] ?? []
            )).
            ($status
                ? Html::beginTag('div', [
                    'id' => $statusId,
                    'class' => ['status-badge', Str::toString($status[0])],
                    'title' => $status[1],
                    'aria-hidden' => 'true',
                ]).
                Html::tag('span', $status[1], [
                    'class' => 'visually-hidden',
                ]).
                Html::endTag('div')
                : '').
            (($label || $showLabelExtra)
                ? (
                    Html::beginTag('div', ['class' => 'heading']).
                    ($config['headingPrefix'] ?? '').
                    ($label
                        ? Html::tag($fieldset ? 'legend' : 'label', $labelHtml, Arr::merge([
                            'id' => $labelId,
                            'class' => $config['labelClass'] ?? null,
                            'for' => ! $fieldset ? $id : null,
                        ], $config['labelAttributes'] ?? []))
                        : '').
                    ($static ? Html::tag('span', t('Read Only'), [
                        'class' => ['read-only-badge'],
                    ]) : '').
                    ($showLabelExtra
                        ? Html::tag('div', '', ['class' => 'flex-grow']).
                        ($showActionMenu ? app(MenuHtml::class)->disclosureMenu($config['actionMenuItems'], [
                            'hiddenLabel' => t('Actions'),
                            'buttonAttributes' => [
                                'class' => ['action-btn', 'small', 'prevent-autofocus'],
                            ],
                        ]) : '').
                        ($showAttribute ? self::renderTemplate('_includes/forms/copytextbtn', [
                            'id' => "$id-attribute",
                            'class' => ['code', 'small', 'light'],
                            'value' => $config['attribute'],
                        ]) : '').
                        ($config['labelExtra'] ?? '')
                        : '').
                    ($config['headingSuffix'] ?? '').
                    Html::endTag('div')
                )
                : '').
            ($instructionsPosition === 'before' ? $instructionsHtml : '').
            Html::tag('div', $input, Arr::merge(
                [
                    'class' => array_filter([
                        'input',
                        $orientation,
                        $errors ? 'errors' : null,
                        $disabled ? 'disabled' : null,
                    ]),
                ],
                $config['inputContainerAttributes'] ?? []
            )).
            ($instructionsPosition === 'after' ? $instructionsHtml : '').
            self::noticeHtml($tipId, 'notice', t('Tip:'), $tip).
            self::noticeHtml($warningId, 'warning', t('Warning:'), $warning).
            ($errors
                ? self::renderTemplate('_includes/forms/errorList', [
                    'id' => $errorsId,
                    'errors' => $errors,
                ])
                : '').
            Html::endTag('div');
    }

    private static function noticeHtml(string $id, string $class, string $label, ?string $message): string
    {
        if (! $message) {
            return '';
        }

        return
            Html::beginTag('p', [
                'id' => $id,
                'class' => [$class, 'has-icon'],
            ]).
            Html::tag('span', '', [
                'class' => 'icon',
                'aria' => [
                    'hidden' => 'true',
                ],
            ]).
            Html::tag('span', "$label ", [
                'class' => 'visually-hidden',
            ]).
            Html::tag('span', Html::decodeDoubles(Markdown::processParagraph(Html::encodeInvalidTags($message)))).
            Html::endTag('p');
    }

    public static function buttonHtml(array $config): string
    {
        return self::renderTemplate('_includes/forms/button', $config);
    }

    public static function buttonGroupHtml(array $config): string
    {
        return self::renderTemplate('_includes/forms/buttonGroup', $config);
    }

    public static function buttonGroupFieldHtml(array $config): string
    {
        $config['id'] ??= 'buttongroup'.mt_rand();
        $config['fieldset'] = true;

        return self::fieldHtml('template:_includes/forms/buttonGroup', $config);
    }

    public static function checkboxFieldHtml(array $config): string
    {
        $config['id'] ??= 'checkbox'.mt_rand();

        $config['fieldClass'] = Html::explodeClass($config['fieldClass'] ?? []);
        $config['fieldClass'][] = 'checkboxfield';
        $config['instructionsPosition'] ??= 'after';

        // Don't pass along `label` since it's ambiguous
        unset($config['label']);

        return self::fieldHtml('template:_includes/forms/checkbox', $config);
    }

    public static function checkboxSelectFieldHtml(array $config): string
    {
        $config['id'] ??= 'checkboxselect'.mt_rand();
        $config['fieldset'] = true;

        return self::fieldHtml('template:_includes/forms/checkboxSelect', $config);
    }

    public static function checkboxGroupHtml(array $config): string
    {
        return self::renderTemplate('_includes/forms/checkboxGroup', $config);
    }

    public static function checkboxGroupFieldHtml(array $config): string
    {
        $config['id'] ??= 'checkboxgroup'.mt_rand();

        return self::fieldHtml('template:_includes/forms/checkboxGroup', $config);
    }

    public static function colorHtml(array $config): string
    {
        return self::renderTemplate('_includes/forms/color', $config);
    }

    public static function colorFieldHtml(array $config): string
    {
        $config['id'] ??= 'color'.mt_rand();
        $config['fieldset'] = true;

        return self::fieldHtml('template:_includes/forms/color', $config);
    }

    public static function colorSelectFieldHtml(array $config): string
    {
        $config['id'] ??= 'colorselect'.mt_rand();

        return self::fieldHtml('template:_includes/forms/colorSelect', $config);
    }

    public static function iconPickerHtml(array $config): string
    {
        return self::renderTemplate('_includes/forms/iconPicker', $config);
    }

    public static function iconPickerFieldHtml(array $config): string
    {
        $config['id'] ??= 'iconpicker'.mt_rand();

        return self::fieldHtml('template:_includes/forms/iconPicker', $config);
    }

    public static function editableTableHtml(array $config): string
    {
        return self::renderTemplate('_includes/forms/editableTable', $config);
    }

    public static function editableTableFieldHtml(array $config): string
    {
        $config['id'] ??= 'editabletable'.mt_rand();

        return self::fieldHtml('template:_includes/forms/editableTable', $config);
    }

    public static function lightswitchHtml(array $config): string
    {
        return self::renderTemplate('_includes/forms/lightswitch', $config);
    }

    public static function lightswitchFieldHtml(array $config): string
    {
        $config['id'] ??= 'lightswitch'.mt_rand();

        $config['fieldClass'] = Html::explodeClass($config['fieldClass'] ?? []);
        $config['fieldClass'][] = 'lightswitch-field';

        // Don't pass along `label` since it's ambiguous
        $config['fieldLabel'] ??= $config['label'] ?? null;
        unset($config['label']);

        return self::fieldHtml('template:_includes/forms/lightswitch', $config);
    }

    public static function rangeHtml(array $config): string
    {
        return self::renderTemplate('_includes/forms/range', $config);
    }

    public static function rangeFieldHtml(array $config): string
    {
        $config['id'] ??= 'range'.mt_rand();

        return self::fieldHtml('template:_includes/forms/range', $config);
    }

    public static function moneyInputHtml(array $config): string
    {
        return self::renderTemplate('_includes/forms/money', $config);
    }

    public static function moneyFieldHtml(array $config): string
    {
        $config['id'] ??= 'money'.mt_rand();

        return self::fieldHtml('template:_includes/forms/money', $config);
    }

    public static function selectHtml(array $config): string
    {
        return self::renderTemplate('_includes/forms/select', $config);
    }

    public static function selectFieldHtml(array $config): string
    {
        $config['id'] ??= 'select'.mt_rand();

        return self::fieldHtml('template:_includes/forms/select', $config);
    }

    public static function customSelectHtml(array $config): string
    {
        return self::renderTemplate('_includes/forms/customSelect', $config);
    }

    public static function customSelectFieldHtml(array $config): string
    {
        $config['id'] ??= 'customselect'.mt_rand();

        return self::fieldHtml('template:_includes/forms/customSelect', $config);
    }

    public static function selectizeHtml(array $config): string
    {
        return self::renderTemplate('_includes/forms/selectize', $config);
    }

    public static function selectizeFieldHtml(array $config): string
    {
        $config['id'] ??= 'selectize'.mt_rand();

        return self::fieldHtml('template:_includes/forms/selectize', $config);
    }

    public static function multiSelectHtml(array $config): string
    {
        return self::renderTemplate('_includes/forms/multiselect', $config);
    }

    public static function multiSelectFieldHtml(array $config): string
    {
        $config['id'] ??= 'multiselect'.mt_rand();

        return self::fieldHtml('template:_includes/forms/multiselect', $config);
    }

    public static function textHtml(array $config): string
    {
        return self::renderTemplate('_includes/forms/text', $config);
    }

    public static function textFieldHtml(array $config): string
    {
        $config['id'] ??= 'text'.mt_rand();

        return self::fieldHtml('template:_includes/forms/text', $config);
    }

    public static function textareaHtml(array $config): string
    {
        return self::renderTemplate('_includes/forms/textarea', $config);
    }

    public static function textareaFieldHtml(array $config): string
    {
        $config['id'] ??= 'textarea'.mt_rand();

        return self::fieldHtml('template:_includes/forms/textarea', $config);
    }

    public static function dateHtml(array $config): string
    {
        return self::renderTemplate('_includes/forms/date', $config);
    }

    public static function dateFieldHtml(array $config): string
    {
        $config['id'] ??= 'date'.mt_rand();

        return self::fieldHtml('template:_includes/forms/date', $config);
    }

    public static function timeHtml(array $config): string
    {
        return self::renderTemplate('_includes/forms/time', $config);
    }

    public static function timeFieldHtml(array $config): string
    {
        $config['id'] ??= 'time'.mt_rand();

        return self::fieldHtml('template:_includes/forms/time', $config);
    }

    public static function dateTimeFieldHtml(array $config): string
    {
        $config += [
            'id' => 'datetime'.mt_rand(),
            'fieldset' => true,
        ];

        return self::fieldHtml('template:_includes/forms/datetime', $config);
    }

    public static function elementSelectHtml(array $config): string
    {
        return self::renderTemplate('_includes/forms/elementSelect', $config);
    }

    public static function elementSelectFieldHtml(array $config): string
    {
        $config['id'] ??= 'elementselect'.mt_rand();

        return self::fieldHtml('template:_includes/forms/elementSelect', $config);
    }

    public static function entryTypeSelectHtml(array $config): string
    {
        return self::renderTemplate('_includes/forms/entryTypeSelect', $config);
    }

    public static function entryTypeSelectFieldHtml(array $config): string
    {
        $config['id'] ??= 'entrytypeselect'.mt_rand();

        return self::fieldHtml('template:_includes/forms/entryTypeSelect', $config);
    }

    public static function autosuggestFieldHtml(array $config): string
    {
        $config['id'] ??= 'autosuggest'.mt_rand();

        // Suggest an environment variable / alias?
        if ($config['suggestEnvVars'] ?? false) {
            $value = $config['value'] ?? '';
            if (! isset($config['tip']) && (! isset($value[0]) || ! in_array($value[0], ['$', '@']))) {
                if ($config['suggestAliases'] ?? false) {
                    $config['tip'] = t('This can begin with an environment variable or alias.');
                } else {
                    $config['tip'] = t('This can begin with an environment variable.');
                }
                $config['tip'] .= ' '.
                    Html::a(t('Learn more'), 'https://craftcms.com/docs/5.x/configure.html#control-panel-settings', [
                        'class' => 'go',
                    ]);
            } elseif (
                ! isset($config['warning']) &&
                ($value === '@web' || str_starts_with((string) $value, '@web/'))
            ) {
                $config['warning'] = t('The `@web` alias is not recommended.');
            }
        }

        return self::fieldHtml('template:_includes/forms/autosuggest', $config);
    }

    public static function addressFieldsHtml(Address $address, bool $static = false): string
    {
        $requiredFields = [];
        $scenario = $address->getScenario();
        $address->setScenario(Element::SCENARIO_LIVE);
        $activeValidators = $address->getActiveValidators();
        $address->setScenario($scenario);
        $belongsToCurrentUser = $address->getBelongsToCurrentUser();

        foreach ($activeValidators as $validator) {
            if ($validator instanceof RequiredValidator) {
                foreach ($validator->getAttributeNames() as $attr) {
                    if ($validator->when === null || call_user_func($validator->when, $address, $attr)) {
                        $requiredFields[$attr] = true;
                    }
                }
            }
        }

        $addressesService = app(Addresses::class);
        $visibleFields = array_flip(array_merge(
            $addressesService->getUsedFields($address->countryCode),
            $addressesService->getUsedSubdivisionFields($address->countryCode),
        )) + $requiredFields;

        $parents = self::subdivisionParents($address, $visibleFields);

        return
            self::textFieldHtml([
                'status' => $address->getAttributeStatus('addressLine1'),
                'label' => $address->getAttributeLabel('addressLine1'),
                'id' => 'addressLine1',
                'name' => 'addressLine1',
                'value' => $address->addressLine1,
                'autocomplete' => $belongsToCurrentUser ? 'address-line1' : 'off',
                'required' => isset($requiredFields['addressLine1']),
                'errors' => ! $static ? $address->errors()->get('addressLine1') : [],
                'data' => [
                    'error-key' => 'addressLine1',
                ],
                'disabled' => $static,
            ]).
            self::textFieldHtml([
                'status' => $address->getAttributeStatus('addressLine2'),
                'label' => $address->getAttributeLabel('addressLine2'),
                'id' => 'addressLine2',
                'name' => 'addressLine2',
                'value' => $address->addressLine2,
                'autocomplete' => $belongsToCurrentUser ? 'address-line2' : 'off',
                'required' => isset($requiredFields['addressLine2']),
                'errors' => ! $static ? $address->errors()->get('addressLine2') : [],
                'data' => [
                    'error-key' => 'addressLine2',
                ],
                'disabled' => $static,
            ]).
            self::textFieldHtml([
                'status' => $address->getAttributeStatus('addressLine3'),
                'label' => $address->getAttributeLabel('addressLine3'),
                'id' => 'addressLine3',
                'name' => 'addressLine3',
                'value' => $address->addressLine3,
                'autocomplete' => $belongsToCurrentUser ? 'address-line3' : 'off',
                'required' => isset($requiredFields['addressLine3']),
                'errors' => ! $static ? $address->errors()->get('addressLine3') : [],
                'data' => [
                    'error-key' => 'addressLine3',
                ],
                'disabled' => $static,
            ]).
            self::subdivisionField(
                $address,
                'administrativeArea',
                $belongsToCurrentUser ? 'address-level1' : 'off',
                isset($visibleFields['administrativeArea']),
                isset($requiredFields['administrativeArea']),
                [$address->countryCode],
                true,
                $static,
            ).
            self::subdivisionField(
                $address,
                'locality',
                $belongsToCurrentUser ? 'address-level2' : 'off',
                isset($visibleFields['locality']),
                isset($requiredFields['locality']),
                $parents['locality'],
                true,
                $static,
            ).
            self::subdivisionField(
                $address,
                'dependentLocality',
                $belongsToCurrentUser ? 'address-level3' : 'off',
                isset($visibleFields['dependentLocality']),
                isset($requiredFields['dependentLocality']),
                $parents['dependentLocality'],
                false,
                $static,
            ).
            self::textFieldHtml([
                'fieldClass' => array_filter([
                    'width-50',
                    ! isset($visibleFields['postalCode']) ? 'hidden' : null,
                ]),
                'status' => $address->getAttributeStatus('postalCode'),
                'label' => $address->getAttributeLabel('postalCode'),
                'id' => 'postalCode',
                'name' => 'postalCode',
                'value' => $address->postalCode,
                'autocomplete' => $belongsToCurrentUser ? 'postal-code' : 'off',
                'required' => isset($requiredFields['postalCode']),
                'errors' => ! $static ? $address->errors()->get('postalCode') : [],
                'data' => [
                    'error-key' => 'postalCode',
                ],
                'disabled' => $static,
            ]).
            self::textFieldHtml([
                'fieldClass' => array_filter([
                    'width-50',
                    ! isset($visibleFields['sortingCode']) ? 'hidden' : null,
                ]),
                'status' => $address->getAttributeStatus('sortingCode'),
                'label' => $address->getAttributeLabel('sortingCode'),
                'id' => 'sortingCode',
                'name' => 'sortingCode',
                'value' => $address->sortingCode,
                'required' => isset($requiredFields['sortingCode']),
                'errors' => ! $static ? $address->errors()->get('sortingCode') : [],
                'data' => [
                    'error-key' => 'sortingCode',
                ],
                'disabled' => $static,
            ]);
    }

    private static function subdivisionParents(Address $address, array $visibleFields): array
    {
        $baseSubdivisionRepository = new BaseSubdivisionRepository;

        $localityParents = [$address->countryCode];
        $administrativeAreas = $baseSubdivisionRepository->getList([$address->countryCode]);

        if (array_key_exists('administrativeArea', $visibleFields) || empty($administrativeAreas)) {
            $localityParents[] = $address->administrativeArea;
        }

        $dependentLocalityParents = $localityParents;
        $localities = $baseSubdivisionRepository->getList($localityParents);
        if (array_key_exists('locality', $visibleFields) || empty($localities)) {
            $dependentLocalityParents[] = $address->locality;
        }

        return ['locality' => $localityParents, 'dependentLocality' => $dependentLocalityParents];
    }

    private static function subdivisionField(
        Address $address,
        string $name,
        string $autocomplete,
        bool $visible,
        bool $required,
        ?array $parents,
        bool $spinner,
        bool $static = false,
    ): string {
        $value = $address->$name;
        $options = app(Addresses::class)->getSubdivisionRepository()->getList($parents, app()->getLocale());

        if ($options) {
            // Persist invalid values in the UI
            if ($value && ! isset($options[$value])) {
                $options[$value] = $value;
            }

            if ($spinner) {
                $errors = ! $static ? $address->errors()->get($name) : [];
                $input =
                    Html::beginTag('div', [
                        'class' => ['flex', 'flex-nowrap'],
                    ]).
                    self::selectizeHtml([
                        'id' => $name,
                        'name' => $name,
                        'value' => $value,
                        'options' => $options,
                        'errors' => $errors,
                        'autocomplete' => $autocomplete,
                        'disabled' => $static,
                    ]).
                    Html::tag('div', '', [
                        'id' => "$name-spinner",
                        'class' => ['spinner', 'hidden'],
                    ]).
                    Html::endTag('div');

                return self::fieldHtml($input, [
                    'fieldClass' => ! $visible ? 'hidden' : null,
                    'label' => $address->getAttributeLabel($name),
                    'id' => $name,
                    'required' => $required,
                    'errors' => $errors,
                    'data' => [
                        'error-key' => $name,
                    ],
                    'disabled' => $static,
                ]);
            }

            return self::selectizeFieldHtml([
                'fieldClass' => ! $visible ? 'hidden' : null,
                'status' => $address->getAttributeStatus($name),
                'label' => $address->getAttributeLabel($name),
                'id' => $name,
                'name' => $name,
                'value' => $value,
                'options' => $options,
                'required' => $required,
                'errors' => $address->errors()->get($name),
                'autocomplete' => $autocomplete,
                'data' => [
                    'error-key' => $name,
                ],
                'disabled' => $static,
            ]);
        }

        // No preconfigured subdivisions for the given parents, so just output a text input
        return self::textFieldHtml([
            'fieldClass' => ! $visible ? 'hidden' : null,
            'status' => $address->getAttributeStatus($name),
            'label' => $address->getAttributeLabel($name),
            'autocomplete' => $autocomplete,
            'id' => $name,
            'name' => $name,
            'value' => $value,
            'required' => $required,
            'errors' => ! $static ? $address->errors()->get($name) : [],
            'data' => [
                'error-key' => $name,
            ],
            'disabled' => $static,
        ]);
    }

    private static function renderTemplate(string $template, array $variables = []): string
    {
        return template(''.$template, $variables, templateMode: TemplateMode::Cp);
    }
}
