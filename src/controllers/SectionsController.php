<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\base\Element;
use craft\enums\PropagationMethod;
use craft\helpers\Cp;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use craft\web\assets\editsection\EditSectionAsset;
use craft\web\Controller;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * SectionsController handles various section-related tasks.
 *
 * Note that all actions in this controller require administrator access in order to execute.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class SectionsController extends Controller
{
    private bool $readOnly;

    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        $viewActions = ['index', 'edit-section', 'table-data'];
        if (in_array($action->id, $viewActions)) {
            // Some actions require admin but not allowAdminChanges
            $this->requireAdmin(false);
        } else {
            // All other actions require an admin & allowAdminChanges
            $this->requireAdmin();
        }

        $this->readOnly = !Craft::$app->getConfig()->getGeneral()->allowAdminChanges;

        return true;
    }

    /**
     * Sections index.
     *
     * @param array $variables
     * @return Response The rendering result
     */
    public function actionIndex(array $variables = []): Response
    {
        $variables['sections'] = Craft::$app->getEntries()->getAllSections();
        $variables['readOnly'] = $this->readOnly;

        return $this->renderTemplate('settings/sections/_index.twig', $variables);
    }

    /**
     * Edit a section.
     *
     * @param int|null $sectionId The section’s ID, if any.
     * @param Section|null $section The section being edited, if there were any validation errors.
     * @return Response
     * @throws NotFoundHttpException if the requested section cannot be found
     * @throws BadRequestHttpException if attempting to do something not allowed by the current Craft edition
     */
    public function actionEditSection(?int $sectionId = null, ?Section $section = null): Response
    {
        if ($sectionId === null && $this->readOnly) {
            throw new ForbiddenHttpException('Administrative changes are disallowed in this environment.');
        }

        $sectionsService = Craft::$app->getEntries();

        $variables = [
            'sectionId' => $sectionId,
            'brandNewSection' => false,
        ];

        if ($sectionId !== null) {
            if ($section === null) {
                $section = $sectionsService->getSectionById($sectionId);

                if (!$section) {
                    throw new NotFoundHttpException('Section not found');
                }
            }

            $title = trim($section->name) ?: Craft::t('app', 'Edit Section');
        } else {
            if ($section === null) {
                $section = new Section();
                $variables['brandNewSection'] = true;
            }

            $title = Craft::t('app', 'Create a new section');
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
        $variables['readOnly'] = $this->readOnly;

        $this->getView()->registerAssetBundle(EditSectionAsset::class);

        $response = $this->asCpScreen()
            ->editUrl($section->getCpEditUrl())
            ->title($title)
            ->addCrumb(Craft::t('app', 'Settings'), 'settings')
            ->addCrumb(Craft::t('app', 'Sections'), 'settings/sections')
            ->contentTemplate('settings/sections/_edit.twig', $variables);

        if (!$this->readOnly) {
            $response
                ->action('sections/save-section')
                ->redirectUrl('settings/sections')
                ->addAltAction(Craft::t('app', 'Save and continue editing'), [
                    'redirect' => 'settings/sections/{id}',
                    'shortcut' => true,
                    'retainScroll' => true,
                ]);
        } else {
            $response->noticeHtml(Cp::readOnlyNoticeHtml());
        }

        return $response;
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

        $sectionsService = Craft::$app->getEntries();
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
        $section->type = $this->request->getBodyParam('type') ?? Section::TYPE_CHANNEL;
        $section->enableVersioning = $this->request->getBodyParam('enableVersioning', true);
        $maxAuthors = $this->request->getBodyParam('maxAuthors');
        $section->maxAuthors = is_numeric($maxAuthors) ? (int)$maxAuthors : null;
        $section->propagationMethod = PropagationMethod::tryFrom($this->request->getBodyParam('propagationMethod') ?? '')
            ?? PropagationMethod::All;
        $section->previewTargets = $this->request->getBodyParam('previewTargets') ?: [];

        // Structure settings
        if ($section->type === Section::TYPE_STRUCTURE) {
            $section->maxLevels = $this->request->getBodyParam('maxLevels') ?: null;
            $section->defaultPlacement = $this->request->getBodyParam('defaultPlacement') ?? $section->defaultPlacement;
        }

        $section->setEntryTypes(array_filter($this->request->getBodyParam('entryTypes') ?: []));

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
            return $this->asModelFailure($section, Craft::t('app', 'Couldn’t save section.'), 'section');
        }

        return $this->asModelSuccess($section, Craft::t('app',  'Section saved.'), 'section');
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

        Craft::$app->getEntries()->deleteSectionById($sectionId);

        return $this->asSuccess();
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
            'type' => 'type',
            default => 'name',
        };
        $sortDir = match ($this->request->getParam('sort.0.direction')) {
            'desc' => SORT_DESC,
            default => SORT_ASC,
        };

        [$pagination, $tableData] = $entriesService->getSectionTableData($page, $limit, $searchTerm, $orderBy, $sortDir);

        return $this->asSuccess(data: [
            'pagination' => $pagination,
            'data' => $tableData,
        ]);
    }
}
