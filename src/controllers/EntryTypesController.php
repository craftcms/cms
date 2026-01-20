<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\base\ElementContainerFieldInterface;
use craft\base\FieldInterface;
use craft\base\FieldLayoutElement;
use craft\base\Iconic;
use craft\elements\Entry;
use craft\enums\Color;
use craft\fieldlayoutelements\entries\EntryTitleField;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\Html;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\models\EntryType;
use craft\models\Section;
use craft\web\Controller;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * EntryTypesController handles various entry type-related tasks.
 *
 * Note that all actions in this controller require administrator access in order to execute.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
class EntryTypesController extends Controller
{
    private bool $readOnly;

    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        // All actions require an admin account (but not allowAdminChanges)
        $this->requireAdmin(false);

        $viewActions = ['edit', 'table-data'];
        if (in_array($action->id, $viewActions)) {
            // Some actions require admin but not allowAdminChanges
            $this->requireAdmin(false);
        } else {
            // All other actions require an admin & allowAdminChanges
            $this->requireAdmin();
        }

        $this->readOnly = !Craft::$app->getConfig()->getGeneral()->allowAdminChanges;

        return parent::beforeAction($action);
    }

    /**
     * Edit an entry type
     *
     * @param int|null $entryTypeId The entry type’s ID, if any.
     * @param EntryType|null $entryType The entry type being edited, if there were any validation errors.
     * @return Response
     * @throws NotFoundHttpException if the requested entry type cannot be found
     */
    public function actionEdit(?int $entryTypeId = null, ?EntryType $entryType = null): Response
    {
        if ($entryTypeId === null && $this->readOnly) {
            throw new ForbiddenHttpException('Administrative changes are disallowed in this environment.');
        }

        if ($entryTypeId !== null) {
            if ($entryType === null) {
                $entryType = Craft::$app->getEntries()->getEntryTypeById($entryTypeId);

                if (!$entryType) {
                    throw new NotFoundHttpException('Entry type not found');
                }
            }

            $title = trim($entryType->name) ?: Craft::t('app', 'Edit Entry Type');
        } else {
            if ($entryType === null) {
                $entryType = new EntryType();
            }

            $title = Craft::t('app', 'Create a new entry type');
        }

        $fieldLayout = $entryType->getFieldLayout();
        if ($entryType->hasTitleField) {
            // Ensure the Title field is present
            if (!$fieldLayout->isFieldIncluded('title')) {
                $fieldLayout->prependElements([new EntryTitleField()]);
            }
        } else {
            // Remove the title field
            foreach ($fieldLayout->getTabs() as $tab) {
                $elements = array_filter($tab->getElements(), fn(FieldLayoutElement $element) => !$element instanceof EntryTitleField);
                $tab->setElements($elements);
            }
        }

        $response = $this->asCpScreen()
            ->editUrl($entryType->getCpEditUrl())
            ->title($title)
            ->addCrumb(Craft::t('app', 'Settings'), 'settings')
            ->addCrumb(Craft::t('app', 'Entry Types'), 'settings/entry-types')
            ->contentTemplate('settings/entry-types/_edit.twig', [
                'entryTypeId' => $entryTypeId,
                'entryType' => $entryType,
                'typeName' => Entry::displayName(),
                'lowerTypeName' => Entry::lowerDisplayName(),
                'readOnly' => $this->readOnly,
            ]);

        if (!$this->readOnly) {
            $response
                ->action('entry-types/save')
                ->redirectUrl(UrlHelper::cpReferralUrl() ?? 'settings/entry-types')
                ->addAltAction(Craft::t('app', 'Save and continue editing'), [
                    'redirect' => 'settings/entry-types/{id}',
                    'shortcut' => true,
                    'retainScroll' => true,
                ]);

            if ($entryType->id) {
                $response->addAltAction(Craft::t('app', 'Save as a new entry type'), [
                    'params' => ['saveAsNew' => true],
                    'redirect' => 'settings/entry-types/{id}',
                ]);
            }
        } else {
            $response->noticeHtml(Cp::readOnlyNoticeHtml());
        }

        if ($entryType->id) {
            if (!$this->readOnly) {
                $response->addAltAction(Craft::t('app', 'Delete'), [
                    'action' => 'entry-types/delete',
                    'destructive' => true,
                ]);
            }

            $response
                ->metaSidebarHtml(Cp::metadataHtml([
                    Craft::t('app', 'ID') => $entryType->id,
                    Craft::t('app', 'Used by') => function() use ($entryType) {
                        $usages = $entryType->findUsages();
                        if (empty($usages)) {
                            return Html::tag('i', Craft::t('app', 'No usages'));
                        }

                        $labels = [];
                        $items = array_map(function(Section|ElementContainerFieldInterface $usage) use (&$labels) {
                            $icon = $usage instanceof FieldInterface && !$usage instanceof Iconic
                                ? $usage::icon()
                                : $usage->getIcon();
                            $label = $labels[] = $usage->getUiLabel();
                            $labelHtml = Html::beginTag('span', [
                                    'class' => ['flex', 'flex-nowrap', 'gap-s'],
                                ]) .
                                Html::tag('div', Cp::iconSvg($icon), [
                                    'class' => ['cp-icon', 'small'],
                                ]) .
                                Html::tag('span', Html::encode($label)) .
                                Html::endTag('span');
                            return Html::a($labelHtml, $usage->getCpEditUrl());
                        }, $entryType->findUsages());

                        // sort by label
                        array_multisort($labels, SORT_ASC, $items);

                        return Html::ul($items, [
                            'encode' => false,
                        ]);
                    },
                ]));
        }

        return $response;
    }

    /**
     * Saves an entry type.
     *
     * @return Response|null
     * @throws BadRequestHttpException
     */
    public function actionSave(): ?Response
    {
        $this->requirePostRequest();

        $entriesService = Craft::$app->getEntries();
        $entryTypeId = $this->request->getBodyParam('entryTypeId');

        if ($entryTypeId) {
            $entryType = $entriesService->getEntryTypeById($entryTypeId);
            if (!$entryType) {
                throw new BadRequestHttpException("Invalid entry type ID: $entryTypeId");
            }

            $saveAsNew = $this->request->getBodyParam('saveAsNew');
            if ($saveAsNew) {
                $originalEntryType = $entryType;
                $entryType = clone $entryType;
                $entryType->id = $entryType->uid = null;
            }
        } else {
            $entryType = new EntryType();
            $saveAsNew = false;
        }

        // Set the simple stuff
        $entryType->name = $this->request->getBodyParam('name', $entryType->name);
        $entryType->handle = $this->request->getBodyParam('handle', $entryType->handle);
        $entryType->description = $this->request->getBodyParam('description', $entryType->description);
        $entryType->icon = $this->request->getBodyParam('icon', $entryType->icon);
        $color = $this->request->getBodyParam('color', $entryType->color?->value);
        $entryType->color = $color && $color !== '__blank__' ? Color::from($color) : null;
        $entryType->uiLabelFormat = $this->request->getBodyParam('uiLabelFormat', $entryType->uiLabelFormat);
        $entryType->titleTranslationMethod = $this->request->getBodyParam('titleTranslationMethod', $entryType->titleTranslationMethod);
        $entryType->titleTranslationKeyFormat = $this->request->getBodyParam('titleTranslationKeyFormat', $entryType->titleTranslationKeyFormat);
        $entryType->titleFormat = $this->request->getBodyParam('titleFormat', $entryType->titleFormat);
        $entryType->allowLineBreaksInTitles = $this->request->getBodyParam('allowLineBreaksInTitles', $entryType->allowLineBreaksInTitles);
        $entryType->showSlugField = $this->request->getBodyParam('showSlugField', $entryType->showSlugField);
        $entryType->slugTranslationMethod = $this->request->getBodyParam('slugTranslationMethod', $entryType->slugTranslationMethod);
        $entryType->slugTranslationKeyFormat = $this->request->getBodyParam('slugTranslationKeyFormat', $entryType->slugTranslationKeyFormat);
        $entryType->showStatusField = $this->request->getBodyParam('showStatusField', $entryType->showStatusField);

        // If we're duplicating the entry type and the handle hasn't changed, find a unique one
        if ($entryType->handle === ($originalEntryType->handle ?? null)) {
            if (preg_match('/^(.*?)(\d+)$/', $entryType->handle, $match)) {
                $baseHandle = $match[1];
                $i = (int)$match[2];
            } else {
                $baseHandle = $entryType->handle;
                $i = 1;
            }
            do {
                $testHandle = sprintf('%s%s', $baseHandle, ++$i);
                if (!$entriesService->getEntryTypeByHandle($testHandle)) {
                    $entryType->handle = $testHandle;
                    break;
                }
            } while (true);
        }

        // Set the field layout
        $fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost();
        $fieldLayout->type = Entry::class;
        $entryType->setFieldLayout($fieldLayout);

        if (!$entryType->validate()) {
            if (isset($originalEntryType)) {
                $entryType->id = $originalEntryType->id;
                $entryType->uid = $originalEntryType->uid;
            }

            return $this->asModelFailure($entryType, Craft::t('app', 'Couldn’t save entry type.'), 'entryType');
        }

        if ($saveAsNew) {
            $fieldLayout->resetUids();
        }

        // Save it
        $entriesService->saveEntryType($entryType, false);
        return $this->asModelSuccess($entryType, Craft::t('app', 'Entry type saved.'), 'entryType');
    }

    /**
     * Deletes an entry type.
     *
     * @return Response
     */
    public function actionDelete(): Response
    {
        $this->requirePostRequest();

        $entryTypeId = $this->request->getBodyParam('entryTypeId') ?? $this->request->getRequiredBodyParam('id');

        $entriesService = Craft::$app->getEntries();
        $entryType = $entriesService->getEntryTypeById($entryTypeId);

        if (!$entryType) {
            throw new BadRequestHttpException("Invalid entry type ID: $entryType");
        }

        if (!$entriesService->deleteEntryType($entryType)) {
            return $this->asFailure(Craft::t('app', 'Couldn’t delete “{name}”.', [
                'name' => $entryType->getUiLabel(),
            ]));
        }

        return $this->asSuccess(Craft::t('app', '“{name}” deleted.', [
            'name' => $entryType->getUiLabel(),
        ]));
    }

    /**
     * Returns data formatted for AdminTable vue component
     *
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionTableData(): Response
    {
        $this->requireAcceptsJson();

        $entriesService = Craft::$app->getEntries();

        $page = (int)$this->request->getParam('page', 1);
        $limit = (int)$this->request->getParam('per_page', 100);
        $searchTerm = $this->request->getParam('search');
        $orderBy = match ($this->request->getParam('sort.0.field')) {
            '__slot:handle' => 'handle',
            default => 'name',
        };
        $sortDir = match ($this->request->getParam('sort.0.direction')) {
            'desc' => SORT_DESC,
            default => SORT_ASC,
        };

        [$pagination, $tableData] = $entriesService->getTableData($page, $limit, $searchTerm, $orderBy, $sortDir);

        return $this->asSuccess(data: [
            'pagination' => $pagination,
            'data' => $tableData,
        ]);
    }

    /**
     * Renders an entry type’s override settings for an entry type select input.
     *
     * @return Response
     * @since 5.6.0
     */
    public function actionRenderOverrideSettings(): Response
    {
        $entryType = $this->_entryTypeForSelectInput();
        $entryType->name = $this->request->getBodyParam('name') ?? $entryType->name;
        $entryType->handle = $this->request->getBodyParam('handle') ?? $entryType->handle;
        $entryType->description = $this->request->getBodyParam('description') ?? $entryType->description;

        $namespace = StringHelper::randomString(10);
        $view = Craft::$app->getView();

        $html = $view->namespaceInputs(
            fn() => $view->renderTemplate('_includes/forms/entry-type-select/selection-settings.twig', [
                'entryType' => $entryType,
            ]),
            $namespace,
        );

        return $this->asJson([
            'settingsHtml' => $html,
            'namespace' => $namespace,
            'headHtml' => $view->getHeadHtml(),
            'bodyHtml' => $view->getBodyHtml(),
        ]);
    }

    /**
     * Validates and returns an entry type’s override settings for an entry type select input.
     *
     * @return Response
     * @since 5.6.0
     */
    public function actionApplyOverrideSettings(): Response
    {
        $entryType = $this->_entryTypeForSelectInput();

        $settingsStr = $this->request->getBodyParam('settings');
        parse_str($settingsStr, $postedSettings);
        $settingsNamespace = $this->request->getRequiredBodyParam('settingsNamespace');
        $settings = array_filter(ArrayHelper::getValue($postedSettings, $settingsNamespace, []));

        if (!empty($settings)) {
            Craft::configure($entryType, $settings);
            $entryType->validateHandleUniqueness = false;

            if (!$entryType->validate(array_keys($settings))) {
                return $this->asModelFailure($entryType, Craft::t('app', 'Couldn’t apply changes.'), 'entryType');
            }
        }

        $chipHtml = Cp::chipHtml($entryType, [
            'showHandle' => true,
            'showIndicators' => true,
            'showDescription' => true,
        ]);

        return $this->asJson([
            'config' => $entryType->toArray(['id', 'name', 'handle', 'description']),
            'chipHtml' => $chipHtml,
        ]);
    }

    private function _entryTypeForSelectInput(): EntryType
    {
        $id = $this->request->getRequiredBodyParam('id');
        $original = Craft::$app->getEntries()->getEntryTypeById($id);

        if (!$original) {
            throw new BadRequestHttpException("Invalid entry type ID: $id");
        }

        $entryType = clone $original;
        $entryType->original = $original;
        return $entryType;
    }
}
