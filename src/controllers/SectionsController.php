<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\base\Element;
use craft\elements\Entry;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\models\EntryType;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use craft\web\assets\editsection\EditSectionAsset;
use craft\web\Controller;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * The SectionsController class is a controller that handles various section and entry type related tasks such as
 * displaying, saving, deleting and reordering them in the control panel.
 * Note that all actions in this controller require administrator access in order to execute.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class SectionsController extends Controller
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
     * Sections index.
     *
     * @param array $variables
     * @return Response The rendering result
     */
    public function actionIndex(array $variables = []): Response
    {
        $variables['sections'] = Craft::$app->getSections()->getAllSections();

        return $this->renderTemplate('settings/sections/_index.twig', $variables);
    }

    /**
     * Edit a section.
     *
     * @param int|null $sectionId The section’s id, if any.
     * @param Section|null $section The section being edited, if there were any validation errors.
     * @return Response
     * @throws NotFoundHttpException if the requested section cannot be found
     * @throws BadRequestHttpException if attempting to do something not allowed by the current Craft edition
     */
    public function actionEditSection(?int $sectionId = null, ?Section $section = null): Response
    {
        $variables = [
            'sectionId' => $sectionId,
            'brandNewSection' => false,
        ];

        if ($sectionId !== null) {
            if ($section === null) {
                $section = Craft::$app->getSections()->getSectionById($sectionId);

                if (!$section) {
                    throw new NotFoundHttpException('Section not found');
                }
            }

            $variables['title'] = trim($section->name) ?: Craft::t('app', 'Edit Section');
        } else {
            if ($section === null) {
                $section = new Section();
                $variables['brandNewSection'] = true;
            }

            $variables['title'] = Craft::t('app', 'Create a new section');
        }

        $typeOptions = [
            Section::TYPE_SINGLE => Craft::t('app', 'Single'),
            Section::TYPE_CHANNEL => Craft::t('app', 'Channel'),
            Section::TYPE_STRUCTURE => Craft::t('app', 'Structure'),
        ];

        if (!$section->type) {
            $section->type = Section::TYPE_CHANNEL;
        }

        $variables['section'] = $section;
        $variables['typeOptions'] = $typeOptions;

        $this->getView()->registerAssetBundle(EditSectionAsset::class);

        return $this->renderTemplate('settings/sections/_edit.twig', $variables);
    }

    /**
     * Saves a section.
     *
     * @return Response|null
     * @throws BadRequestHttpException if any invalid site IDs are specified in the request
     */
    public function actionSaveSection(): ?Response
    {
        $this->requirePostRequest();

        $sectionsService = Craft::$app->getSections();
        $sectionId = $this->request->getBodyParam('sectionId');
        if ($sectionId) {
            $section = $sectionsService->getSectionById($sectionId);
            if (!$section) {
                throw new BadRequestHttpException("Invalid section ID: $sectionId");
            }
        } else {
            $section = new Section();
        }

        // Main section settings
        $section->name = $this->request->getBodyParam('name');
        $section->handle = $this->request->getBodyParam('handle');
        $section->type = $this->request->getBodyParam('type');
        $section->enableVersioning = $this->request->getBodyParam('enableVersioning', true);
        $section->propagationMethod = $this->request->getBodyParam('propagationMethod', Section::PROPAGATION_METHOD_ALL);
        $section->previewTargets = $this->request->getBodyParam('previewTargets') ?: [];

        if ($section->type === Section::TYPE_STRUCTURE) {
            $section->maxLevels = $this->request->getBodyParam('maxLevels') ?: null;
            $section->defaultPlacement = $this->request->getBodyParam('defaultPlacement') ?? $section->defaultPlacement;
        }

        // Site-specific settings
        $allSiteSettings = [];

        foreach (Craft::$app->getSites()->getAllSites() as $site) {
            $postedSettings = $this->request->getBodyParam('sites.' . $site->handle);

            // Skip disabled sites if this is a multi-site install
            if (Craft::$app->getIsMultiSite() && empty($postedSettings['enabled'])) {
                continue;
            }

            $siteSettings = new Section_SiteSettings();
            $siteSettings->siteId = $site->id;

            if ($section->type === Section::TYPE_SINGLE) {
                $siteSettings->uriFormat = ($postedSettings['singleHomepage'] ?? false) ? Element::HOMEPAGE_URI : ($postedSettings['singleUri'] ?? null);
            } else {
                $siteSettings->uriFormat = $postedSettings['uriFormat'] ?? null;
                $siteSettings->enabledByDefault = (bool)$postedSettings['enabledByDefault'];
            }

            if ($siteSettings->hasUrls = (bool)$siteSettings->uriFormat) {
                $siteSettings->template = $postedSettings['template'] ?? null;
            }

            $allSiteSettings[$site->id] = $siteSettings;
        }

        $section->setSiteSettings($allSiteSettings);

        // Save it
        if (!$sectionsService->saveSection($section)) {
            $this->setFailFlash(Craft::t('app', 'Couldn’t save section.'));

            // Send the section back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'section' => $section,
            ]);

            return null;
        }

        $this->setSuccessFlash(Craft::t('app', 'Section saved.'));
        return $this->redirectToPostedUrl($section);
    }

    /**
     * Deletes a section.
     *
     * @return Response
     */
    public function actionDeleteSection(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $sectionId = $this->request->getRequiredBodyParam('id');

        Craft::$app->getSections()->deleteSectionById($sectionId);

        return $this->asSuccess();
    }

    // Entry Types

    /**
     * Entry types index
     *
     * @param int $sectionId The ID of the section whose entry types we’re listing
     * @return Response
     * @throws NotFoundHttpException if the requested section cannot be found
     */
    public function actionEntryTypesIndex(int $sectionId): Response
    {
        $section = Craft::$app->getSections()->getSectionById($sectionId);

        if ($section === null) {
            throw new NotFoundHttpException('Section not found');
        }

        $title = Craft::t('app', '{section} Entry Types',
            ['section' => Craft::t('site', $section->name)]);

        return $this->renderTemplate('settings/sections/_entrytypes/index.twig', [
            'sectionId' => $sectionId,
            'section' => $section,
            'title' => $title,
        ]);
    }

    /**
     * Edit an entry type
     *
     * @param int $sectionId The section’s ID.
     * @param int|null $entryTypeId The entry type’s ID, if any.
     * @param EntryType|null $entryType The entry type being edited, if there were any validation errors.
     * @return Response
     * @throws NotFoundHttpException if the requested section/entry type cannot be found
     * @throws BadRequestHttpException if the requested entry type does not belong to the requested section
     */
    public function actionEditEntryType(int $sectionId, ?int $entryTypeId = null, ?EntryType $entryType = null): Response
    {
        $section = Craft::$app->getSections()->getSectionById($sectionId);

        if (!$section) {
            throw new NotFoundHttpException('Section not found');
        }

        if ($entryTypeId !== null) {
            if ($entryType === null) {
                $entryType = Craft::$app->getSections()->getEntryTypeById($entryTypeId);

                if (!$entryType) {
                    throw new NotFoundHttpException('Entry type not found');
                }

                if ($entryType->sectionId != $section->id) {
                    throw new BadRequestHttpException('Entry type does not belong to the requested section');
                }
            }

            $title = trim($entryType->name) ?: Craft::t('app', 'Edit Entry Type');
        } else {
            if ($entryType === null) {
                $entryType = new EntryType();
                $entryType->sectionId = $section->id;
            }

            $title = Craft::t('app', 'Create a new {section} entry type',
                ['section' => Craft::t('site', $section->name)]);
        }

        $crumbs = [
            [
                'label' => Craft::t('app', 'Settings'),
                'url' => UrlHelper::url('settings'),
            ],
            [
                'label' => Craft::t('app', 'Sections'),
                'url' => UrlHelper::url('settings/sections'),
            ],
            [
                'label' => $section->name,
                'url' => UrlHelper::url('settings/sections/' . $section->id),
            ],
            [
                'label' => Craft::t('app', 'Entry Types'),
                'url' => UrlHelper::url('settings/sections/' . $sectionId . '/entrytypes'),
            ],
        ];

        return $this->renderTemplate('settings/sections/_entrytypes/edit.twig', [
            'sectionId' => $sectionId,
            'section' => $section,
            'entryTypeId' => $entryTypeId,
            'entryType' => $entryType,
            'title' => $title,
            'crumbs' => $crumbs,
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
    public function actionSaveEntryType(): ?Response
    {
        $this->requirePostRequest();

        $sectionsService = Craft::$app->getSections();
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
        $entryType->sectionId = $this->request->getRequiredBodyParam('sectionId');
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
            $this->setFailFlash(Craft::t('app', 'Couldn’t save entry type.'));

            // Send the entry type back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'entryType' => $entryType,
            ]);

            return null;
        }

        $this->setSuccessFlash(Craft::t('app', 'Entry type saved.'));
        return $this->redirectToPostedUrl($entryType);
    }

    /**
     * Reorders entry types.
     *
     * @return Response
     */
    public function actionReorderEntryTypes(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $entryTypeIds = Json::decode($this->request->getRequiredBodyParam('ids'));
        Craft::$app->getSections()->reorderEntryTypes($entryTypeIds);

        return $this->asSuccess();
    }

    /**
     * Deletes an entry type.
     *
     * @return Response
     */
    public function actionDeleteEntryType(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $entryTypeId = $this->request->getRequiredBodyParam('id');

        $success = Craft::$app->getSections()->deleteEntryTypeById($entryTypeId);
        return $success ? $this->asSuccess() : $this->asFailure();
    }
}
