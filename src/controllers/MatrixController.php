<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\elements\db\EntryQuery;
use craft\elements\ElementCollection;
use craft\elements\Entry;
use craft\fields\Matrix;
use craft\helpers\StringHelper;
use craft\web\Controller;
use yii\web\BadRequestHttpException;
use yii\web\Response;

/**
 * Class MatrixController
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
class MatrixController extends Controller
{
    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        $this->requireCpRequest();
        return true;
    }

    /**
     * Renders an updated “Default Table Columns” input for the selected entry types.
     *
     * @return Response
     */
    public function actionDefaultTableColumnOptions(): Response
    {
        $entryTypeIds = $this->request->getRequiredBodyParam('entryTypeIds');
        $entryTypes = [];
        $entriesService = Craft::$app->getEntries();

        foreach ($entryTypeIds as $entryTypeId) {
            $entryType = $entriesService->getEntryTypeById($entryTypeId);
            if (!$entryType) {
                throw new BadRequestHttpException("Invalid entry type ID: $entryTypeId");
            }
            $entryTypes[] = $entryType;
        }

        return $this->asJson([
            'options' => Matrix::defaultTableColumnOptions($entryTypes),
        ]);
    }

    /**
     * Renders a new entry block.
     *
     * @return Response
     */
    public function actionRenderBlock(): Response
    {
        $fieldId = $this->request->getRequiredBodyParam('fieldId');
        $entryTypeId = $this->request->getRequiredBodyParam('entryTypeId');
        $ownerId = $this->request->getBodyParam('ownerId');
        $ownerElementType = $this->request->getBodyParam('ownerElementType');
        $siteId = $this->request->getRequiredBodyParam('siteId');
        $namespace = $this->request->getRequiredBodyParam('namespace');

        $field = Craft::$app->getFields()->getFieldById($fieldId);
        if (!$field instanceof Matrix) {
            throw new BadRequestHttpException("Invalid Matrix field ID: $fieldId");
        }

        $entryType = Craft::$app->getEntries()->getEntryTypeById($entryTypeId);
        if (!$entryType) {
            throw new BadRequestHttpException("Invalid entry type ID: $entryTypeId");
        }

        $site = Craft::$app->getSites()->getSiteById($siteId, true);
        if (!$site) {
            throw new BadRequestHttpException("Invalid site ID: $siteId");
        }

        if ($ownerId) {
            $owner = Craft::$app->getElements()->getElementById($ownerId, $ownerElementType, $siteId);
        } else {
            $owner = null;
        }

        $entry = Craft::createObject([
            'class' => Entry::class,
            'siteId' => $siteId,
            'uid' => StringHelper::UUID(),
            'typeId' => $entryType->id,
            'fieldId' => $fieldId,
            'owner' => $owner ?? null,
        ]);

        /** @var EntryQuery|ElementCollection|null $value */
        $value = $owner?->getFieldValue($field->handle);

        $view = $this->getView();
        $html = $view->namespaceInputs(fn() => $view->renderTemplate('_components/fieldtypes/Matrix/block.twig', [
            'name' => $field->handle,
            'entryTypes' => $field->getEntryTypesForField($value?->all() ?? [], $owner),
            'entry' => $entry,
        ]), $namespace);

        return $this->asJson([
            'blockHtml' => $html,
            'headHtml' => $view->getHeadHtml(),
            'bodyHtml' => $view->getBodyHtml(),
        ]);
    }
}
