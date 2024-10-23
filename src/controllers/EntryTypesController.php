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
use craft\elements\Entry;
use craft\enums\Color;
use craft\fieldlayoutelements\entries\EntryTitleField;
use craft\helpers\Cp;
use craft\helpers\Html;
use craft\models\EntryType;
use craft\models\Section;
use craft\web\Controller;
use yii\web\BadRequestHttpException;
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
    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        // All section actions require an admin
        $this->requireAdmin();

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
            ->action('entry-types/save')
            ->redirectUrl('settings/entry-types')
            ->addAltAction(Craft::t('app', 'Save and continue editing'), [
                'redirect' => 'settings/entry-types/{id}',
                'shortcut' => true,
                'retainScroll' => true,
            ])
            ->contentTemplate('settings/entry-types/_edit.twig', [
                'entryTypeId' => $entryTypeId,
                'entryType' => $entryType,
                'typeName' => Entry::displayName(),
                'lowerTypeName' => Entry::lowerDisplayName(),
            ]);

        if ($entryType->id) {
            $response
                ->addAltAction(Craft::t('app', 'Delete'), [
                    'action' => 'entry-types/delete',
                    'destructive' => true,
                ])
                ->metaSidebarHtml(Cp::metadataHtml([
                    Craft::t('app', 'ID') => $entryType->id,
                    Craft::t('app', 'Used by') => function() use ($entryType) {
                        $usages = $entryType->findUsages();
                        if (empty($usages)) {
                            return Html::tag('i', Craft::t('app', 'No usages'));
                        }

                        $labels = [];
                        $items = array_map(function(Section|ElementContainerFieldInterface $usage) use (&$labels) {
                            $icon = $usage instanceof FieldInterface ? $usage::icon() : $usage->getIcon();
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

        $sectionsService = Craft::$app->getEntries();
        $entryTypeId = $this->request->getBodyParam('entryTypeId');

        if ($entryTypeId) {
            $entryType = $sectionsService->getEntryTypeById($entryTypeId);
            if (!$entryType) {
                throw new BadRequestHttpException("Invalid entry type ID: $entryTypeId");
            }
        } else {
            $entryType = new EntryType();
        }

        // Set the simple stuff
        $entryType->name = $this->request->getBodyParam('name', $entryType->name);
        $entryType->handle = $this->request->getBodyParam('handle', $entryType->handle);
        $entryType->icon = $this->request->getBodyParam('icon', $entryType->icon);
        $color = $this->request->getBodyParam('color', $entryType->color?->value);
        $entryType->color = $color && $color !== '__blank__' ? Color::from($color) : null;
        $entryType->titleTranslationMethod = $this->request->getBodyParam('titleTranslationMethod', $entryType->titleTranslationMethod);
        $entryType->titleTranslationKeyFormat = $this->request->getBodyParam('titleTranslationKeyFormat', $entryType->titleTranslationKeyFormat);
        $entryType->titleFormat = $this->request->getBodyParam('titleFormat', $entryType->titleFormat);
        $entryType->showSlugField = $this->request->getBodyParam('showSlugField', $entryType->showSlugField);
        $entryType->slugTranslationMethod = $this->request->getBodyParam('slugTranslationMethod', $entryType->slugTranslationMethod);
        $entryType->slugTranslationKeyFormat = $this->request->getBodyParam('slugTranslationKeyFormat', $entryType->slugTranslationKeyFormat);
        $entryType->showStatusField = $this->request->getBodyParam('showStatusField', $entryType->showStatusField);

        // Set the field layout
        $fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost();
        $fieldLayout->type = Entry::class;
        $entryType->setFieldLayout($fieldLayout);

        // Save it
        if (!$sectionsService->saveEntryType($entryType)) {
            return $this->asModelFailure($entryType, Craft::t('app', 'Couldn’t save entry type.'), 'entryType');
        }

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
}
