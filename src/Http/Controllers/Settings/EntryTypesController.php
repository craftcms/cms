<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Settings;

use Craft;
use craft\base\FieldLayoutElement;
use craft\elements\Entry;
use craft\fieldlayoutelements\entries\EntryTitleField;
use craft\helpers\Cp;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Entry\EntryTypes;
use CraftCms\Cms\Entry\Models\EntryType as EntryTypeModel;
use CraftCms\Cms\Field\Contracts\ElementContainerFieldInterface;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Str;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

final class EntryTypesController
{
    use RespondsWithFlash;

    private bool $readOnly;

    private FieldLayout $fieldLayout;

    public function __construct(
        Request $request,
        Fields $fields,
        GeneralConfig $generalConfig,
        private EntryTypes $entryTypes,
    ) {
        $this->readOnly = ! $generalConfig->allowAdminChanges;

        /**
         * We assemble the field layout here before validation runs
         * so we can flash the old layout to the session.
         */
        if ($request->route()->getActionMethod() === 'store') {
            $this->fieldLayout = $fields->assembleLayoutFromPost();
            $this->fieldLayout->type = Entry::class;

            Session::flash('oldFieldLayout', $this->fieldLayout);
        }
    }

    public function index(): View
    {
        return view('craftcms::settings.entry-types.index');
    }

    public function create(): CpScreenResponse
    {
        return new CpScreenResponse()
            ->title(t('Create a new entry type'))
            ->addCrumb(t('Settings'), 'settings')
            ->addCrumb(t('Entry Types'), 'settings/entry-types')
            ->contentTemplate('settings/entry-types/_edit.twig', [
                'entryTypeId' => null,
                'entryType' => new EntryType,
                'typeName' => Entry::displayName(),
                'lowerTypeName' => Entry::lowerDisplayName(),
                'readOnly' => $this->readOnly,
            ])
            ->action('entry-types/save')
            ->redirectUrl('settings/entry-types')
            ->addAltAction(t('Save and continue editing'), [
                'redirect' => 'settings/entry-types/{id}',
                'shortcut' => true,
                'retainScroll' => true,
            ]);
    }

    public function edit(EntryTypeModel $entryType): CpScreenResponse
    {
        $entryTypeData = $this->entryTypes->getEntryTypeById($entryType->id);

        abort_if(is_null($entryTypeData), 404, 'Entry type not found');

        $fieldLayout = $entryTypeData->getFieldLayout();

        if ($entryTypeData->hasTitleField) {
            // Ensure the Title field is present
            if (! $fieldLayout->isFieldIncluded('title')) {
                $fieldLayout->prependElements([new EntryTitleField]);
            }
        } else {
            // Remove the title field
            foreach ($fieldLayout->getTabs() as $tab) {
                $elements = array_filter($tab->getElements(),
                    fn (FieldLayoutElement $element) => ! $element instanceof EntryTitleField);
                $tab->setElements($elements);
            }
        }

        return new CpScreenResponse()
            ->editUrl($entryTypeData->getCpEditUrl())
            ->title(trim($entryTypeData->name) ?: t('Edit Entry Type'))
            ->addCrumb(t('Settings'), 'settings')
            ->addCrumb(t('Entry Types'), 'settings/entry-types')
            ->contentTemplate('settings/entry-types/_edit.twig', [
                'entryTypeId' => $entryTypeData->id,
                'entryType' => $entryTypeData,
                'typeName' => Entry::displayName(),
                'lowerTypeName' => Entry::lowerDisplayName(),
                'readOnly' => $this->readOnly,
            ])
            ->unless(
                $this->readOnly,
                callback: function (CpScreenResponse $response) use ($entryTypeData) {
                    $response
                        ->action('entry-types/save')
                        ->redirectUrl(UrlHelper::cpReferralUrl() ?? 'settings/entry-types')
                        ->addAltAction(t('Save and continue editing'), [
                            'redirect' => 'settings/entry-types/{id}',
                            'shortcut' => true,
                            'retainScroll' => true,
                        ])
                        ->addAltAction(t('Save as a new entry type'), [
                            'params' => ['saveAsNew' => true],
                            'redirect' => 'settings/entry-types/{id}',
                        ])
                        ->addAltAction(t('Delete'), [
                            'action' => 'entry-types/delete',
                            'destructive' => true,
                        ])
                        ->metaSidebarHtml(Cp::metadataHtml([
                            t('ID') => $entryTypeData->id,
                            t('Used by') => function () use ($entryTypeData) {
                                $usages = $entryTypeData->findUsages();
                                if (empty($usages)) {
                                    return Html::tag('i', t('No usages'));
                                }

                                $labels = [];
                                $items = array_map(function (Section|ElementContainerFieldInterface $usage) use (
                                    &$labels
                                ) {
                                    $icon = $usage instanceof FieldInterface ? $usage::icon() : $usage->getIcon();
                                    $label = $labels[] = $usage->getUiLabel();
                                    $labelHtml = Html::beginTag('span', [
                                        'class' => ['flex', 'flex-nowrap', 'gap-s'],
                                    ]).
                                        Html::tag('div', Cp::iconSvg($icon), [
                                            'class' => ['cp-icon', 'small'],
                                        ]).
                                        Html::tag('span', Html::encode($label)).
                                        Html::endTag('span');

                                    return Html::a($labelHtml, $usage->getCpEditUrl());
                                }, $entryTypeData->findUsages());

                                // sort by label
                                array_multisort($labels, SORT_ASC, $items);

                                $items = array_map(fn ($item) => Html::li($item)->encode(false), $items);

                                return Html::ul()->items(...$items)->render();
                            },
                        ]));
                },
                default: function (CpScreenResponse $response) {
                    $response->noticeHtml(Cp::readOnlyNoticeHtml());
                },
            );
    }

    public function tableData(Request $request): JsonResponse
    {
        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('per_page', 100);
        $searchTerm = $request->input('search');
        $orderBy = match ($request->input('sort.0.field')) {
            '__slot:handle' => 'handle',
            default => 'name',
        };
        $sortDir = match ($request->input('sort.0.direction')) {
            'desc' => SORT_DESC,
            default => SORT_ASC,
        };

        [$pagination, $tableData] = $this->entryTypes->getTableData(page: $page,
            limit: $limit,
            searchTerm: $searchTerm,
            orderBy: $orderBy,
            sortDir: $sortDir,
        );

        return new JsonResponse([
            'pagination' => $pagination,
            'data' => $tableData,
        ]);
    }

    public function store(Request $request, EntryType $entryType): Response
    {
        $entryTypeId = $request->input('entryTypeId');

        if ($entryTypeId) {
            $entryTypeId = (int) $entryTypeId;
            abort_if(is_null($found = $this->entryTypes->getEntryTypeById($entryTypeId)), 400, "Invalid entry type ID: $entryType");

            $entryType->id = $found->id;
            $entryType->uid = $found->uid;
        }

        $saveAsNew = false;
        $originalEntryType = null;
        if ($entryTypeId && $saveAsNew = (bool) $request->input('saveAsNew')) {
            $originalEntryType = $entryType;
            $entryType = clone $entryType;
            $entryType->id = $entryType->uid = null;
        }

        // If we're duplicating the entry type and the handle hasn't changed, find a unique one
        if ($originalEntryType && $this->entryTypes->getEntryTypeByHandle($entryType->handle)) {
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

        if (! $this->fieldLayout->validate()) {
            return $this->asModelFailure($entryType, t('Couldn’t save entry type.'), 'entryType');
        }

        if ($saveAsNew) {
            $this->fieldLayout->resetUids();
        }

        $this->entryTypes->saveEntryType($entryType);

        return $this->asModelSuccess($entryType, t('Entry type saved.'), 'entryType');
    }

    public function destroy(Request $request): Response
    {
        $id = $request->input('entryTypeId') ?? $request->input('id');

        if (! $id) {
            throw ValidationException::withMessages([
                'id' => t('id or entryTypeId is required.'),
            ]);
        }

        $entryType = $this->entryTypes->getEntryTypeById($id);

        abort_if(is_null($entryType), 404, "Invalid entry type ID: $entryType");

        if (! $this->entryTypes->deleteEntryType($entryType)) {
            return $this->asFailure(t('Couldn’t delete “{name}”.', [
                'name' => $entryType->getUiLabel(),
            ]));
        }

        return $this->asSuccess(t('“{name}” deleted.', [
            'name' => $entryType->getUiLabel(),
        ]));
    }

    public function renderOverrideSettings(Request $request): JsonResponse
    {
        $entryType = $this->entryTypeForSelectInput($request);
        $entryType->name = $request->input('name', $entryType->name);
        $entryType->handle = $request->input('handle', $entryType->handle);
        $entryType->description = $request->input('description', $entryType->description);

        $namespace = Str::random(10);
        $view = Craft::$app->getView();

        $html = $view->namespaceInputs(
            fn () => $view->renderTemplate('_includes/forms/entry-type-select/selection-settings.twig', [
                'entryType' => $entryType,
            ]),
            $namespace,
        );

        return new JsonResponse([
            'settingsHtml' => $html,
            'namespace' => $namespace,
            'headHtml' => $view->getHeadHtml(),
            'bodyHtml' => $view->getBodyHtml(),
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

        $chipHtml = Cp::chipHtml($entryType, [
            'showHandle' => true,
            'showIndicators' => true,
            'showDescription' => true,
        ]);

        return new JsonResponse([
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

        $id = $request->input('id');
        $original = $this->entryTypes->getEntryTypeById($id);

        abort_if(is_null($original), 400, "Invalid entry type ID: $id");

        $entryType = clone $original;
        $entryType->original = $original;

        return $entryType;
    }
}
