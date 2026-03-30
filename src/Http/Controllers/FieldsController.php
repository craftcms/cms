<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers;

use Craft;
use craft\base\ElementInterface;
use craft\web\assets\fieldsettings\FieldSettingsAsset;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Component\ComponentHelper;
use CraftCms\Cms\Component\Contracts\Chippable;
use CraftCms\Cms\Component\Contracts\Colorable;
use CraftCms\Cms\Component\Contracts\CpEditable;
use CraftCms\Cms\Component\Contracts\Iconic;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Cp\FieldLayoutDesigner\CardDesigner;
use CraftCms\Cms\Cp\FieldLayoutDesigner\FieldLayoutDesigner;
use CraftCms\Cms\Cp\Html\ContentHtml;
use CraftCms\Cms\Cp\Icons;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Field;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Field\MissingField;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\Contracts\FieldLayoutProviderInterface;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\FieldLayoutComponent;
use CraftCms\Cms\FieldLayout\FieldLayoutElement;
use CraftCms\Cms\FieldLayout\FieldLayoutTab;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Flash;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Typecast;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\View\HtmlStack;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use ReflectionException;
use ReflectionProperty;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

class FieldsController
{
    use RespondsWithFlash;

    private bool $readOnly;

    public function __construct(
        GeneralConfig $generalConfig,
        private HtmlStack $HtmlStack,
        private readonly Fields $fieldsService,
    ) {
        $this->readOnly = ! $generalConfig->allowAdminChanges;
    }

    public function index(): View
    {
        return view('settings/fields/index', [
            'readOnly' => $this->readOnly,
        ]);
    }

    public function create(): CpScreenResponse
    {
        $field = $this->fieldsService->createField(PlainText::class);

        return $this->cpScreenResponse($field);
    }

    public function edit(Request $request, ?FieldInterface $field = null, ?int $fieldId = null): CpScreenResponse
    {
        $fieldId ??= $field->id ?? $request->input('fieldId');

        if (is_null($fieldId)) {
            return $this->create();
        }

        abort_if(is_null($found = $this->fieldsService->getFieldById((int) $fieldId)), 404, 'Field not found');

        if ($field === null) {
            $field = $found;
        }

        return $this->cpScreenResponse($field, $request);
    }

    public function renderSettings(Request $request): JsonResponse
    {
        $request->validate([
            'type' => ['required', 'string'],
            'oldType' => ['nullable', 'string'],
            'settings' => ['nullable', 'string'],
            'namespace' => ['nullable', 'string'],
            'oldNamespace' => ['nullable', 'string'],
        ]);

        $type = $request->input('type');
        $oldType = $request->input('oldType');
        $field = $this->fieldsService->createField($type);

        if ($oldType && ComponentHelper::validateComponentClass($oldType, FieldInterface::class)) {
            $settingsStr = $request->input('settings', '');
            parse_str((string) $settingsStr, $postedOldSettings);
            $oldNamespace = $request->input('oldNamespace');
            $settings = Arr::get($postedOldSettings, $oldNamespace, []);

            // Remove any settings that aren't defined by the same class between both types
            $settings = array_filter($settings, function ($attribute) use ($type, $oldType) {
                try {
                    $r1 = new ReflectionProperty($type, $attribute);
                    $r2 = new ReflectionProperty($oldType, $attribute);

                    return $r1->getDeclaringClass()->name === $r2->getDeclaringClass()->name;
                } catch (ReflectionException) {
                    return false;
                }
            }, ARRAY_FILTER_USE_KEY);

            Typecast::configure($field, $settings);
        }

        $html = template('settings/fields/_type-settings', [
            'field' => $field,
            'namespace' => $request->input('namespace'),
        ]);

        return new JsonResponse([
            'settingsHtml' => $html,
            'headHtml' => $this->HtmlStack->headHtml(),
            'bodyHtml' => $this->HtmlStack->bodyHtml(),
        ]);
    }

    public function store(Request $request): Response|CpScreenResponse
    {
        $request->validate([
            'type' => ['required', 'string'],
            'fieldId' => ['nullable', 'integer'],
            'name' => ['nullable', 'string'],
            'handle' => ['nullable', 'string'],
            'instructions' => ['nullable', 'string'],
            'searchable' => ['nullable', 'boolean'],
            'translationMethod' => ['nullable', 'string'],
            'translationKeyFormat' => ['nullable', 'string'],
        ]);

        $type = $request->input('type');
        $fieldId = $request->input('fieldId');

        if ($fieldId) {
            $fieldId = (int) $fieldId;
            $oldField = $this->fieldsService->getFieldById($fieldId);
            abort_if(is_null($oldField), 400, 'Invalid field ID: '.$fieldId);
            $oldField = clone $oldField;
            $fieldUid = $oldField->uid;
        } else {
            $fieldUid = null;
        }

        $field = $this->fieldsService->createField([
            'type' => $type,
            'id' => $fieldId,
            'uid' => $fieldUid,
            'name' => $request->input('name'),
            'handle' => $request->input('handle'),
            'instructions' => $request->input('instructions'),
            'searchable' => (bool) $request->input('searchable', true),
            'translationMethod' => $request->input('translationMethod', Field::TRANSLATION_METHOD_NONE),
            'translationKeyFormat' => $request->input('translationKeyFormat'),
            'settings' => $request->input('types', [])[Html::id($type)] ?? [],
        ]);

        if (! $this->fieldsService->saveField($field)) {
            Flash::fail(t('Couldn’t save field.'));

            return $this->edit($request, $field);
        }

        if ($request->input('addAnother')) {
            $redirect = Url::cpUrl('settings/fields/new', [
                'type' => $field::class,
            ]);
        } else {
            $redirect = null;
        }

        return $this->asModelSuccess($field, t('Field saved.'), 'field', [
            'selectorHtml' => app(FieldLayoutDesigner::class)->layoutElementSelectorHtml(new CustomField($field), true),
        ], $redirect);
    }

    public function destroy(Request $request): Response
    {
        $request->validate([
            'fieldId' => ['nullable', 'int'],
            'id' => ['nullable', 'int', 'required_without:fieldId'],
        ]);

        $fieldId = $request->input('fieldId') ?? $request->input('id');
        /** @var FieldInterface|Field|null $field */
        $field = $this->fieldsService->getFieldById((int) $fieldId);

        abort_if(is_null($field), 400, 'Invalid field ID: '.$fieldId);

        if (! $this->fieldsService->deleteField($field)) {
            return $this->asModelFailure($field, t('Couldn’t delete “{name}”.', [
                'name' => $field->name,
            ]));
        }

        return $this->asModelSuccess($field, t('“{name}” deleted.', [
            'name' => $field->name,
        ]));
    }

    public function renderLayoutComponentSettings(Request $request): JsonResponse
    {
        $element = $this->fieldLayoutComponent($request);
        $namespace = Str::random(10);
        $html = InputNamespace::namespaceInputs(fn () => $element->getSettingsHtml(), $namespace);

        return new JsonResponse([
            'settingsHtml' => $html,
            'namespace' => $namespace,
            'headHtml' => $this->HtmlStack->headHtml(),
            'bodyHtml' => $this->HtmlStack->bodyHtml(),
        ]);
    }

    public function applyLayoutTabSettings(Request $request): Response
    {
        /** @var FieldLayoutTab $tab */
        $tab = $this->fieldLayoutComponent($request);

        return new JsonResponse([
            'config' => $tab->toArray(),
            'labelHtml' => $tab->labelHtml(),
        ]);
    }

    public function applyLayoutElementSettings(Request $request): Response
    {
        /** @var FieldLayoutElement $element */
        $element = $this->fieldLayoutComponent($request, $settings);

        if (! empty($settings)) {
            $validateAttributes = array_intersect(
                array_keys(array_filter($settings)),
                ['name', 'handle', 'instructions'],
            );
        }

        if (! empty($validateAttributes) && $element instanceof CustomField) {
            $field = $element->getField();

            if ($field instanceof Field) {
                $field->validateHandleUniqueness = false;
            }

            if (! $field->validate($validateAttributes)) {
                if ($field->errors()->has('name')) {
                    $field->errors()->merge(['label' => $field->errors()->get('name')]);
                    $field->errors()->forget('name');
                }

                return $this->asModelFailure($field, t('Couldn’t apply changes.'), 'field');
            }
        }

        $selectorHtml = app(FieldLayoutDesigner::class)->layoutElementSelectorHtml($element);

        return new JsonResponse([
            'config' => ['type' => $element::class] + $element->toArray(),
            'selectorHtml' => $selectorHtml,
        ]);
    }

    public function renderCardPreview(Request $request, Fields $fields): JsonResponse
    {
        $request->validate([
            'fieldLayoutConfig' => ['required', 'array'],
            'cardElements' => ['nullable', 'array'],
            'showThumb' => ['nullable', 'boolean'],
            'thumbAlignment' => ['nullable', 'string'],
        ]);

        $fieldLayoutConfig = $request->input('fieldLayoutConfig');
        $fieldLayout = $fields->createLayout($fieldLayoutConfig);

        return new JsonResponse([
            'previewHtml' => app(CardDesigner::class)->previewHtml($fieldLayout),
        ]);
    }

    public function tableData(Request $request): Response
    {
        $page = (int) $request->input(Cms::config()->getPageTriggerParam(), 1);
        $limit = (int) $request->input('per_page', 100);
        $searchTerm = $request->input('search');
        $orderBy = match ($request->input('sort.0.field')) {
            '__slot:handle' => 'handle',
            'type' => 'type',
            default => 'name',
        };
        $sortDir = match ($request->input('sort.0.direction')) {
            'desc' => SORT_DESC,
            default => SORT_ASC,
        };

        [$pagination, $tableData] = $this->fieldsService->getTableData($page, $limit, $searchTerm, $orderBy, $sortDir);

        return $this->asSuccess(data: [
            'pagination' => $pagination,
            'data' => $tableData,
        ]);
    }

    private function fieldLayoutComponent(Request $request, ?array &$settings = null): FieldLayoutComponent
    {
        $request->validate([
            'uid' => ['required', 'string'],
            'elementType' => ['required', 'string'],
            'layoutConfig' => ['required', 'array'],
            'config' => ['nullable', 'array'],
            'settings' => ['nullable', 'string'],
            'settingsNamespace' => ['nullable', 'string'],
        ]);

        $uid = $request->input('uid');
        $elementType = $request->input('elementType');
        $layoutConfig = $request->array('layoutConfig');

        abort_if(! isset($layoutConfig['tabs']), 400, 'Layout config doesn’t have any tabs.');

        $layoutConfig['type'] = $elementType;

        $componentConfig = $request->input('config', []);
        $componentConfig['elementType'] = $elementType;
        $settingsStr = $request->input('settings');

        if ($settingsStr !== null) {
            parse_str((string) $settingsStr, $postedSettings);
            $settingsNamespace = $request->input('settingsNamespace');
            $settings = Arr::get($postedSettings, $settingsNamespace, []);
            $componentConfig = array_merge($componentConfig, $settings);
        }

        $isTab = false;

        foreach ($layoutConfig['tabs'] as &$tabConfig) {
            if (isset($tabConfig['uid']) && $tabConfig['uid'] === $uid) {
                $isTab = true;
                $tabConfig = array_merge($tabConfig, $componentConfig);
                break;
            }

            foreach ($tabConfig['elements'] as &$elementConfig) {
                if (isset($elementConfig['uid']) && $elementConfig['uid'] === $uid) {
                    $elementConfig = array_merge($elementConfig, $componentConfig);
                    break 2;
                }
            }
        }

        $layout = $this->fieldsService->createLayout($layoutConfig);

        if ($isTab) {
            foreach ($layout->getTabs() as $tab) {
                if ($tab->uid === $uid) {
                    return $tab;
                }
            }

            abort(400, "Invalid layout tab UUID: $uid");
        }

        $element = $layout->getElementByUid($uid);

        abort_if(! $element, 400, "Invalid layout element UUID: $uid");

        return $element;
    }

    private function cpScreenResponse(FieldInterface $field, ?Request $request = null): CpScreenResponse
    {
        // Supported translation methods
        // ---------------------------------------------------------------------

        $supportedTranslationMethods = [];
        $allFieldTypes = $this->fieldsService->getAllFieldTypes();

        foreach ($allFieldTypes as $class) {
            if ($class === $field::class || $class::isSelectable()) {
                $supportedTranslationMethods[$class] = $class::supportedTranslationMethods();
            }
        }

        // Allowed field types
        // ---------------------------------------------------------------------

        if (! $field->id) {
            $compatibleFieldTypes = $allFieldTypes;
        } else {
            $compatibleFieldTypes = $this->fieldsService->getCompatibleFieldTypes($field, includeCurrent: true);
        }

        $fieldTypeOptions = [];
        $fieldTypeNames = [];
        $foundCurrent = false;
        $missingFieldPlaceholder = null;
        $multiInstanceTypesOnly = (bool) $request?->input('multiInstanceTypesOnly');

        foreach ($allFieldTypes as $class) {
            $isCurrent = $class === ($field instanceof MissingField ? $field->expectedType : $field::class);
            $foundCurrent = $foundCurrent || $isCurrent;

            if (
                $isCurrent ||
                (
                    $class::isSelectable() &&
                    (! $multiInstanceTypesOnly || $class::isMultiInstance())
                )
            ) {
                $compatible = $isCurrent || $compatibleFieldTypes->contains($class);
                $name = $class::displayName();
                $option = [
                    $isCurrent && $field instanceof Iconic ? $field->getIcon() : $class::icon(),
                    'value' => $class,
                ];
                if ($compatible) {
                    $option['label'] = $name;
                } else {
                    $option['labelHtml'] = Html::beginTag('div', ['class' => 'inline-flex']).
                        Html::tag('span', Html::encode($name)).
                        Html::tag('span', Icons::svg('triangle-exclamation'), ['class' => ['cp-icon', 'small', 'warning']]).
                        Html::endTag('div');
                }
                $fieldTypeOptions[] = $option;
                $fieldTypeNames[] = $name;
            }
        }

        // Sort them by name
        array_multisort($fieldTypeNames, $fieldTypeOptions);

        if ($field instanceof MissingField) {
            if ($foundCurrent) {
                $field = $this->fieldsService->createField($field->expectedType);
            } else {
                array_unshift($fieldTypeOptions, ['value' => $field->expectedType, 'label' => '']);
                $missingFieldPlaceholder = $field->getPlaceholderHtml();
            }
        }

        // Page setup + render
        // ---------------------------------------------------------------------

        if ($field->id) {
            $title = trim((string) $field->name) ?: t('Edit Field');
        } else {
            $title = t('Create a new field');
        }

        $response = new CpScreenResponse()
            ->title($title)
            ->addCrumb(t('Settings'), 'settings')
            ->addCrumb(t('Fields'), 'settings/fields')
            ->contentTemplate('settings/fields/_edit.twig', [
                'fieldId' => $field->id,
                'field' => $field,
                'fieldTypeOptions' => $fieldTypeOptions,
                'missingFieldPlaceholder' => $missingFieldPlaceholder,
                'supportedTranslationMethods' => $supportedTranslationMethods,
                'readOnly' => $this->readOnly,
            ]);

        if (! $this->readOnly) {
            $response
                ->action('fields/save-field')
                ->redirectUrl(Url::cpReferralUrl() ?? 'settings/fields')
                ->addAltAction(t('Save and continue editing'), [
                    'redirect' => 'settings/fields/edit/{id}',
                    'shortcut' => true,
                    'retainScroll' => true,
                ])
                ->addAltAction(t('Save and add another'), [
                    'shortcut' => true,
                    'shift' => true,
                    'params' => ['addAnother' => 1],
                ])
                ->editUrl($field->id ? "settings/fields/edit/$field->id" : null);
        } else {
            $response->noticeHtml(app(ContentHtml::class)->readOnlyNoticeHtml());
        }

        $response
            ->prepareScreen(function () {
                Craft::$app->getView()->registerAssetBundle(FieldSettingsAsset::class);
                $this->HtmlStack->jsWithVars(fn ($typeId, $settingsId, $namespace) => <<<JS
new Craft.FieldSettingsToggle('#' + $typeId, '#' + $settingsId, $namespace, {
  wrapWithTypeClassDiv: true
})
JS, [
                    InputNamespace::namespaceId('type'),
                    InputNamespace::namespaceId('settings'),
                    InputNamespace::namespaceInputName('types[__TYPE__]'),
                ]);
            });

        if ($field->id) {
            if (! $this->readOnly) {
                $response
                    ->addAltAction(t('Delete'), [
                        'action' => 'fields/delete-field',
                        'redirect' => 'settings/fields',
                        'destructive' => true,
                        'confirm' => t('Are you sure you want to delete “{name}”?', [
                            'name' => $field->name,
                        ]),
                    ]);
            }
            $response
                ->metaSidebarHtml(app(ContentHtml::class)->metadataHtml([
                    t('ID') => $field->id,
                    t('Used by') => function () use ($field) {
                        $layouts = $this->fieldsService->findFieldUsages($field);

                        if ($layouts->isEmpty()) {
                            return Html::tag('i', t('No usages'));
                        }

                        /** @var FieldLayout[][] $layoutsByType */
                        $layoutsByType = $layouts
                            ->keyBy('uid')
                            ->groupBy(fn (FieldLayout $layout) => $layout->type ?? '__UNKNOWN__')
                            ->all();

                        /** @var FieldLayout[] $unknownLayouts */
                        $unknownLayouts = Arr::pull($layoutsByType, '__UNKNOWN__');
                        /** @var FieldLayout[] $layoutsWithProviders */
                        $layoutsWithProviders = [];

                        // re-fetch as many of these as we can from the element types,
                        // so they have a chance to supply the layout providers
                        foreach ($layoutsByType as $type => &$typeLayouts) {
                            /** @var class-string<ElementInterface> $type */
                            /** @phpstan-ignore-next-line */
                            foreach ($type::fieldLayouts(null) as $layout) {
                                if (isset($typeLayouts[$layout->uid]) && $layout->provider instanceof Chippable) {
                                    $layoutsWithProviders[] = $layout;
                                    unset($typeLayouts[$layout->uid]);
                                }
                            }
                        }
                        unset($typeLayouts);

                        $labels = [];
                        $items = array_map(function (FieldLayout $layout) use (&$labels) {
                            /** @var FieldLayoutProviderInterface&Chippable $provider */
                            $provider = $layout->provider;
                            $label = $labels[] = $provider->getUiLabel();
                            $url = $provider instanceof CpEditable ? $provider->getCpEditUrl() : null;
                            $icon = $provider instanceof Iconic ? $provider->getIcon() : null;

                            $labelHtml = Html::beginTag('span', [
                                'class' => ['flex', 'flex-nowrap', 'gap-s'],
                            ]);
                            if ($icon) {
                                $labelHtml .= Html::tag('div', Icons::svg($icon), [
                                    'class' => array_filter([
                                        'cp-icon',
                                        'small',
                                        $provider instanceof Colorable ? $provider->getColor()?->value : null,
                                    ]),
                                ]);
                            }
                            $labelHtml .= Html::tag('span', Html::encode($label)).
                                Html::endTag('span');

                            return $url ? Html::a($labelHtml, $url) : $labelHtml;
                        }, $layoutsWithProviders);

                        // sort by label
                        array_multisort($labels, SORT_ASC, $items);

                        foreach ($layoutsByType as $type => $typeLayouts) {
                            // any remaining layouts for this type?
                            if (! empty($typeLayouts)) {
                                /** @var class-string<ElementInterface> $type */
                                $items[] = t('{total, number} {type} {total, plural, =1{field layout} other{field layouts}}', [
                                    'total' => count($typeLayouts),
                                    'type' => $type::lowerDisplayName(),
                                ]);
                            }
                        }

                        if (! empty($unknownLayouts)) {
                            $items[] = t('{total, number} {type} {total, plural, =1{field layout} other{field layouts}}', [
                                'total' => count($unknownLayouts),
                                'type' => t('unknown'),
                            ]);
                        }

                        $items = array_map(fn ($item) => Html::li($item)->encode(false), $items);

                        return Html::ul()->items(...$items)->render();
                    },
                ]));
        }

        return $response;
    }
}
