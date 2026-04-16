<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\base\NestedElementInterface;
use craft\db\Table;
use craft\elements\db\NestedElementQueryInterface;
use craft\helpers\Component;
use craft\helpers\Cp;
use craft\helpers\Db;
use craft\web\Controller;
use yii\web\BadRequestHttpException;
use yii\web\Response;

/**
 * Delete Elements controller.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.10.0
 */
class DeleteElementsController extends Controller
{
    /**
     * @var class-string<ElementInterface>
     */
    private string $elementType;
    /**
     * @var ElementInterface[]
     */
    private array $elements;
    private bool $hardDelete;

    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        $this->requireCpRequest();
        $this->requirePostRequest();

        $this->elementType = $this->request->getRequiredBodyParam('elementType');
        $this->hardDelete = $this->request->getBodyParam('hardDelete') ?? false;

        if (!Component::validateComponentClass($this->elementType, ElementInterface::class)) {
            throw new BadRequestHttpException("Invalid element type: $this->elementType");
        }

        $this->elements = $this->elements();

        return true;
    }

    private function elements(): array
    {
        $elementIds = array_map(fn($id) => (int)$id, $this->request->getRequiredBodyParam('elementIds'));
        $siteId = $this->request->getBodyParam('siteId');

        $query = $this->elementType::find()
            ->id($elementIds)
            ->siteId($siteId ?? '*')
            ->unique()
            ->status(null)
            ->drafts(null)
            ->savedDraftsOnly(false);

        $withDescendants = !$this->hardDelete && $this->request->getBodyParam('withDescendants');
        if ($withDescendants) {
            $query
                ->with([
                    [
                        'descendants',
                        [
                            'orderBy' => ['structureelements.lft' => SORT_DESC],
                            'status' => null,
                        ],
                    ],
                ])
                ->orderBy(['structureelements.lft' => SORT_DESC]);
        }

        if ($query instanceof NestedElementQueryInterface) {
            $ownerId = $this->request->getBodyParam('ownerId');
            $query->ownerId($ownerId);
        }

        $elements = [];
        $elementIds = [];
        $user = static::currentUser();
        $elementsService = Craft::$app->getElements();

        foreach ($query->all() as $element) {
            if (
                isset($elementIds[$element->id]) ||
                !$elementsService->canView($element, $user) ||
                !$elementsService->canDelete($element, $user)
            ) {
                continue;
            }

            $elements[] = $element;
            $elementIds[$element->id] = true;

            if ($withDescendants) {
                foreach ($element->getDescendants()->all() as $descendant) {
                    if (
                        isset($elementIds[$descendant->id]) ||
                        !$elementsService->canView($descendant, $user) ||
                        !$elementsService->canDelete($descendant, $user)
                    ) {
                        continue;
                    }

                    $elements[] = $descendant;
                    $elementIds[$descendant->id] = true;
                }
            }
        }

        return $elements;
    }

    /**
     * Returns any issues that should block the posted elements from being deleted.
     */
    public function actionDeletionBlockers(): Response
    {
        $elements = $this->elements;

        if (is_subclass_of($this->elementType, NestedElementInterface::class)) {
            // filter out elements that primarily belong to a different element,
            // as they won't actually be getting deleted
            $elements = array_values(array_filter(
                $elements,
                /** @phpstan-ignore-next-line */
                fn(NestedElementInterface $element) => $this->elementOwnedByPrimaryOwner($element),
            ));
        }

        $blockers = $this->elementType::deletionBlockers($elements, $this->hardDelete);
        $elementPreview = Cp::elementPreviewHtml(
            elements: $this->elements,
            showStatus: false,
        );

        return $this->asJson([
            'blockers' => $blockers,
            'elementPreview' => $elementPreview,
            'headHtml' => $this->view->getHeadHtml(),
            'bodyHtml' => $this->view->getBodyHtml(),
        ]);
    }

    /**
     * Deletes the posted elements.
     */
    public function actionDelete(): Response
    {
        $deleteOwnership = [];
        $elementsService = Craft::$app->getElements();

        foreach ($this->elements as $element) {
            if (
                $element instanceof NestedElementInterface &&
                !$this->elementOwnedByPrimaryOwner($element)
            ) {
                $deleteOwnership[$element->getOwnerId()][] = $element->id;
                continue;
            }

            $elementsService->deleteElement($element, $this->hardDelete);
        }

        foreach ($deleteOwnership as $ownerId => $elementIds) {
            Db::delete(Table::ELEMENTS_OWNERS, [
                'elementId' => $elementIds,
                'ownerId' => $ownerId,
            ]);
        }

        return $this->asJson([]);
    }

    private function elementOwnedByPrimaryOwner(NestedElementInterface $element): bool
    {
        $ownerId = $element->getOwnerId();
        return !$ownerId || $element->getPrimaryOwnerId() === $ownerId;
    }
}
