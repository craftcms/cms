<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers;

use CraftCms\Cms\Component\ComponentHelper;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Cp\FieldLayoutDesigner\CardDesigner;
use CraftCms\Cms\Cp\FieldLayoutDesigner\FieldLayoutDesigner;
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
use Deprecated;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
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

    public function index(TableRequest $request)
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
        // "Save and add another" links back here with the last-saved type preselected
        $type = $request->input('type');

        if (
            ! is_string($type) ||
            ! ComponentHelper::validateComponentClass($type, FieldInterface::class) ||
            ! $type::isSelectable()
        ) {
            $type = PlainText::class;
        }

        /** @var Field $field */
        $field = $this->fieldsService->createField($type);

        $response = new CpScreenResponse()
            ->title(t('Create a new field'))
            ->addCrumb(t('Settings'), 'settings')
            ->addCrumb(t('Fields'), 'settings/fields')
            ->redirectUrl('settings/fields')
            ->inertiaPage('settings/fields/Edit', new FieldEditViewModel(
                $field,
                $this->fieldsService,
                embedded: $request->wantsJson(),
                multiInstanceTypesOnly: $request->boolean('multiInstanceTypesOnly'),
            ));

        if ($request->wantsJson()) {
            $response->action('fields/save-field');
        }

        return $response;
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

        $response = $this->editScreenResponse($field, $request);

        if ($request->wantsJson() && ! $this->readOnly) {
            $response->action('fields/save-field');
        }

        return $response;
    }

    private function editScreenResponse(FieldInterface $field, Request $request): CpScreenResponse
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
            ->inertiaPage('settings/fields/Edit', new FieldEditViewModel(
                $field,
                $this->fieldsService,
                $this->readOnly,
                embedded: $request->wantsJson(),
                multiInstanceTypesOnly: $request->boolean('multiInstanceTypesOnly'),
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

    public function renderSettings(Request $request): JsonResponse
    {
        $request->validate([
            'type' => ['required', 'string'],
            'oldType' => ['nullable', 'string'],
            'settings' => ['nullable', 'array'],
        ]);

        $type = $request->input('type');
        $oldType = $request->input('oldType');
        $field = $this->fieldsService->createField($type);

        if ($oldType && ComponentHelper::validateComponentClass($oldType, FieldInterface::class)) {
            $settings = $request->array('settings');

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

        /** @var Field $field */
        $viewModel = new FieldEditViewModel($field, $this->fieldsService);

        return new JsonResponse([
            'definition' => $viewModel->settingsDefinition(),
            'values' => $viewModel->settingsValues(),
            'errors' => $viewModel->settingsErrors(),
            'bindingScope' => $viewModel->settingsBindingScope(),
            'inputNamespace' => $viewModel->settingsInputNamespace(),
            'readOnly' => false,
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
            'types' => ['nullable', 'array'],
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
            'settings' => $request->input('types', [])[Html::id($type)] ?? [],
        ]);

        if (! $this->fieldsService->saveField($field)) {
            Flash::error(t('Couldn’t save field.'));

            throw ValidationException::withMessages(
                new FieldEditViewModel($field, $this->fieldsService)->settingsErrors(),
            );
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

    #[Deprecated(message: 'in 6.0. Use `settings/fields` instead.')]
    public function tableData(TableRequest $request): Response
    {
        [$pagination, $tableData] = $this->fieldsService->getTableData(
            page: $request->page(),
            limit: $request->limit(),
            searchTerm: $request->search(),
            orderBy: $request->orderBy(),
            sortDir: $request->sortDir(),
        );

        return $this->asSuccess(data: [
            'pagination' => $pagination,
            'data' => $tableData,
        ]);
    }

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
}
