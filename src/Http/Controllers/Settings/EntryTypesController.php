<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Settings;

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Cp\FieldLayoutDesigner\FieldLayoutDesigner;
use CraftCms\Cms\Cp\Html\ContentHtml;
use CraftCms\Cms\Cp\Html\ElementHtml;
use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\EntryTypes;
use CraftCms\Cms\Entry\Models\EntryType as EntryTypeModel;
use CraftCms\Cms\Entry\Resources\EntryTypeResource;
use CraftCms\Cms\Field\Enums\TranslationMethod;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\FieldLayoutElement;
use CraftCms\Cms\FieldLayout\LayoutElements\Entries\EntryTitleField;
use CraftCms\Cms\Http\Requests\TableRequest;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Shared\Enums\Color;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\View\HtmlStack;
use Deprecated;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

class EntryTypesController
{
    use RespondsWithFlash;

    private bool $readOnly;

    private FieldLayout $fieldLayout;

    public function __construct(
        Request $request,
        Fields $fields,
        GeneralConfig $generalConfig,
        private readonly EntryTypes $entryTypes,
        private readonly FieldLayoutDesigner $fieldLayoutDesigner,
    ) {
        $this->readOnly = ! $generalConfig->allowAdminChanges;

        if ($request->route()->getActionMethod() === 'store') {
            $this->fieldLayout = $fields->assembleLayoutFromPost();
            $this->fieldLayout->type = Entry::class;
        }
    }

    public function index(TableRequest $request)
    {
        [$pagination, $tableData] = $this->entryTypes->getTableData(
            page: $request->page(),
            limit: $request->limit(),
            searchTerm: $request->search(),
            orderBy: $request->orderBy(),
            sortDir: $request->sortDir(),
        );

        return Inertia::render('settings/entry-types/Index', [
            'crumbs' => fn () => [
                ['label' => t('Settings'), 'url' => Url::cpUrl('settings')],
                ['label' => t('Entry Types')],
            ],
            'title' => t('Entry Types'),
            'searchTerm' => $request->search(),
            'sort' => $request->sort(),
            'data' => fn () => $tableData,
            'pagination' => fn () => $pagination,
            'readOnly' => $this->readOnly,
        ]);
    }

    public function create(): CpScreenResponse
    {
        $entryType = new EntryType;

        return new CpScreenResponse()
            ->title(t('Create a new entry type'))
            ->addCrumb(t('Settings'), 'settings')
            ->addCrumb(t('Entry Types'), 'settings/entry-types')
            ->redirectUrl('settings/entry-types')
            ->inertiaPage('settings/EntryTypesEdit', $this->entryTypeProps($entryType, brandNew: true));
    }

    public function edit(Request $request, ?EntryTypeModel $entryType = null): CpScreenResponse
    {
        $entryType ??= EntryTypeModel::find($request->input('entryTypeId'));

        abort_if(is_null($entryType), 404, 'Entry type not found');

        $entryTypeData = $this->entryTypes->getEntryTypeById($entryType->id);

        abort_if(is_null($entryTypeData), 404, 'Entry type not found');

        $response = new CpScreenResponse()
            ->editUrl($entryTypeData->getCpEditUrl())
            ->title(trim($entryTypeData->name) ?: t('Edit Entry Type'))
            ->addCrumb(t('Settings'), 'settings')
            ->addCrumb(t('Entry Types'), 'settings/entry-types')
            ->redirectUrl('settings/entry-types')
            ->metaSidebarHtml(app(ContentHtml::class)->metadataHtml($entryTypeData->getMetadata()))
            ->inertiaPage('settings/EntryTypesEdit', $this->entryTypeProps($entryTypeData, brandNew: false));

        if (! $this->readOnly) {
            if ($entryTypeData->id) {
                $response->addAltAction(t('Delete'), [
                    'variant' => 'danger',
                    'action' => [
                        'type' => 'http',
                        'method' => 'DELETE',
                        'url' => action([EntryTypesController::class, 'destroy'], [$entryTypeData->id]),
                        'body' => [
                            'redirect' => Crypt::encrypt(action([EntryTypesController::class, 'index'])),
                        ],
                    ],
                ]);
            }
        }

        return $response;
    }

    /**
     * Builds the Inertia props for the entry type edit/new screen.
     */
    private function entryTypeProps(EntryType $entryType, bool $brandNew): array
    {
        $fieldLayout = $entryType->getFieldLayout();

        // Normalize the Title field's presence so the round-tripped config matches what gets saved back.
        if ($entryType->hasTitleField) {
            if (! $fieldLayout->isFieldIncluded('title')) {
                $fieldLayout->prependElements([new EntryTitleField]);
            }
        } else {
            foreach ($fieldLayout->getTabs() as $tab) {
                $elements = array_filter(
                    $tab->getElements(),
                    fn (FieldLayoutElement $element) => ! $element instanceof EntryTitleField,
                );
                $tab->setElements($elements);
            }
        }

        // Render just the designer markup (with `autoBoot: false`, so it doesn't queue its
        // own boot JS).
        $fieldLayoutDesigner = [
            'html' => $this->fieldLayoutDesigner->fieldHtml($fieldLayout, [
                'disabled' => $this->readOnly,
                'withGeneratedFields' => true,
                'withCardViewDesigner' => true,
                'autoBoot' => false,
            ]),
        ];

        return [
            'brandNew' => $brandNew,
            'entryType' => [
                'id' => $entryType->id,
                'color' => $entryType->color?->value,
                'icon' => $entryType->icon,
                'name' => $entryType->name,
                'handle' => $entryType->handle,
                'description' => $entryType->description,
                'uiLabelFormat' => $entryType->uiLabelFormat,
                'titleTranslationMethod' => $entryType->titleTranslationMethod->value,
                'titleTranslationKeyFormat' => $entryType->titleTranslationKeyFormat,
                'titleFormat' => $entryType->titleFormat,
                'allowLineBreaksInTitles' => (bool) $entryType->allowLineBreaksInTitles,
                'showSlugField' => (bool) $entryType->showSlugField,
                'slugTranslationMethod' => $entryType->slugTranslationMethod->value,
                'slugTranslationKeyFormat' => $entryType->slugTranslationKeyFormat,
                'showStatusField' => (bool) $entryType->showStatusField,
            ],
            'metadataHtml' => app(ContentHtml::class)->metadataHtml($entryType->getMetadata()),
            'fieldLayoutDesigner' => $fieldLayoutDesigner,
            'translationMethodOptions' => TranslationMethod::asOptions(),
            'typeName' => Entry::displayName(),
            'lowerTypeName' => Entry::lowerDisplayName(),
            'isMultiSite' => Sites::isMultiSite(),
            'readOnly' => $this->readOnly,
        ];
    }

    #[Deprecated(message: 'in 6.0. Use `settings/entry-types` instead.')]
    public function tableData(TableRequest $request): JsonResponse
    {
        [$pagination, $tableData] = $this->entryTypes->getTableData(
            page: $request->page(),
            limit: $request->limit(),
            searchTerm: $request->search(),
            orderBy: $request->orderBy(),
            sortDir: $request->sortDir(),
        );

        return new JsonResponse([
            'pagination' => $pagination,
            'data' => $tableData,
        ]);
    }

    public function store(Request $request): Response
    {
        $entryTypeId = $request->input('entryTypeId');

        if ($entryTypeId) {
            $entryTypeId = (int) $entryTypeId;
            abort_if(is_null($entryType = $this->entryTypes->getEntryTypeById($entryTypeId)), 400, "Invalid entry type ID: $entryType");

            $saveAsNew = $request->boolean('saveAsNew');
            if ($saveAsNew) {
                $originalEntryType = $entryType;
                $entryType = clone $entryType;
                $entryType->id = $entryType->uid = null;
            }
        } else {
            $entryType = new EntryType;
            $saveAsNew = false;
        }

        // Set the simple stuff
        $entryType->name = $request->input('name', $entryType->name);
        $entryType->handle = $request->input('handle', $entryType->handle);
        $entryType->description = $request->input('description', $entryType->description);
        $entryType->icon = $request->input('icon', $entryType->icon);
        $color = $request->input('color', $entryType->color?->value);
        $entryType->color = $color && $color !== '__blank__' ? Color::from($color) : null;
        $entryType->uiLabelFormat = $request->input('uiLabelFormat', $entryType->uiLabelFormat);
        $entryType->titleTranslationMethod = $request->enum('titleTranslationMethod', TranslationMethod::class, $entryType->titleTranslationMethod);
        $entryType->titleTranslationKeyFormat = $request->input('titleTranslationKeyFormat', $entryType->titleTranslationKeyFormat);
        $entryType->titleFormat = $request->input('titleFormat', $entryType->titleFormat);
        $entryType->allowLineBreaksInTitles = $request->boolean('allowLineBreaksInTitles', $entryType->allowLineBreaksInTitles);
        $entryType->showSlugField = $request->boolean('showSlugField', $entryType->showSlugField);
        $entryType->slugTranslationMethod = $request->enum('slugTranslationMethod', TranslationMethod::class, $entryType->slugTranslationMethod);
        $entryType->slugTranslationKeyFormat = $request->input('slugTranslationKeyFormat', $entryType->slugTranslationKeyFormat);
        $entryType->showStatusField = $request->boolean('showStatusField', $entryType->showStatusField);

        // If we're duplicating the entry type and the handle hasn't changed, find a unique one
        if ($saveAsNew && $entryType->handle === ($originalEntryType->handle ?? null)) {
            if (preg_match('/^(.*?)(\d+)$/', (string) $entryType->handle, $match)) {
                $baseHandle = $match[1];
                $i = (int) $match[2];
            } else {
                $baseHandle = $entryType->handle;
                $i = 1;
            }
            do {
                $testHandle = sprintf('%s%s', $baseHandle, ++$i);
                if (! $this->entryTypes->getEntryTypeByHandle($testHandle)) {
                    $entryType->handle = $testHandle;
                    break;
                }
            } while (true);
        }

        $entryType->setFieldLayout($this->fieldLayout);

        $entryType->validate(throw: true);

        if (! $this->fieldLayout->validate()) {
            return $this->asModelFailure($entryType, t('Couldn’t save entry type.'), 'entryType');
        }

        if ($saveAsNew) {
            $this->fieldLayout->resetUids();
        }

        $this->entryTypes->saveEntryType($entryType);

        return $this->asModelSuccess(
            $entryType,
            t('Entry type saved.'),
            'entryType',
            // A "save as new" submit should land on the newly-created entry type
            // rather than the posted (original) redirect.
            redirect: $saveAsNew ? Url::cpUrl("settings/entry-types/{$entryType->id}") : null,
        );
    }

    public function destroy(Request $request, ?EntryTypeModel $entryType = null): Response
    {
        $entryType ??= EntryTypeModel::find($request->input('entryTypeId'));

        abort_if(is_null($entryType), 404, 'Entry type not found');

        $entryTypeData = $this->entryTypes->getEntryTypeById($entryType->id);

        abort_if(is_null($entryTypeData), 404, 'Entry type not found');

        if (! $this->entryTypes->deleteEntryType($entryTypeData)) {
            return $this->asFailure(t('Couldn’t delete “{name}”.', [
                'name' => $entryTypeData->getUiLabel(),
            ]));
        }

        return $this->asSuccess(t('“{name}” deleted.', [
            'name' => $entryTypeData->getUiLabel(),
        ]), redirect: action([EntryTypesController::class, 'index']));
    }

    public function renderOverrideSettings(Request $request, HtmlStack $HtmlStack): JsonResponse
    {
        $entryType = $this->entryTypeForSelectInput($request);
        $entryType->name = $request->input('name', $entryType->name);
        $entryType->handle = $request->input('handle', $entryType->handle);
        $entryType->description = $request->input('description', $entryType->description);

        $namespace = Str::random(10);

        $html = InputNamespace::namespaceInputs(
            fn () => template('_includes/forms/entry-type-select/selection-settings', [
                'entryType' => $entryType,
            ]),
            $namespace,
        );

        return new JsonResponse([
            'settingsHtml' => $html,
            'namespace' => $namespace,
            'headHtml' => $HtmlStack->headHtml(),
            'bodyHtml' => $HtmlStack->bodyHtml(),
        ]);
    }

    public function applyOverrideSettings(Request $request): Response
    {
        $request->validate([
            'settings' => ['nullable'],
            'settingsNamespace' => ['required'],
        ]);

        $entryType = $this->entryTypeForSelectInput($request);
        $settingsStr = $request->input('settings', '');
        parse_str((string) $settingsStr, $postedSettings);

        $settingsNamespace = $request->input('settingsNamespace');
        $settings = array_filter(Arr::get($postedSettings, $settingsNamespace, []));

        if (! empty($settings)) {
            foreach ($settings as $key => $value) {
                $entryType->{$key} = $value;
            }

            $entryType->validateHandleUniqueness = false;

            if (! $entryType->validate($settings)) {
                return $this->asModelFailure($entryType, t('Couldn’t apply changes.'), 'entryType');
            }
        }

        $chipHtml = app(ElementHtml::class)->chipHtml($entryType, [
            'showHandle' => true,
            'showIndicators' => true,
            'showDescription' => true,
        ]);

        return new JsonResponse([
            'entryType' => EntryTypeResource::make($entryType)->additional([
                'id' => $entryType->id,
                'name' => $entryType->name,
                'handle' => $entryType->handle,
                'description' => $entryType->description,
            ]),
            'config' => [
                'id' => $entryType->id,
                'name' => $entryType->name,
                'handle' => $entryType->handle,
                'description' => $entryType->description,
            ],
            'chipHtml' => $chipHtml,
        ]);
    }

    private function entryTypeForSelectInput(Request $request): EntryType
    {
        $request->validate(['id' => ['required', 'integer']]);

        $id = (int) $request->input('id');
        $original = $this->entryTypes->getEntryTypeById($id);

        abort_if(is_null($original), 400, "Invalid entry type ID: $id");

        $entryType = clone $original;
        $entryType->original = $original;

        return $entryType;
    }
}
