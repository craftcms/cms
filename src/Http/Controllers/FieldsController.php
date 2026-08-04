<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers;

use CraftCms\Cms\Component\ComponentHelper;
use CraftCms\Cms\Component\Contracts\Iconic;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Cp\FieldLayoutDesigner\CardDesigner;
use CraftCms\Cms\Cp\FieldLayoutDesigner\FieldLayoutDesigner;
use CraftCms\Cms\Cp\Html\ContentHtml;
use CraftCms\Cms\Cp\Html\FieldHtml;
use CraftCms\Cms\Cp\Icons;
use CraftCms\Cms\Element\Validation\Rules\ElementTypeRule;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Enums\TranslationMethod;
use CraftCms\Cms\Field\Field;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Field\MissingField;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\FieldLayoutComponent;
use CraftCms\Cms\FieldLayout\FieldLayoutElement;
use CraftCms\Cms\FieldLayout\FieldLayoutTab;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Http\Requests\TableRequest;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Http\ViewModels\FieldEditViewModel;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Flash;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Typecast;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\View\HtmlStack;
use CraftCms\Cms\View\LegacyAssets\FieldSettingsAsset;
use CraftCms\Cms\View\LegacyAssets\InternalAssetRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
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
        private readonly HtmlStack $HtmlStack,
        private readonly Fields $fieldsService,
    ) {
        $this->readOnly = ! $generalConfig->allowAdminChanges;
    }

    public function index(TableRequest $request): \Inertia\Response
    {
        [$pagination, $tableData] = $this->fieldsService->getTableData(
            page: $request->page(),
            limit: $request->limit(),
            searchTerm: $request->search(),
            orderBy: $request->orderBy(),
            sortDir: $request->sortDir(),
        );

        return Inertia::render('settings/fields/Index', [
            'crumbs' => fn () => [
                ['label' => t('Settings'), 'url' => Url::cpUrl('settings')],
                ['label' => t('Fields')],
            ],
            'title' => t('Fields'),
            'sort' => $request->sort(),
            'data' => fn () => $tableData,
            'pagination' => fn () => $pagination,
            'searchTerm' => $request->search(),
        ]);
    }

    public function create(Request $request): CpScreenResponse
    {
        // Slideouts (`new Craft.CpScreenSlideout(Craft.getCpUrl('settings/fields/edit'))` with no
        // field ID, e.g. the field layout designer's "New field" button) still
        // consume the legacy Twig screen as JSON.
        if ($request->wantsJson()) {
            return $this->cpScreenResponse($this->fieldsService->createField(PlainText::class), $request);
        }

        // "Save and add another" links back here with the last-saved type preselected
        $type = $request->input('type');

        if (
            ! is_string($type) ||
            ! is_subclass_of($type, FieldInterface::class) ||
            ! ComponentHelper::validateComponentClass($type, FieldInterface::class) ||
            ! $type::isSelectable()
        ) {
            $type = PlainText::class;
        }

        $field = $this->fieldsService->createField($type);

        abort_unless($field instanceof Field, 500, 'Field types must extend the base field class.');

        return new CpScreenResponse()
            ->title(t('Create a new field'))
            ->addCrumb(t('Settings'), 'settings')
            ->addCrumb(t('Fields'), 'settings/fields')
            ->redirectUrl('settings/fields')
            ->inertiaPage('settings/fields/Edit', new FieldEditViewModel($field, $this->fieldsService));
    }

    public function edit(Request $request, ?FieldInterface $field = null, ?int $fieldId = null): CpScreenResponse
    {
        $fieldId ??= $field->id ?? $request->input('fieldId');

        if (is_null($fieldId)) {
            return $this->create($request);
        }

        abort_if(is_null($found = $this->fieldsService->getFieldById((int) $fieldId)), 404, 'Field not found');

        if ($field === null) {
            $field = $found;
        }

        if ($request->wantsJson()) {
            return $this->cpScreenResponse($field, $request);
        }

        return $this->editScreenResponse($field);
    }

    private function editScreenResponse(FieldInterface $field): CpScreenResponse
    {
        // If the field's type class exists again (e.g. a plugin was reinstalled),
        // swap the missing-field placeholder for a fresh instance of it
        if (
            $field instanceof MissingField &&
            $this->fieldsService->getAllFieldTypes()->contains($field->expectedType)
        ) {
            $field = $this->fieldsService->createField($field->expectedType);
        }

        /** @var Field $field */
        $response = new CpScreenResponse()
            ->title(trim((string) $field->name) ?: t('Edit Field'))
            ->addCrumb(t('Settings'), 'settings')
            ->addCrumb(t('Fields'), 'settings/fields')
            ->redirectUrl('settings/fields')
            ->editUrl("settings/fields/edit/$field->id")
            ->inertiaPage('settings/fields/Edit', new FieldEditViewModel($field, $this->fieldsService, $this->readOnly));

        if (! $this->readOnly) {
            $response->addAltAction(t('Delete'), [
                'variant' => 'danger',
                'confirm' => t('Are you sure you want to delete “{name}”?', [
                    'name' => $field->name,
                ]),
                'action' => [
                    'type' => 'http',
                    'method' => 'DELETE',
                    'url' => action([self::class, 'destroy'], ['fieldId' => $field->id]),
                    'body' => [
                        'redirect' => Crypt::encrypt(action([self::class, 'index'])),
                    ],
                ],
            ]);
        }

        return $response;
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
            'typeSettings' => ['nullable', 'string'],
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
            'translationMethod' => $request->enum('translationMethod', TranslationMethod::class, TranslationMethod::None),
            'translationKeyFormat' => $request->input('translationKeyFormat'),
            'settings' => $this->typeSettingsFromRequest($request, $type),
        ]);

        if (! $this->fieldsService->saveField($field)) {
            Flash::error(t('Couldn’t save field.'));

            throw ValidationException::withMessages($field->errors()->getMessages());
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

    /**
     * The Inertia edit screen posts the field type settings island as a
     * URL-encoded string (`typeSettings`), since its inputs are server-rendered
     * HTML rather than form state. The legacy Twig form posts a `types` array.
     */
    /** @return array<string, mixed> */
    private function typeSettingsFromRequest(Request $request, string $type): array
    {
        $settingsStr = $request->input('typeSettings');

        if (is_string($settingsStr) && $settingsStr !== '') {
            parse_str($settingsStr, $postedSettings);

            return $postedSettings['types'][Html::id($type)] ?? [];
        }

        return $request->input('types', [])[Html::id($type)] ?? [];
    }

    public function destroy(Request $request, int $fieldId): Response
    {
        /** @var FieldInterface|Field|null $field */
        $field = $this->fieldsService->getFieldById($fieldId);

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

    /** @param array<string, mixed>|null $settings */
    private function fieldLayoutComponent(Request $request, ?array &$settings = null): FieldLayoutComponent
    {
        $request->validate([
            'uid' => ['required', 'string'],
            'elementType' => ['required', 'string', new ElementTypeRule],
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

                    // If fieldId is set, we're replacing the selected field
                    if ($elementConfig['type'] === CustomField::class && isset($elementConfig['fieldId'])) {
                        if (! empty($elementConfig['fieldId'])) {
                            unset($elementConfig['fieldUid']);
                        } else {
                            unset($elementConfig['fieldId']);
                        }
                    }

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

        $supportedTranslationMethods = array_map(
            fn (array $translationMethods) => array_map(
                static fn (TranslationMethod $translationMethod) => $translationMethod->value,
                $translationMethods,
            ),
            $supportedTranslationMethods,
        );

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
                    'icon' => $isCurrent && $field instanceof Iconic ? $field->getIcon() : $class::icon(),
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
                ->formAttributes([
                    'action' => Url::cpUrl('settings/fields'),
                ])
                ->redirectUrl('settings/fields')
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
                app(InternalAssetRegistry::class)->register(FieldSettingsAsset::class);
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
                        'action' => [
                            'type' => 'http',
                            'method' => 'DELETE',
                            'url' => Url::cpUrl("settings/fields/$field->id"),
                            'body' => [
                                'redirect' => Crypt::encrypt(Url::cpUrl('settings/fields')),
                            ],
                        ],
                        'confirm' => t('Are you sure you want to delete “{name}”?', [
                            'name' => $field->name,
                        ]),
                    ]);
            }
            $response
                ->metaSidebarHtml(app(FieldHtml::class)->metadataHtml($field));
        }

        return $response;
    }
}
