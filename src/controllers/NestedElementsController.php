<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\base\ElementInterface;
use craft\base\NestedElementInterface;
use craft\db\Table;
use craft\elements\db\ElementQueryInterface;
use craft\elements\ElementCollection;
use craft\helpers\Db;
use craft\web\Controller;
use yii\web\BadRequestHttpException;
use yii\web\Response;

/**
 * Nested elements controller.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
class NestedElementsController extends Controller
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
     * Moves the given elements to a new starting offset
     *
     * @return Response
     */
    public function actionReorder(): Response
    {
        /** @var ElementInterface|string $ownerElementType */
        $ownerElementType = $this->request->getRequiredBodyParam('ownerElementType');
        $ownerId = $this->request->getRequiredBodyParam('ownerId');
        $ownerSiteId = $this->request->getRequiredBodyParam('ownerSiteId');
        $attribute = $this->request->getRequiredBodyParam('attribute');
        $ids = array_map(fn($id) => (int)$id, $this->request->getRequiredBodyParam('elementIds'));
        $offset = $this->request->getRequiredBodyParam('offset');

        $elementsService = Craft::$app->getElements();
        $owner = $elementsService->getElementById($ownerId, $ownerElementType, $ownerSiteId);

        if (!$owner) {
            throw new BadRequestHttpException('Invalid owner params');
        }

        $this->requireAuthorization("editNestedElements::$owner->id::$attribute");

        // Get the current sort orders, so we know what needs to change
        /** @var ElementQueryInterface|ElementCollection $nestedElements */
        $nestedElements = $owner->$attribute;

        if ($nestedElements instanceof ElementQueryInterface) {
            $oldSortOrders = (clone $nestedElements)
                ->asArray()
                ->select(['id', 'sortOrder'])
                ->pairs();
        } else {
            $oldSortOrders = $nestedElements
                ->keyBy(fn(NestedElementInterface $element) => $element->id)
                ->map(fn(NestedElementInterface $element) => $element->getSortOrder())
                ->all();
        }

        // Build the full list of IDs in the new sort order
        $allIds = array_diff(array_keys($oldSortOrders), $ids);
        array_splice($allIds, $offset, 0, $ids);

        // Update all the incorrect sort orders
        foreach ($allIds as $i => $id) {
            $sortOrder = $i + 1;
            if (!isset($oldSortOrders[$id]) || $sortOrder !== $oldSortOrders[$id]) {
                Db::update(Table::ELEMENTS_OWNERS, [
                    'sortOrder' => $sortOrder,
                ], [
                    'ownerId' => $owner->id,
                    'elementId' => $id,
                ]);
            }
        }

        $elementsService->invalidateCachesForElement($owner);

        return $this->asSuccess(Craft::t('app', 'New {total, plural, =1{position} other{positions}} saved.', [
            'total' => count($ids),
        ]));
    }
}
