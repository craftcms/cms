<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\elements\Entry;
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
     * Entry types index
     *
     * @return Response
     */
    public function actionIndex(): Response
    {
        $sectionsService = Craft::$app->getEntries();
        $entryTypes = $sectionsService->getAllEntryTypes();
        usort($entryTypes, fn(EntryType $a, EntryType $b) => Craft::t('site', $a->name) <=> Craft::t('site', $b->name));

        $sectionsByEntryType = [];
        $sections = $sectionsService->getAllSections();
        usort($sections, fn(Section $a, Section $b) => Craft::t('site', $a->name) <=> Craft::t('site', $b->name));
        foreach ($sections as $section) {
            foreach ($section->getEntryTypes() as $entryType) {
                $sectionsByEntryType[$entryType->id][] = $section;
            }
        }

        return $this->renderTemplate('settings/entry-types/_index.twig', [
            'entryTypes' => $entryTypes,
            'sectionsByEntryType' => $sectionsByEntryType,
        ]);
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

        return $this->asCpScreen()
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
        $entryType->hasTitleField = (bool)$this->request->getBodyParam('hasTitleField', $entryType->hasTitleField);
        $entryType->titleTranslationMethod = $this->request->getBodyParam('titleTranslationMethod', $entryType->titleTranslationMethod);
        $entryType->titleTranslationKeyFormat = $this->request->getBodyParam('titleTranslationKeyFormat', $entryType->titleTranslationKeyFormat);
        $entryType->titleFormat = $this->request->getBodyParam('titleFormat', $entryType->titleFormat);

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
        $this->requireAcceptsJson();

        $entryTypeId = $this->request->getRequiredBodyParam('id');

        $success = Craft::$app->getEntries()->deleteEntryTypeById($entryTypeId);
        return $success ? $this->asSuccess() : $this->asFailure();
    }
}
