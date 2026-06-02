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
use yii\web\ForbiddenHttpException;
use yii\web\Response;

/**
 * Nested elements controller.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
class NestedElementsController extends Controller
{
    private ElementInterface $owner;
    private ElementQueryInterface|ElementCollection $nestedElements;

    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        $this->requireCpRequest();

        // Get the owner element
        /** @var class-string<ElementInterface> $ownerElementType */
        $ownerElementType = $this->request->getRequiredBodyParam('ownerElementType');
        $ownerId = $this->request->getRequiredBodyParam('ownerId');
        $ownerSiteId = $this->request->getRequiredBodyParam('ownerSiteId');
        $owner = Craft::$app->getElements()->getElementById($ownerId, $ownerElementType, $ownerSiteId);
        if (!$owner) {
            throw new BadRequestHttpException('Invalid owner params');
        }
        $this->owner = $owner;

        // Make sure they're authorized to manage it
        $session = Craft::$app->getSession();
        $attribute = $this->request->getRequiredBodyParam('attribute');
        if (
            !$session->checkAuthorization(sprintf('manageNestedElements::%s::%s', $owner->id, $attribute)) &&
            (
                $owner->id === $owner->getCanonicalId() ||
                !$session->checkAuthorization(sprintf('manageNestedElements::%s::%s', $owner->getCanonicalId(), $attribute))
            )
        ) {
            throw new ForbiddenHttpException('User is not authorized to perform this action');
        }

        // Set the nested elements for the action
        $this->nestedElements = $this->owner->$attribute;

        return true;
    }

    /**
     * Moves the given elements to a new starting offset
     *
     * @return Response
     */
    public function actionReorder(): Response
    {
        $ids = array_map(fn($id) => (int)$id, $this->request->getRequiredBodyParam('elementIds'));
        $offset = $this->request->getRequiredBodyParam('offset');

        if ($this->nestedElements instanceof ElementQueryInterface) {
            $oldSortOrders = (clone $this->nestedElements)
                ->status(null)
                ->asArray()
                ->select(['id', 'sortOrder'])
                ->pairs();
        } else {
            $oldSortOrders = $this->nestedElements
                ->keyBy(fn(ElementInterface $element) => $element->id)
                /** @phpstan-ignore-next-line */
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
                    'ownerId' => $this->owner->id,
                    'elementId' => $id,
                ]);
            }
        }

        Craft::$app->getElements()->invalidateCachesForElement($this->owner);

        return $this->asSuccess(Craft::t('app', 'New {total, plural, =1{position} other{positions}} saved.', [
            'total' => count($ids),
        ]));
    }

    /**
     * Deletes a given element.
     *
     * @return Response
     */
    public function actionDelete(): Response
    {
        $elementId = (int)$this->request->getRequiredBodyParam('elementId');

        if ($this->nestedElements instanceof ElementQueryInterface) {
            $element = $this->nestedElements
                ->id($elementId)
                ->status(null)
                ->drafts(null)
                ->provisionalDrafts(null)
                ->one();
        } else {
            $element = $this->nestedElements->first(
                fn(ElementInterface $element) => (
                    $element->id === $elementId ||
                    $element->getCanonicalId() === $elementId
                )
            );
        }

        if (!$element) {
            throw new BadRequestHttpException('Invalid elementId param');
        }

        $elementsService = Craft::$app->getElements();

        if (!$elementsService->canDelete($element)) {
            throw new ForbiddenHttpException('User not authorized to delete this element.');
        }

        // If the element primarily belongs to a different element, just delete the ownership
        if ($element->getPrimaryOwnerId() !== $this->owner->id) {
            Db::delete(Table::ELEMENTS_OWNERS, [
                'elementId' => $element->id,
                'ownerId' => $this->owner->id,
            ]);
            $success = true;
        } else {
            $success = $elementsService->deleteElement($element);
        }

        if (!$success) {
            return $this->asFailure(Craft::t('app', 'Couldn’t delete {type}.', [
                'type' => $element::lowerDisplayName(),
            ]));
        }

        return $this->asSuccess(Craft::t('app', '{type} deleted.', [
            'type' => $element::displayName(),
        ]));
    }
}
