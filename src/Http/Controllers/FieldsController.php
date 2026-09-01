<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers;

use CraftCms\Cms\Component\ComponentHelper;
use CraftCms\Cms\Condition\BaseCondition;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Cp\FieldLayoutDesigner\CardDesigner;
use CraftCms\Cms\Cp\FieldLayoutDesigner\FieldLayoutDesigner;
use CraftCms\Cms\Cp\Html\FieldHtml;
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
use CraftCms\Cms\Form\Controls\ConditionBuilder as ConditionBuilderControl;
use CraftCms\Cms\Form\Controls\FieldLayoutDesigner as FieldLayoutDesignerControl;
use CraftCms\Cms\Form\Controls\FieldSelect as FieldSelectControl;
use CraftCms\Cms\Form\Controls\GroupedEntryTypeManager as GroupedEntryTypeManagerControl;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormPayload;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Http\Requests\TableRequest;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Http\ViewModels\FieldEditViewModel;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Flash;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\View\HtmlStack;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use ReflectionException;
use ReflectionProperty;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

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
                ['label' => t('Settings'), 'href' => Url::cpUrl('settings')],
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
            ->formAttributes(['action' => action([self::class, 'store'])])
            ->inertiaPage('settings/fields/Edit', new FieldEditViewModel(
                $field,
                $this->fieldsService,
                multiInstanceTypesOnly: $request->boolean('multiInstanceTypesOnly'),
            ));
    }

    public function edit(Request $request, ?FieldInterface $field = null, ?int $fieldId = null): CpScreenResponse
    {
        $fieldId ??= $field->id ?? $request->input('fieldId');

        if (is_null($fieldId)) {
            return $this->create($request);
        }

        abort_if(is_null($found = $this->fieldsService->getFieldById((int) $fieldId)), 404, 'Field not found');

        $field ??= $found;

        return $this->editScreenResponse($field, $request->boolean('multiInstanceTypesOnly'));
    }

    private function editScreenResponse(FieldInterface $field, bool $multiInstanceTypesOnly = false): CpScreenResponse
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
            ->formAttributes(['action' => action([self::class, 'store'])])
            ->metaSidebarHtml($field->id ? app(FieldHtml::class)->metadataHtml($field) : null)
            ->inertiaPage('settings/fields/Edit', new FieldEditViewModel(
                $field,
                $this->fieldsService,
                $this->readOnly,
                $multiInstanceTypesOnly,
            ));

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

    public function renderForm(Request $request): JsonResponse
    {
        $data = $request->validate([
            'values' => ['required', 'array'],
            'values.fieldId' => ['nullable', 'integer'],
            'values.oldType' => ['nullable', 'string'],
            'values.type' => ['required', 'string', Rule::in($this->fieldsService->getAllFieldTypes())],
            'values.name' => ['nullable', 'string'],
            'values.handle' => ['nullable', 'string'],
            'values.instructions' => ['nullable', 'string'],
            'values.searchable' => ['nullable', 'boolean'],
            'values.translationMethod' => ['nullable', 'string'],
            'values.translationKeyFormat' => ['nullable', 'string'],
            'values.settings' => ['nullable', 'array'],
            'scope' => ['present', 'array', 'size:0'],
        ]);
        $values = $data['values'];
        $type = $values['type'];
        $oldType = $values['oldType'] ?? null;
        $settings = $values['settings'] ?? [];

        if ($oldType && ComponentHelper::validateComponentClass($oldType, FieldInterface::class)) {
            $settings = $this->compatibleSettings($settings, $type, $oldType);
        }

        $field = $this->fieldsService->createField([
            'type' => $type,
            'id' => $values['fieldId'] ?? null,
            'name' => $values['name'] ?? null,
            'handle' => $values['handle'] ?? null,
            'instructions' => $values['instructions'] ?? null,
            'searchable' => (bool) ($values['searchable'] ?? false),
            'translationMethod' => $values['translationMethod'] ?? TranslationMethod::None,
            'translationKeyFormat' => $values['translationKeyFormat'] ?? null,
            'settings' => $settings,
        ]);

        abort_unless($field instanceof Field, 500, 'Field types must extend the base field class.');

        return new JsonResponse([
            'form' => new FieldEditViewModel(
                $field,
                $this->fieldsService,
                multiInstanceTypesOnly: $request->boolean('multiInstanceTypesOnly'),
            )->form(),
        ]);
    }

    public function renderFieldLayoutDesigner(Request $request): JsonResponse
    {
        $data = $request->validate([
            'value' => ['present', 'array'],
            'elementType' => ['required', 'string', new ElementTypeRule],
            'name' => ['required', 'string'],
            'disabled' => ['required', 'boolean'],
            'customizableTabs' => ['required', 'boolean'],
            'withGeneratedFields' => ['required', 'boolean'],
            'withCardViewDesigner' => ['required', 'boolean'],
        ]);

        return new JsonResponse([
            'html' => FieldLayoutDesignerControl::designerHtml(
                $data['value'],
                $data['elementType'],
                $data['name'],
                $this->readOnly || $data['disabled'],
                $data['customizableTabs'],
                $data['withGeneratedFields'],
                $data['withCardViewDesigner'],
            ),
        ]);
    }

    public function renderGroupedEntryTypeManager(Request $request): JsonResponse
    {
        $data = $request->validate([
            'value' => ['present', 'array'],
            'name' => ['required', 'string'],
            'disabled' => ['required', 'boolean'],
        ]);

        return new JsonResponse([
            'html' => GroupedEntryTypeManagerControl::managerHtml(
                $data['value'],
                $data['name'],
                $data['disabled'],
            ),
        ]);
    }

    public function renderFieldSelect(Request $request): JsonResponse
    {
        $data = $request->validate([
            'value' => ['nullable', 'integer'],
            'limit' => ['nullable', 'integer'],
            'create' => ['required', 'boolean'],
            'name' => ['required', 'string'],
            'disabled' => ['required', 'boolean'],
        ]);

        $html = FieldSelectControl::selectHtml(
            $data['value'] ?? null,
            $data['limit'] ?? null,
            $data['create'],
            $data['name'],
            $data['disabled'],
        );

        return new JsonResponse([
            'html' => $html,
            'headHtml' => $this->HtmlStack->headHtml(),
            'bodyHtml' => $this->HtmlStack->bodyHtml(),
        ]);
    }

    public function renderConditionBuilder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'value' => ['present', 'array'],
            'conditionClass' => ['required', 'string', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! is_string($value) || ! is_a($value, BaseCondition::class, true)) {
                    $fail("The {$attribute} field must be a condition class.");
                }
            }],
            'queryParams' => ['present', 'array'],
            'queryParams.*' => ['string'],
            'forProjectConfig' => ['required', 'boolean'],
            'name' => ['required', 'string'],
            'disabled' => ['required', 'boolean'],
            'fieldLayouts' => ['nullable', 'array'],
        ]);

        $html = ConditionBuilderControl::builderHtml(
            $data['value'],
            $data['conditionClass'],
            $data['queryParams'],
            $data['forProjectConfig'],
            $data['name'],
            $data['disabled'],
            $data['fieldLayouts'] ?? [],
        );

        return new JsonResponse([
            'html' => $html,
            'headHtml' => $this->HtmlStack->headHtml(),
            'bodyHtml' => $this->HtmlStack->bodyHtml(),
        ]);
    }

    public function normalizeConditionBuilder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'serialized' => ['required', 'string'],
            'path' => ['required', 'array'],
            'path.*' => ['required', 'string'],
        ]);
        parse_str((string) $data['serialized'], $values);
        $config = Arr::get($values, implode('.', $data['path']), []);

        return new JsonResponse(['value' => is_array($config) ? $config : []]);
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
            'settings' => ['nullable', 'array'],
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
            'settings' => $this->typeSettingsFromRequest($request, $type, $oldField ?? null),
        ]);

        if (! $this->fieldsService->saveField($field)) {
            Flash::error(t('Couldn’t save field.'));

            $errors = $field->errors()->getMessages();

            if ($request->has('settings')) {
                $settingAttributes = array_keys($field->getSettings());
                $errors = collect($errors)->mapWithKeys(function (array $messages, string $attribute) use ($settingAttributes): array {
                    if (in_array($attribute, ['charLimit', 'byteLimit'], true)) {
                        return ['settings.fieldLimit' => $messages];
                    }

                    return in_array($attribute, $settingAttributes, true)
                        ? ["settings.{$attribute}" => $messages]
                        : [$attribute => $messages];
                })->all();
            }

            throw ValidationException::withMessages($errors);
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
    private function typeSettingsFromRequest(Request $request, string $type, ?FieldInterface $oldField = null): array
    {
        if ($request->has('settings')) {
            $settings = $oldField
                ? $this->compatibleSettings($oldField->getSettings(), $type, $oldField::class)
                : [];
            $changes = $request->array('settings');

            if (array_key_exists('fieldLimit', $changes) || array_key_exists('limitUnit', $changes)) {
                unset($settings['charLimit'], $settings['byteLimit']);
            }

            return array_replace($settings, $changes);
        }

        $settingsStr = $request->input('typeSettings');

        if (is_string($settingsStr) && $settingsStr !== '') {
            parse_str($settingsStr, $postedSettings);

            return $postedSettings['types'][Html::id($type)] ?? [];
        }

        return $request->input('types', [])[Html::id($type)] ?? [];
    }

    /**
     * @param  array<string, mixed>  $settings
     * @param  class-string<FieldInterface>  $type
     * @param  class-string<FieldInterface>  $oldType
     * @return array<string, mixed>
     */
    private function compatibleSettings(array $settings, string $type, string $oldType): array
    {
        if ($type === $oldType) {
            return $settings;
        }

        return array_filter($settings, function ($attribute) use ($type, $oldType) {
            try {
                $newProperty = new ReflectionProperty($type, $attribute);
                $oldProperty = new ReflectionProperty($oldType, $attribute);

                return $newProperty->getDeclaringClass()->name === $oldProperty->getDeclaringClass()->name;
            } catch (ReflectionException) {
                return false;
            }
        }, ARRAY_FILTER_USE_KEY);
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
        $component = $this->fieldLayoutComponent($request);

        return new JsonResponse([
            'form' => $this->layoutComponentSettingsPayload($component),
            'headHtml' => $this->HtmlStack->headHtml(),
            'bodyHtml' => $this->HtmlStack->bodyHtml(),
        ]);
    }

    public function refreshLayoutComponentSettings(Request $request): JsonResponse
    {
        $request->validate([
            'scope' => ['present', 'array'],
            'scope.*' => ['string'],
        ]);

        $component = $this->fieldLayoutComponent($request, $settings);
        $payload = $this->layoutComponentSettingsPayload($component, $settings ?? []);
        $scope = $request->array('scope');

        return new JsonResponse([
            'form' => $scope === [] ? $payload : $payload->forScope($scope),
            'headHtml' => $this->HtmlStack->headHtml(),
            'bodyHtml' => $this->HtmlStack->bodyHtml(),
        ]);
    }

    /** @param array<string, mixed> $values */
    private function layoutComponentSettingsPayload(FieldLayoutComponent $component, array $values = []): ?FormPayload
    {
        $context = new FormContext(
            namespace: 'settings',
            values: $values === [] ? [] : ['settings' => $values],
            refreshable: true,
        );

        $form = $component->settingsForm($context);

        return $form === null ? null : app(FormResolver::class)->resolve($form, $context);
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
            'settings' => ['nullable', 'array'],
        ]);

        $uid = $request->input('uid');
        $elementType = $request->input('elementType');
        $layoutConfig = $request->array('layoutConfig');

        abort_if(! isset($layoutConfig['tabs']), 400, 'Layout config doesn’t have any tabs.');

        $layoutConfig['type'] = $elementType;

        $componentConfig = $request->input('config', []);
        $componentConfig['elementType'] = $elementType;
        $settings = $request->input('settings');

        if (is_array($settings) && $settings !== []) {
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
                            // Keep track of the old field's UUID so we can update any conditions referencing it on save
                            $elementConfig['oldFieldUid'] ??= $elementConfig['fieldUid'] ?? null;

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
}
