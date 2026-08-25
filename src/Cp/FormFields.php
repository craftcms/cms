<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp;

use CraftCms\Cms\Address\Addresses;
use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Cp\Components\Button;
use CraftCms\Cms\Cp\Components\ButtonGroup;
use CraftCms\Cms\Cp\Components\Checkbox;
use CraftCms\Cms\Cp\Components\CheckboxGroup;
use CraftCms\Cms\Cp\Components\CheckboxSelect;
use CraftCms\Cms\Cp\Components\Field;
use CraftCms\Cms\Cp\Components\FieldGroup;
use CraftCms\Cms\Cp\Components\Input;
use CraftCms\Cms\Cp\Components\InputColor;
use CraftCms\Cms\Cp\Components\InputCopy;
use CraftCms\Cms\Cp\Components\InputDate;
use CraftCms\Cms\Cp\Components\InputDateTime;
use CraftCms\Cms\Cp\Components\InputPassword;
use CraftCms\Cms\Cp\Components\InputTime;
use CraftCms\Cms\Cp\Components\Lightswitch;
use CraftCms\Cms\Cp\Components\Radio;
use CraftCms\Cms\Cp\Components\RadioGroup;
use CraftCms\Cms\Cp\Components\Textarea;
use CraftCms\Cms\Cp\Enums\Size;
use CraftCms\Cms\Cp\Html\MenuHtml;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\DateTimeHelper;
use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\View\TemplateMode;
use DateTimeInterface;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\HtmlString;
use Illuminate\Support\ViewErrorBag;
use InvalidArgumentException;
use Stringable;

use function CraftCms\Cms\currentUser;
use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

/** @phpstan-import-type AddressFormField from Addresses */
readonly class FormFields
{
    /**
     * Renders a `<craft-field>` for the given input, via the {@see Field} UI
     * component. The field chrome (heading, notices, error list) lives in the
     * web component; aria wiring between the label, descriptors and the
     * slotted control happens client-side, so `labelledBy`/`describedBy` are
     * only passed to input templates when explicitly configured.
     */
    /** @param array<string, mixed> $config */
    public static function fieldHtml(string|Stringable|callable $input, array $config = []): string
    {
        return self::fieldFromConfig($input, $config)->toHtml();
    }

    /**
     * Maps the legacy field config surface onto the {@see Field} component —
     * the PHP twin of the `_includes/forms/field` glue template.
     */
    /** @param array<string, mixed> $config */
    private static function fieldFromConfig(string|Stringable|callable $input, array $config): Field
    {
        $attribute = $config['attribute'] ?? $config['id'] ?? null;
        $id = $config['id'] ??= 'field'.mt_rand();

        $errors = $config['errors'] ?? null;
        if ($errors instanceof ViewErrorBag) {
            $errors = $errors->get($attribute);
        }

        $status = $config['status'] ?? null;
        $label = $config['fieldLabel'] ?? $config['label'] ?? null;

        if ($label === '__blank__') {
            $label = null;
        }

        $siteId = Sites::isMultiSite() && isset($config['siteId']) ? (int) $config['siteId'] : null;

        if (! is_callable($input)) {
            $input = (string) $input;
        }

        if (is_callable($input)) {
            $input = $input($config);
        } elseif (str_starts_with($input, 'template:')) {
            $input = self::renderTemplate(substr($input, 9), $config);
        }

        if ($siteId) {
            $site = Sites::getSiteById($siteId);
            if (! $site) {
                throw new InvalidArgumentException("Invalid site ID: $siteId");
            }
        } else {
            $site = null;
        }

        $translatable = Sites::isMultiSite() ? ($config['translatable'] ?? ($site !== null)) : false;

        $showAttribute = (
            ($config['showAttribute'] ?? false) &&
            currentUser()->isAdmin() &&
            currentUser()->getPreference('showFieldHandles')
        );
        $showActionMenu = (
            ! empty($config['actionMenuItems']) &&
            ($label || $showAttribute || isset($config['actions']) || isset($config['labelExtra']))
        );

        self::deprecateConfig('field', $config, [
            'labelExtra' => 'has been deprecated. `actions` should be used instead.',
        ]);

        $actions = implode('', array_filter([
            $showActionMenu
                ? app(MenuHtml::class)->disclosureMenu($config['actionMenuItems'], [
                    'hiddenLabel' => t('Actions'),
                    'buttonAttributes' => [
                        'class' => ['action-btn', 'small', 'prevent-autofocus'],
                    ],
                ])
                : null,
            $showAttribute
                ? self::renderTemplate('_includes/forms/copytextbtn', [
                    'id' => "$id-attribute",
                    'class' => ['code', 'small', 'light'],
                    'value' => $config['attribute'],
                ])
                : null,
            isset($config['actions']) ? (string) $config['actions'] : null,
        ]));

        $errors = $errors !== null && ! is_iterable($errors) ? [$errors] : $errors;

        $field = Field::make()
            ->id($config['fieldId'] ?? "$id-field")
            ->label($label !== null ? (string) $label : null)
            ->required((bool) ($config['required'] ?? false))
            ->translatable($translatable, $config['translationDescription'] ?? null)
            ->fieldset((bool) ($config['fieldset'] ?? false))
            ->readOnly((bool) ($config['static'] ?? false))
            ->disabled((bool) ($config['disabled'] ?? false))
            ->orientation($config['orientation'] ?? ($site ? $site->getLocale() : I18N::getLocale())->getOrientation())
            ->status($status ? Str::toString($status[0]) : null, $status[1] ?? null)
            ->instructions($config['instructions'] ?? null)
            ->instructionsPosition($config['instructionsPosition'] ?? 'before')
            ->tip($config['tip'] ?? null)
            ->warning($config['warning'] ?? null)
            ->errors($errors !== null ? collect($errors)->map(fn ($error): string => (string) $error)->all() : [])
            ->headingPrefix($config['headingPrefix'] ?? null)
            ->headingSuffix($config['headingSuffix'] ?? null)
            ->actions($actions !== '' ? $actions : null)
            ->input($input)
            ->width($config['width'] ?? null)
            ->attributes(Arr::merge(
                [
                    'class' => array_merge(array_filter([
                        'field',
                        ($config['first'] ?? false) ? 'first' : null,
                    ]), Html::explodeClass($config['fieldClass'] ?? [])),
                    'data' => [
                        'attribute' => $attribute,
                    ] + ($config['data'] ?? []),
                ],
                // Transitional: the input container lives in the web
                // component's shadow DOM now, so per-instance container
                // attributes land on the host until callers migrate.
                Arr::merge(
                    $config['inputContainerAttributes'] ?? [],
                    $config['fieldAttributes'] ?? [],
                ),
            ));

        if (isset($config['labelExtra'])) {
            $field->labelExtra((string) $config['labelExtra']);
        }

        return $field;
    }

    /**
     * Logs deprecations for legacy config keys that the new UI components no
     * longer support (or support with changed behavior). Keys that map
     * faithfully onto the components don't warn.
     *
     * @param  array<string, mixed>  $config
     * @param  array<string, string>  $messages  Key => what to use instead
     */
    private static function deprecateConfig(string $component, array $config, array $messages): void
    {
        foreach ($messages as $key => $message) {
            if (array_key_exists($key, $config)) {
                Deprecator::log(
                    "$component-config-$key",
                    "The `$key` $component config option $message",
                );
            }
        }
    }

    /**
     * Maps the legacy button config surface onto the {@see Button} component,
     * the translation layer between the legacy Twig config array and the
     * component. `spinner` is absorbed (the web component renders its own
     * spinner when loading); the busy/failure/retry/success messages pass
     * through as data attributes for the legacy submit JS.
     */
    /** @param array<string, mixed> $config */
    public static function buttonFromConfig(array $config): Button
    {
        self::deprecateConfig('button', $config, [
            'spinner' => 'has been deprecated. `<craft-button>` renders its own spinner while loading.',
        ]);

        $label = $config['label'] ?? null;
        $labelHtml = $config['labelHtml'] ?? null;
        $readOnly = (bool) ($config['readOnly'] ?? false);
        $size = Size::tryFrom($config['size'] ?? '');
        $classArray = Html::explodeClass($config['class'] ?? []);

        if (! $size && in_array('small', $classArray)) {
            $size = Size::Small;
            // Remove the small class
            Arr::forget($classArray, 'small');
            self::deprecateConfig('button', $config, [
                'class.small' => 'has been deprecated. Use size="small" instead.',
            ]);
        }

        $attributes = $config['attributes'] ?? [];

        return Button::make()
            ->id($config['id'] ?? null)
            ->type($config['type'] ?? 'button')
            ->label($labelHtml !== null ? new HtmlString((string) $labelHtml) : ($label !== null ? (string) $label : null))
            ->icon($config['icon'] ?? null)
            ->prefix(! isset($config['icon']) ? ($config['iconHtml'] ?? null) : null)
            ->disabled((bool) ($config['disabled'] ?? $readOnly))
            ->size($size)
            ->variant($config['variant'] ?? $config['appearance'] ?? null)
            ->command($config['command'] ?? null)
            ->attributes(Arr::merge(
                [
                    'class' => array_merge(
                        Html::explodeClass($config['class'] ?? []),
                        array_filter([$readOnly ? 'read-only' : null]),
                    ),
                    'data' => array_filter([
                        'busy-message' => $config['busyMessage'] ?? null,
                        'failure-message' => $config['failureMessage'] ?? null,
                        'retry-message' => $config['retryMessage'] ?? null,
                        'success-message' => $config['successMessage'] ?? null,
                    ]),
                ],
                $attributes
            ));
    }

    /** @param array<string, mixed> $config */
    public static function buttonGroupFieldHtml(array $config): string
    {
        $config['id'] ??= 'buttongroup'.mt_rand();
        $config['fieldset'] = true;

        return self::fieldHtml(
            fn (array $c): string => self::buttonGroupFromConfig($c)->toHtml(),
            $config,
        );
    }

    /**
     * Maps the legacy buttonGroup config surface onto the {@see ButtonGroup}
     * component, applying the group-level appearance/size defaults and the
     * selected state to each option's button.
     */
    /** @param array<string, mixed> $config */
    public static function buttonGroupFromConfig(array $config): ButtonGroup
    {
        $value = $config['value'] ?? null;
        $variant = $config['variant'] ?? $config['appearance'] ?? 'outline';
        $size = $config['size'] ?? null;
        $disabled = ($config['disabled'] ?? false) || ($config['static'] ?? false);

        $buttons = [];

        foreach (($config['options'] ?? []) as $key => $option) {
            if (! is_array($option)) {
                $option = ['label' => $option, 'value' => $key];
            }

            $optionValue = $option['value'] ?? null;
            $selected = $optionValue !== null && $optionValue == $value;

            $buttons[] = Button::make()
                ->label(isset($option['labelHtml'])
                    ? new HtmlString((string) $option['labelHtml'])
                    : ($option['label'] ?? null))
                ->icon($option['icon'] ?? null)
                ->variant($option['variant'] ?? $option['appearance'] ?? $variant)
                ->size($option['size'] ?? $size)
                ->active($selected)
                ->disabled($disabled)
                ->attributes(Arr::merge(
                    [
                        'class' => Html::explodeClass($option['class'] ?? []),
                        'value' => $optionValue,
                        'data' => ['value' => $optionValue],
                        'aria' => ['pressed' => $selected ? 'true' : 'false'],
                    ],
                    $option['attributes'] ?? [],
                ));
        }

        return ButtonGroup::make()
            ->id($config['id'] ?? 'button-group-'.mt_rand())
            ->labelledBy($config['labelledBy'] ?? null)
            ->name($config['name'] ?? null)
            ->value($value !== null ? (string) $value : null)
            ->disabled($disabled)
            ->buttons($buttons)
            ->attributes(Arr::merge(
                ['class' => Html::explodeClass($config['class'] ?? [])],
                $config['containerAttributes'] ?? [],
            ));
    }

    /** @param array<string, mixed> $config */
    public static function checkboxFieldHtml(array $config): string
    {
        $config['id'] ??= 'checkbox'.mt_rand();

        $config['fieldClass'] = Html::explodeClass($config['fieldClass'] ?? []);
        $config['fieldClass'][] = 'checkboxfield';
        $config['instructionsPosition'] ??= 'after';

        // Don't pass along `label` since it's ambiguous
        unset($config['label']);

        return self::fieldHtml(
            fn (array $c): string => self::checkboxFromConfig($c)->toHtml(),
            $config,
        );
    }

    /**
     * Maps the legacy checkbox config surface onto the {@see Checkbox}
     * component. Legacy semantics preserved: `checkboxLabel` wins over
     * `label`, aria-labelledby is suppressed when an `aria-label` is
     * configured, and custom-option mode renders a text input for the value.
     */
    /** @param array<string, mixed> $config */
    public static function checkboxFromConfig(array $config): Checkbox
    {
        $id = $config['id'] ?? 'checkbox'.mt_rand();
        $labelId = $config['labelId'] ?? "$id-label";
        $aria = Arr::merge($config['inputAttributes']['aria'] ?? [], $config['aria'] ?? []);
        $custom = (bool) ($config['custom'] ?? false);

        return Checkbox::make()
            ->id($id)
            ->labelId($labelId)
            ->name($config['name'] ?? null)
            ->value($config['value'] ?? 1)
            ->checked((bool) ($config['checked'] ?? false))
            ->autofocus((bool) ($config['autofocus'] ?? false))
            ->disabled((bool) ($config['disabled'] ?? false))
            ->label($config['checkboxLabel'] ?? $config['label'] ?? null)
            ->info($config['info'] ?? null)
            ->icon($config['icon'] ?? null)
            ->color($config['color'] ?? null)
            ->custom($custom
                ? self::textHtml([
                    'value' => $config['value'] ?? null,
                    'class' => 'small custom-option-input',
                    'labelledBy' => $labelId,
                ])
                : null)
            ->toggle($config['toggle'] ?? null)
            ->reverseToggle($config['reverseToggle'] ?? null)
            ->targetPrefix($config['targetPrefix'] ?? null)
            ->labelledBy(empty($aria['label']) ? ($config['labelledBy'] ?? null) : null)
            ->describedBy($config['describedBy'] ?? $aria['describedby'] ?? null)
            ->inputAttributes(Arr::merge(
                [
                    'class' => Html::explodeClass($config['class'] ?? []),
                    'aria' => $aria,
                    'data' => $config['data'] ?? [],
                ],
                $config['inputAttributes'] ?? [],
            ));
    }

    /** @param array<string, mixed> $config */
    public static function checkboxSelectFieldHtml(array $config): string
    {
        $config['id'] ??= 'checkboxselect'.mt_rand();
        $config['fieldset'] = true;

        return self::fieldHtml(
            fn (array $c): string => self::checkboxSelectFromConfig($c)->toHtml(),
            $config,
        );
    }

    /**
     * Maps the legacy checkboxSelect config surface onto the
     * {@see CheckboxSelect} component — the PHP twin of the
     * `_includes/forms/checkboxSelect` glue template. Legacy semantics
     * preserved: sortable pre-orders options by the `values` order, and a
     * checked "All" option checks and disables every item.
     */
    /** @param array<string, mixed> $config */
    public static function checkboxSelectFromConfig(array $config): CheckboxSelect
    {
        $id = $config['id'] ?? 'checkbox-select-'.mt_rand();
        $name = $config['name'] ?? null;
        $values = $config['values'] ?? [];
        $disabled = (bool) ($config['disabled'] ?? false);
        $sortable = (bool) ($config['sortable'] ?? false);

        $rawOptions = $config['options'] ?? [];
        $rawOptions = is_iterable($rawOptions) ? $rawOptions : [$rawOptions];

        $options = collect($rawOptions)
            ->map(fn ($option, $key) => is_array($option) ? $option : [
                'label' => $option,
                'value' => $key,
            ])
            ->values();

        if ($sortable && is_array($values) && $values !== []) {
            $options = $options
                ->sortBy(function (array $option) use ($values): int {
                    $index = array_search($option['value'] ?? null, $values);

                    return $index === false ? PHP_INT_MAX : $index;
                })
                ->values();
        }

        $showAllOption = (bool) ($config['showAllOption'] ?? false);
        $allChecked = false;
        $allCheckbox = null;

        if ($showAllOption) {
            $allLabel = $config['allLabel'] ?? t('All');
            $allValue = $config['allValue'] ?? '*';
            $allChecked = $values == $allValue;

            $allCheckbox = self::checkboxFromConfig([
                'describedBy' => $config['describedBy'] ?? null,
                'class' => ['cp-checkbox-select__item', 'all'],
                'label' => new HtmlString("<b>$allLabel</b>"),
                'name' => $name,
                'value' => $allValue,
                'checked' => $allChecked,
                'autofocus' => $config['autofocus'] ?? false,
                'targetPrefix' => $config['targetPrefix'] ?? null,
                'disabled' => $disabled,
            ]);
        }

        $checkboxes = $options
            ->map(fn (array $option): Checkbox => self::checkboxFromConfig(array_merge([
                'name' => $name !== null ? "{$name}[]" : null,
                'checked' => ($showAllOption && $allChecked)
                    || (isset($option['value']) && is_array($values) && in_array($option['value'], $values)),
                'disabled' => ($showAllOption && $allChecked) || $disabled,
                'targetPrefix' => $config['targetPrefix'] ?? null,
            ], $option)))
            ->all();

        return CheckboxSelect::make()
            ->id($id)
            ->name($showAllOption ? null : $name)
            ->allCheckbox($allCheckbox)
            ->options($checkboxes)
            ->sortable($sortable)
            ->storageKey($config['storageKey'] ?? null)
            ->disabled($disabled)
            ->attributes(Arr::merge(
                ['class' => Html::explodeClass($config['class'] ?? [])],
                $config['containerAttributes'] ?? [],
            ));
    }

    /** @param array<string, mixed> $config */
    public static function radioGroupFieldHtml(array $config): string
    {
        $config['id'] ??= 'radiogroup'.mt_rand();
        $config['fieldset'] = true;

        return self::fieldHtml(
            fn (array $c): string => self::radioGroupFromConfig($c)->toHtml(),
            $config,
        );
    }

    /**
     * Maps the legacy radio config surface onto the {@see Radio} component —
     * the PHP twin of the `_includes/forms/radio` glue template. Legacy
     * semantics preserved: `radioLabel` wins over `label`, and custom-option
     * mode renders an "Other:" text input that syncs its value to the radio.
     */
    /** @param array<string, mixed> $config */
    public static function radioFromConfig(array $config): Radio
    {
        $id = $config['id'] ?? 'radio'.mt_rand();
        $labelId = $config['labelId'] ?? "$id-label";
        $custom = (bool) ($config['custom'] ?? false);

        if ($custom) {
            HtmlStack::jsWithVars(fn ($radio, $text) => <<<JS
                (() => {
                  const \$radio = $($radio);
                  const \$text = $($text);
                  \$text.on('input', () => {
                    \$radio.val(\$text.val());
                  });
                })();
                JS, [
                'radio' => '#'.InputNamespace::namespaceId($id),
                'text' => '#'.InputNamespace::namespaceId("$id-text"),
            ]);
        }

        return Radio::make()
            ->id($id)
            ->labelId($labelId)
            ->name($config['name'] ?? null)
            ->value($config['value'] ?? 1)
            ->checked((bool) ($config['checked'] ?? false))
            ->autofocus((bool) ($config['autofocus'] ?? false))
            ->disabled((bool) ($config['disabled'] ?? false))
            ->label($config['radioLabel'] ?? $config['label'] ?? null)
            ->icon($config['icon'] ?? null)
            ->color($config['color'] ?? null)
            ->custom($custom
                ? self::textHtml([
                    'id' => "$id-text",
                    'value' => $config['value'] ?? null,
                    'class' => 'small custom-option-input',
                    'labelledBy' => $labelId,
                ])
                : null)
            ->describedBy($config['describedBy'] ?? null)
            ->inputAttributes(Arr::merge(
                ['class' => Html::explodeClass($config['class'] ?? [])],
                $config['inputAttributes'] ?? [],
            ))
            ->attributes($config['containerAttributes'] ?? []);
    }

    /**
     * Maps the legacy radioGroup config surface onto the {@see RadioGroup}
     * component — the PHP twin of the `_includes/forms/radioGroup` glue
     * template.
     */
    /** @param array<string, mixed> $config */
    public static function radioGroupFromConfig(array $config): RadioGroup
    {
        $id = $config['id'] ?? 'radio-group-'.mt_rand();
        $value = $config['value'] ?? null;
        $disabled = (bool) ($config['disabled'] ?? false);

        $radios = [];
        $first = true;

        foreach ($config['options'] ?? [] as $key => $option) {
            if (! is_array($option)) {
                $option = ['label' => $option, 'value' => $key];
            }

            $radios[] = self::radioFromConfig(array_merge([
                'describedBy' => $config['describedBy'] ?? null,
                'name' => $config['name'] ?? null,
                'checked' => isset($option['value'])
                    && $option['value'] == $value
                    && ($option['value'] || ! ($option['custom'] ?? false)),
                'autofocus' => $first && ($config['autofocus'] ?? false),
                'disabled' => $disabled,
            ], $option));

            $first = false;
        }

        return RadioGroup::make()
            ->id($id)
            ->options($radios)
            ->toggle((bool) ($config['toggle'] ?? false))
            ->targetPrefix($config['targetPrefix'] ?? null)
            ->attributes(Arr::merge(
                ['class' => Html::explodeClass($config['class'] ?? [])],
                $config['containerAttributes'] ?? [],
            ));
    }

    /** @param array<string, mixed> $config */
    public static function checkboxGroupFieldHtml(array $config): string
    {
        $config['id'] ??= 'checkboxgroup'.mt_rand();

        return self::fieldHtml(
            fn (array $c): string => self::checkboxGroupFromConfig($c)->toHtml(),
            $config,
        );
    }

    /**
     * Maps the legacy checkboxGroup config surface onto the
     * {@see CheckboxGroup} component — the PHP twin of the
     * `_includes/forms/checkboxGroup` glue template.
     */
    /** @param array<string, mixed> $config */
    public static function checkboxGroupFromConfig(array $config): CheckboxGroup
    {
        $id = $config['id'] ?? 'checkbox-group-'.mt_rand();
        $name = $config['name'] ?? null;
        $optionName = $name !== null ? "{$name}[]" : null;
        $values = $config['values'] ?? [];

        $checkboxes = [];
        $first = true;

        foreach ($config['options'] ?? [] as $key => $option) {
            if (! is_array($option)) {
                $option = ['label' => $option, 'value' => $key];
            }

            $checkboxes[] = self::checkboxFromConfig(array_merge([
                'describedBy' => $config['describedBy'] ?? null,
                'name' => $optionName,
                'checked' => isset($option['value']) && is_array($values) && in_array($option['value'], $values),
                'autofocus' => $first && ($config['autofocus'] ?? false),
            ], $option));

            $first = false;
        }

        $customOptionTemplate = null;

        if (($config['allowCustomOptions'] ?? false) && $optionName !== null) {
            $customOptionTemplate = InputNamespace::namespaceInputs(Html::tag(
                'div',
                self::checkboxFromConfig([
                    'id' => '__ID__',
                    'label' => null,
                    'value' => '',
                    'describedBy' => $config['describedBy'] ?? null,
                    'name' => $optionName,
                    'checked' => true,
                    'custom' => true,
                ])->toHtml(),
                ['data' => ['custom' => true]],
            ), InputNamespace::get());
        }

        return CheckboxGroup::make()
            ->id($id)
            ->name($name)
            ->options($checkboxes)
            ->customOptionTemplate($customOptionTemplate)
            ->attributes($config['containerAttributes'] ?? []);
    }

    /** @param array<string, mixed> $config */
    public static function colorHtml(array $config): string
    {
        return self::colorFromConfig($config)->toHtml();
    }

    /**
     * Maps the legacy color config surface onto the {@see InputColor} component
     * — the PHP twin of the `_includes/forms/color` glue template. The value is
     * stored as bare hex (the component renders its own leading `#`, swatch, and
     * native picker, replacing the legacy Craft.ColorInput markup + JS), and
     * `presets` pass through to the picker datalist. The legacy `.color-input`
     * input class is preserved for any CSS/JS still keyed on it.
     *
     * @param  array<string, mixed>  $config
     */
    public static function colorFromConfig(array $config): InputColor
    {
        $value = $config['value'] ?? null;
        $classes = Html::explodeClass($config['class'] ?? []);
        $classes[] = 'color-input';

        $input = InputColor::make();
        self::textFromConfig(array_merge($config, [
            'value' => $value !== null ? ltrim((string) $value, '#') : null,
            'size' => $config['size'] ?? 10,
            'class' => $classes,
            'autocorrect' => false,
            'autocapitalize' => false,
        ]), $input);

        return $input->presets($config['presets'] ?? []);
    }

    /** @param array<string, mixed> $config */
    public static function colorFieldHtml(array $config): string
    {
        $config['id'] ??= 'color'.mt_rand();
        $config['fieldset'] = true;

        return self::fieldHtml(
            fn (array $c): string => self::colorFromConfig($c)->toHtml(),
            $config,
        );
    }

    /** @param array<string, mixed> $config */
    public static function colorSelectFieldHtml(array $config): string
    {
        $config['id'] ??= 'colorselect'.mt_rand();

        return self::fieldHtml('template:_includes/forms/colorSelect', $config);
    }

    /** @param array<string, mixed> $config */
    public static function iconPickerHtml(array $config): string
    {
        return self::renderTemplate('_includes/forms/iconPicker', $config);
    }

    /** @param array<string, mixed> $config */
    public static function iconPickerFieldHtml(array $config): string
    {
        $config['id'] ??= 'iconpicker'.mt_rand();
        // The <craft-icon-picker> control has no single labelable input (preview +
        // buttons), so label the group with a fieldset legend rather than a
        // `for=` label — and keep the component itself label-less.
        $config['fieldset'] = true;

        return self::fieldHtml('template:_includes/forms/iconPicker', $config);
    }

    /** @param array<string, mixed> $config */
    public static function editableTableHtml(array $config): string
    {
        return self::renderTemplate('_includes/forms/editableTable', $config);
    }

    /** @param array<string, mixed> $config */
    public static function editableTableFieldHtml(array $config): string
    {
        $config['id'] ??= 'editabletable'.mt_rand();
        $config['width'] ??= 'full';

        return self::fieldHtml('template:_includes/forms/editableTable', $config);
    }

    /** @param array<string, mixed> $config */
    public static function lightswitchFieldHtml(array $config): string
    {
        $config['id'] ??= 'lightswitch'.mt_rand();

        $config['fieldClass'] = Html::explodeClass($config['fieldClass'] ?? []);
        $config['fieldClass'][] = 'lightswitch-field';

        // Don't pass along `label` since it's ambiguous
        $config['fieldLabel'] ??= $config['label'] ?? null;
        unset($config['label']);

        return self::fieldHtml(
            fn (array $c): string => self::lightswitchFromConfig($c)->toHtml(),
            $config,
        );
    }

    /**
     * Maps the legacy lightswitch config surface onto the {@see Lightswitch}
     * component — the PHP twin of the `_includes/forms/lightswitch` glue
     * template. Legacy semantics preserved: `label` is an `onLabel` fallback,
     * not a field label.
     */
    /** @param array<string, mixed> $config */
    public static function lightswitchFromConfig(array $config): Lightswitch
    {
        self::deprecateConfig('lightswitch', $config, [
            'descriptionId' => 'is no longer supported. `<craft-switch>` renders and links its own state description.',
        ]);

        return Lightswitch::make()
            ->id($config['id'] ?? 'lightswitch'.mt_rand())
            ->on((bool) ($config['on'] ?? false))
            ->indeterminate((bool) ($config['indeterminate'] ?? false))
            ->value((string) ($config['value'] ?? '1'))
            ->indeterminateValue((string) ($config['indeterminateValue'] ?? '-'))
            ->size(($config['small'] ?? false) ? Size::Small : null)
            ->disabled((bool) ($config['disabled'] ?? false))
            ->onLabel($config['onLabel'] ?? $config['label'] ?? null)
            ->offLabel($config['offLabel'] ?? null)
            ->toggle($config['toggle'] ?? null)
            ->reverseToggle($config['reverseToggle'] ?? null)
            ->labelledBy($config['labelledBy'] ?? $config['labelId'] ?? null)
            ->describedBy($config['describedBy'] ?? null)
            ->name($config['name'] ?? null)
            ->attributes($config['containerAttributes'] ?? []);
    }

    /** @param array<string, mixed> $config */
    public static function rangeHtml(array $config): string
    {
        return self::renderTemplate('_includes/forms/range', $config);
    }

    /** @param array<string, mixed> $config */
    public static function rangeFieldHtml(array $config): string
    {
        $config['id'] ??= 'range'.mt_rand();

        return self::fieldHtml('template:_includes/forms/range', $config);
    }

    /** @param array<string, mixed> $config */
    public static function moneyInputHtml(array $config): string
    {
        return self::renderTemplate('_includes/forms/money', $config);
    }

    /** @param array<string, mixed> $config */
    public static function moneyFieldHtml(array $config): string
    {
        $config['id'] ??= 'money'.mt_rand();

        return self::fieldHtml('template:_includes/forms/money', $config);
    }

    /** @param array<string, mixed> $config */
    public static function selectHtml(array $config): string
    {
        return self::renderTemplate('_includes/forms/select', $config);
    }

    /** @param array<string, mixed> $config */
    public static function selectFieldHtml(array $config): string
    {
        $config['id'] ??= 'select'.mt_rand();

        return self::fieldHtml('template:_includes/forms/select', $config);
    }

    /** @param array<string, mixed> $config */
    public static function customSelectHtml(array $config): string
    {
        return self::renderTemplate('_includes/forms/customSelect', $config);
    }

    /** @param array<string, mixed> $config */
    public static function customSelectFieldHtml(array $config): string
    {
        $config['id'] ??= 'customselect'.mt_rand();

        return self::fieldHtml('template:_includes/forms/customSelect', $config);
    }

    /** @param array<string, mixed> $config */
    public static function selectizeHtml(array $config): string
    {
        return self::renderTemplate('_includes/forms/selectize', $config);
    }

    /** @param array<string, mixed> $config */
    public static function selectizeFieldHtml(array $config): string
    {
        $config['id'] ??= 'selectize'.mt_rand();

        return self::fieldHtml('template:_includes/forms/selectize', $config);
    }

    /** @param array<string, mixed> $config */
    public static function multiSelectHtml(array $config): string
    {
        return self::renderTemplate('_includes/forms/multiselect', $config);
    }

    /** @param array<string, mixed> $config */
    public static function multiSelectFieldHtml(array $config): string
    {
        $config['id'] ??= 'multiselect'.mt_rand();

        return self::fieldHtml('template:_includes/forms/multiselect', $config);
    }

    /** @param array<string, mixed> $config */
    public static function textHtml(array $config): string
    {
        return self::textFromConfig($config)->toHtml();
    }

    /**
     * Maps the legacy text config surface onto the {@see Input} component —
     * the PHP twin of the `_includes/forms/text` glue template. Legacy
     * semantics preserved: `unit` is a `suffix` fallback, aria-labelledby is
     * suppressed when an `aria-label` is configured, and a legacy `size`
     * (character width) shrinks the control instead of spanning the column.
     * A `maxlength` alone keeps the legacy full-width behavior unless a
     * `width` is configured, since the web component would otherwise shrink.
     */
    /** @param array<string, mixed> $config */
    public static function textFromConfig(array $config, ?Input $input = null): Input
    {
        $inputAttributes = $config['inputAttributes'] ?? [];
        $size = ($config['size'] ?? false) ?: null;
        $maxlength = ($config['maxlength'] ?? false) ?: null;
        $value = $config['value'] ?? null;

        return ($input ?? Input::make())
            ->id($config['id'] ?? 'text'.mt_rand())
            ->type($config['type'] ?? 'text')
            ->name($config['name'] ?? null)
            ->value($value !== false ? $value : null)
            ->inputSize($size !== null ? (int) $size : null)
            ->maxlength($maxlength !== null ? (int) $maxlength : null)
            ->width($size !== null ? 'auto' : ($maxlength !== null ? 'full' : null))
            ->autofocus((bool) ($config['autofocus'] ?? false))
            ->autocomplete($config['autocomplete'] ?? false)
            ->autocorrect((bool) ($config['autocorrect'] ?? true))
            ->autocapitalize((bool) ($config['autocapitalize'] ?? true))
            ->disabled((bool) ($config['disabled'] ?? false))
            ->readOnly((bool) ($config['readonly'] ?? false))
            ->title($config['title'] ?? null)
            ->placeholder($config['placeholder'] ?? null)
            ->step(($config['step'] ?? false) ?: null)
            ->min(($config['min'] ?? false) ?: null)
            ->max(($config['max'] ?? false) ?: null)
            ->inputmode(($config['inputmode'] ?? false) ?: null)
            ->orientation($config['orientation'] ?? null)
            ->role(($config['role'] ?? false) ?: null)
            ->expanded($config['expanded'] ?? null)
            ->suffix(($config['suffix'] ?? $config['unit'] ?? false) ?: null)
            ->descriptionId($config['descriptionId'] ?? null)
            ->showCharsLeft((bool) ($config['showCharsLeft'] ?? false))
            ->textExpanderTriggers($config['textExpanderTriggers'] ?? [])
            ->labelledBy(empty($inputAttributes['aria']['label']) ? ($config['labelledBy'] ?? null) : null)
            ->describedBy(($config['describedBy'] ?? false) ?: null)
            ->inputAttributes(Arr::merge(
                ['class' => Html::explodeClass($config['class'] ?? [])],
                $inputAttributes,
            ));
    }

    /** @param array<string, mixed> $config */
    public static function textFieldHtml(array $config): string
    {
        $config['id'] ??= 'text'.mt_rand();

        return self::fieldHtml(
            fn (array $c): string => self::textFromConfig($c)->toHtml(),
            $config,
        );
    }

    /** @param array<string, mixed> $config */
    public static function copytextHtml(array $config): string
    {
        return self::copytextFromConfig($config)->toHtml();
    }

    /**
     * Maps the legacy copytext config surface onto the {@see InputCopy}
     * component — the PHP twin of the `_includes/forms/copytext` glue
     * template. The `class` key targets the native input (matching the legacy
     * text input convention). Pass `copy-value` (or `copyValue`) when the
     * clipboard value should differ from the displayed one.
     */
    /** @param array<string, mixed> $config */
    public static function copytextFromConfig(array $config): InputCopy
    {
        $value = $config['value'] ?? null;
        $copyValue = $config['copyValue'] ?? $config['copy-value'] ?? false;

        return InputCopy::make()
            ->id($config['id'] ?? 'copytext'.mt_rand())
            ->name($config['name'] ?? null)
            ->value($value !== false ? $value : null)
            ->copyValue($copyValue !== false ? $copyValue : null)
            ->monospace((bool) ($config['monospace'] ?? false))
            ->disabled((bool) ($config['disabled'] ?? false))
            ->labelledBy(empty($config['inputAttributes']['aria']['label'] ?? null) ? ($config['labelledBy'] ?? null) : null)
            ->describedBy(($config['describedBy'] ?? false) ?: null)
            ->inputAttributes(Arr::merge(
                ['class' => Html::explodeClass($config['class'] ?? [])],
                $config['inputAttributes'] ?? [],
            ));
    }

    /** @param array<string, mixed> $config */
    public static function copytextFieldHtml(array $config): string
    {
        $config['id'] ??= 'copytext'.mt_rand();

        return self::fieldHtml(
            fn (array $c): string => self::copytextFromConfig($c)->toHtml(),
            $config,
        );
    }

    /** @param array<string, mixed> $config */
    public static function passwordHtml(array $config): string
    {
        return self::passwordFromConfig($config)->toHtml();
    }

    /**
     * Maps the legacy password config surface onto the {@see InputPassword}
     * component — the PHP twin of the `_includes/forms/password` glue template.
     * A password input is a text input with a fixed type plus the component's
     * built-in reveal toggle (which replaces the legacy Craft.PasswordInput JS),
     * so this reuses the text mapping and swaps the component. The legacy
     * `.password` input class is preserved for any CSS/JS still keyed on it.
     *
     * @param  array<string, mixed>  $config
     */
    public static function passwordFromConfig(array $config): InputPassword
    {
        $config['type'] = 'password';
        $classes = Html::explodeClass($config['class'] ?? []);
        $classes[] = 'password';
        $config['class'] = $classes;

        $input = InputPassword::make();
        self::textFromConfig($config, $input);

        return $input;
    }

    /** @param array<string, mixed> $config */
    public static function passwordFieldHtml(array $config): string
    {
        $config['id'] ??= 'password'.mt_rand();

        return self::fieldHtml(
            fn (array $c): string => self::passwordFromConfig($c)->toHtml(),
            $config,
        );
    }

    /** @param array<string, mixed> $config */
    public static function textareaHtml(array $config): string
    {
        return self::textareaFromConfig($config)->toHtml();
    }

    /**
     * Maps the legacy textarea config surface onto the {@see Textarea}
     * component — the PHP twin of the `_includes/forms/textarea` glue
     * template. Legacy semantics preserved: unlike {@see textFromConfig()},
     * autofocus isn't gated on the current user's autofocus preference.
     */
    /** @param array<string, mixed> $config */
    public static function textareaFromConfig(array $config): Textarea
    {
        $cols = ($config['cols'] ?? false) ?: null;
        $maxlength = ($config['maxlength'] ?? false) ?: null;

        return Textarea::make()
            ->id($config['id'] ?? 'textarea'.mt_rand())
            ->name($config['name'] ?? null)
            ->value($config['value'] ?? null)
            ->maxlength($maxlength !== null ? (int) $maxlength : null)
            ->rows((int) ($config['rows'] ?? 2))
            ->cols($cols !== null ? (int) $cols : null)
            ->inputmode(($config['inputmode'] ?? false) ?: null)
            ->autofocus((bool) ($config['autofocus'] ?? false))
            ->disabled((bool) ($config['disabled'] ?? false))
            ->readOnly((bool) ($config['readonly'] ?? false))
            ->title($config['title'] ?? null)
            ->placeholder($config['placeholder'] ?? null)
            ->showCharsLeft((bool) ($config['showCharsLeft'] ?? false))
            ->describedBy(($config['describedBy'] ?? false) ?: null)
            ->inputAttributes(Arr::merge(
                ['class' => Html::explodeClass($config['class'] ?? [])],
                $config['inputAttributes'] ?? [],
            ));
    }

    /** @param array<string, mixed> $config */
    public static function textareaFieldHtml(array $config): string
    {
        $config['id'] ??= 'textarea'.mt_rand();

        return self::fieldHtml(
            fn (array $c): string => self::textareaFromConfig($c)->toHtml(),
            $config,
        );
    }

    /** @param array<string, mixed> $config */
    public static function dateHtml(array $config): string
    {
        $html = self::dateFromConfig($config)->toHtml();

        return ($config['hasOuterContainer'] ?? false)
            ? $html
            : Html::tag('craft-input-date-time', $html, ['class' => 'datetimewrapper']);
    }

    /** @param array<string, mixed> $config */
    public static function dateFromConfig(array $config): InputDate
    {
        $id = ($config['id'] ?? 'date'.mt_rand()).'-date';
        $locale = I18N::getFormattingLocale()->id;
        $timezone = ($config['timeZone'] ?? null) === false
            ? self::valueTimezone($config['value'] ?? null)
            : (($config['timeZone'] ?? null) ?: Cms::timezone());

        return InputDate::make()
            ->id($id)
            ->name($config['name'] ?? null)
            ->value(self::formattedDateTimeValue($config['value'] ?? null, 'Y-m-d', $config['timeZone'] ?? null))
            ->min(self::formattedDateTimeValue($config['min'] ?? null, 'Y-m-d'))
            ->max(self::formattedDateTimeValue($config['max'] ?? null, 'Y-m-d'))
            ->inputSize(10)
            ->autocomplete(false)
            ->disabled((bool) ($config['disabled'] ?? false))
            ->readOnly((bool) ($config['readonly'] ?? false))
            ->describedBy(($config['describedBy'] ?? false) ?: null)
            ->inputAttributes(Arr::merge([
                'required' => (bool) ($config['required'] ?? false),
                'aria' => ['label' => ($config['isDateTime'] ?? false) ? t('Date') : null],
            ], $config['inputAttributes'] ?? []))
            ->locale($locale)
            ->timezone($timezone)
            ->outputLocaleParam((bool) ($config['outputLocaleParam'] ?? true))
            ->outputTimezoneParam((bool) ($config['outputTzParam'] ?? true))
            ->attributes(Arr::merge(
                ['class' => Html::explodeClass($config['class'] ?? [])],
                $config['containerAttributes'] ?? [],
            ));
    }

    /** @param array<string, mixed> $config */
    public static function dateFieldHtml(array $config): string
    {
        $config['id'] ??= 'date'.mt_rand();

        return self::fieldHtml(
            fn (array $c): string => self::dateHtml($c),
            $config,
        );
    }

    /** @param array<string, mixed> $config */
    public static function timeHtml(array $config): string
    {
        $html = self::timeFromConfig($config)->toHtml();

        return ($config['hasOuterContainer'] ?? false)
            ? $html
            : Html::tag('craft-input-date-time', $html, ['class' => 'datetimewrapper']);
    }

    /** @param array<string, mixed> $config */
    public static function timeFromConfig(array $config): InputTime
    {
        $id = ($config['id'] ?? 'time'.mt_rand()).'-time';
        $locale = I18N::getFormattingLocale()->id;
        $timezone = ($config['timeZone'] ?? null) === false
            ? self::valueTimezone($config['value'] ?? null)
            : (($config['timeZone'] ?? null) ?: Cms::timezone());

        return InputTime::make()
            ->id($id)
            ->name($config['name'] ?? null)
            ->value(self::formattedDateTimeValue($config['value'] ?? null, 'H:i', $config['timeZone'] ?? null))
            ->min(self::formattedTime($config['minTime'] ?? null))
            ->max(self::formattedTime($config['maxTime'] ?? null))
            ->inputSize(10)
            ->autocomplete(false)
            ->disabled((bool) ($config['disabled'] ?? false))
            ->readOnly((bool) ($config['readonly'] ?? false))
            ->describedBy(($config['describedBy'] ?? false) ?: null)
            ->inputAttributes(Arr::merge([
                'required' => (bool) ($config['required'] ?? false),
                'aria' => ['label' => ($config['isDateTime'] ?? false) ? t('Time') : null],
            ], $config['inputAttributes'] ?? []))
            ->locale($locale)
            ->timezone($timezone)
            ->outputLocaleParam((bool) ($config['outputLocaleParam'] ?? true))
            ->outputTimezoneParam((bool) ($config['outputTzParam'] ?? true))
            ->disabledTimeRanges(array_map(
                fn (array $range): array => [
                    self::formattedTime($range[0]) ?? '',
                    self::formattedTime($range[1]) ?? '',
                ],
                $config['disableTimeRanges'] ?? [],
            ))
            ->minuteIncrement((int) ($config['minuteIncrement'] ?? 30))
            ->forceRoundTime((bool) ($config['forceRoundTime'] ?? false))
            ->attributes(Arr::merge(
                ['class' => Html::explodeClass($config['class'] ?? [])],
                $config['containerAttributes'] ?? [],
            ));
    }

    /** @param array<string, mixed> $config */
    public static function timeFieldHtml(array $config): string
    {
        $config['id'] ??= 'time'.mt_rand();

        return self::fieldHtml(
            fn (array $c): string => self::timeHtml($c),
            $config,
        );
    }

    /** @param array<string, mixed> $config */
    public static function dateTimeHtml(array $config): string
    {
        return self::dateTimeFromConfig($config)->toHtml();
    }

    /** @param array<string, mixed> $config */
    public static function dateTimeFromConfig(array $config): InputDateTime
    {
        $value = $config['value'] ?? null;
        $timezone = ($config['timeZone'] ?? null) === false
            ? self::valueTimezone($value)
            : (($config['timeZone'] ?? null) ?: Cms::timezone());

        return InputDateTime::make()
            ->id($config['id'] ?? 'datetime'.mt_rand())
            ->name($config['name'] ?? null)
            ->dateValue(self::formattedDateTimeValue($value, 'Y-m-d', $config['timeZone'] ?? null))
            ->timeValue(self::formattedDateTimeValue($value, 'H:i', $config['timeZone'] ?? null))
            ->timezone($timezone)
            ->locale(I18N::getFormattingLocale()->id)
            ->min(self::formattedDateTimeValue($config['min'] ?? null, 'Y-m-d'))
            ->max(self::formattedDateTimeValue($config['max'] ?? null, 'Y-m-d'))
            ->minTime(self::formattedTime($config['minTime'] ?? null))
            ->maxTime(self::formattedTime($config['maxTime'] ?? null))
            ->disabledTimeRanges(array_map(
                fn (array $range): array => [
                    self::formattedTime($range[0]) ?? '',
                    self::formattedTime($range[1]) ?? '',
                ],
                $config['disableTimeRanges'] ?? [],
            ))
            ->minuteIncrement((int) ($config['minuteIncrement'] ?? 30))
            ->forceRoundTime((bool) ($config['forceRoundTime'] ?? false))
            ->disabled((bool) ($config['disabled'] ?? false))
            ->readOnly((bool) ($config['readonly'] ?? false))
            ->required((bool) ($config['required'] ?? false))
            ->describedBy(($config['describedBy'] ?? false) ?: null)
            ->attributes(Arr::merge(
                ['class' => Html::explodeClass($config['class'] ?? [])],
                $config['containerAttributes'] ?? [],
            ));
    }

    /** @param array<string, mixed> $config */
    public static function dateTimeFieldHtml(array $config): string
    {
        $config += [
            'id' => 'datetime'.mt_rand(),
            'fieldset' => true,
        ];

        return self::fieldHtml(
            fn (array $c): string => self::dateTimeHtml($c),
            $config,
        );
    }

    private static function formattedDateTimeValue(mixed $value, string $format, bool|string|null $timezone = null): ?string
    {
        $date = DateTimeHelper::toDateTime($value, true, $timezone !== false);

        if (! $date) {
            return null;
        }

        if (is_string($timezone)) {
            $date = Date::instance($date)->setTimezone($timezone);
        }

        return $date->format($format);
    }

    private static function formattedTime(mixed $value): ?string
    {
        if (is_numeric($value)) {
            $seconds = (int) $value;

            return sprintf('%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60));
        }

        return self::formattedDateTimeValue($value, 'H:i');
    }

    private static function valueTimezone(mixed $value): string
    {
        return $value instanceof DateTimeInterface
            ? $value->getTimezone()->getName()
            : Cms::timezone();
    }

    /** @param array<string, mixed> $config */
    public static function elementSelectHtml(array $config): string
    {
        return self::renderTemplate('_includes/forms/elementSelect', $config);
    }

    /** @param array<string, mixed> $config */
    public static function elementSelectFieldHtml(array $config): string
    {
        $config['id'] ??= 'elementselect'.mt_rand();

        return self::fieldHtml('template:_includes/forms/elementSelect', $config);
    }

    /** @param array<string, mixed> $config */
    public static function entryTypeSelectHtml(array $config): string
    {
        return self::renderTemplate('_includes/forms/entryTypeSelect', $config);
    }

    /** @param array<string, mixed> $config */
    public static function fieldSelectHtml(array $config): string
    {
        return self::renderTemplate('_includes/forms/fieldSelect', $config);
    }

    /** @param array<string, mixed> $config */
    public static function entryTypeSelectFieldHtml(array $config): string
    {
        $config['id'] ??= 'entrytypeselect'.mt_rand();

        return self::fieldHtml('template:_includes/forms/entryTypeSelect', $config);
    }

    /** @param array<string, mixed> $config */
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
            }
        }

        return self::fieldHtml('template:_includes/forms/autosuggest', $config);
    }

    public static function addressFieldsHtml(
        Address $address,
        bool $static = false,
        ?bool $belongsToCurrentUser = null,
    ): string {
        return FieldGroup::make()
            ->children(array_map(
                fn (array $field): HtmlString => new HtmlString(self::addressFieldHtml($field, $static)),
                app(Addresses::class)->getFormFieldDefinitions($address, $belongsToCurrentUser),
            ))
            ->toHtml();
    }

    /** @param AddressFormField $field */
    private static function addressFieldHtml(array $field, bool $static): string
    {
        $errors = $static && ($field['type'] === 'text' || ($field['spinner'] ?? false))
            ? []
            : $field['errors'];
        $fieldClass = array_filter([
            isset($field['width']) ? "width-{$field['width']}" : null,
            $field['visible'] ? null : 'hidden',
        ]);
        $config = [
            'fieldClass' => $fieldClass,
            'status' => $field['status'] ?? null,
            'label' => $field['label'],
            'id' => $field['name'],
            'name' => $field['name'],
            'value' => $field['value'],
            'options' => $field['options'] ?? null,
            'autocomplete' => $field['autocomplete'] ?? null,
            'required' => $field['required'],
            'errors' => $errors,
            'data' => ['error-key' => $field['name']],
            'disabled' => $static,
        ];

        if ($field['type'] === 'text') {
            return self::textFieldHtml($config);
        }

        if (! ($field['spinner'] ?? false)) {
            return self::selectizeFieldHtml($config);
        }

        $input = Html::tag('div', self::selectizeHtml($config).Html::tag('div', '', [
            'id' => "{$field['name']}-spinner",
            'class' => ['spinner', 'hidden'],
        ]), ['class' => ['flex', 'flex-nowrap']]);

        return self::fieldHtml($input, [
            'fieldClass' => $fieldClass,
            'label' => $field['label'],
            'id' => $field['name'],
            'required' => $field['required'],
            'errors' => $errors,
            'data' => ['error-key' => $field['name']],
            'disabled' => $static,
        ]);
    }

    /** @param array<string, mixed> $variables */
    private static function renderTemplate(string $template, array $variables = []): string
    {
        return template(''.$template, $variables, templateMode: TemplateMode::Cp);
    }
}
