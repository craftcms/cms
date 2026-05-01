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
use craft\elements\deletionblockers\DeletionBlockerInterface;
use craft\elements\ElementCollection;
use craft\helpers\App;
use craft\helpers\Component;
use craft\helpers\Cp;
use craft\helpers\Db;
use craft\helpers\Html;
use craft\helpers\Queue;
use craft\queue\jobs\ReplaceRelations;
use craft\web\Controller;
use Illuminate\Support\Collection;
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
    protected string $elementType;
    /**
     * @var ElementCollection
     */
    protected ElementCollection $elements;
    protected bool $hardDelete;

    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        $this->requireCpRequest();

        $this->elementType = $this->request->getRequiredParam('elementType');
        $this->hardDelete = App::normalizeBooleanValue($this->request->getParam('hardDelete')) ?? false;

        if (!Component::validateComponentClass($this->elementType, ElementInterface::class)) {
            throw new BadRequestHttpException("Invalid element type: $this->elementType");
        }

        $this->elements = $this->elements();

        return true;
    }

    private function elements(): ElementCollection
    {
        $elementIds = array_map(fn($id) => (int)$id, $this->request->getRequiredParam('elementIds'));
        $siteId = $this->request->getParam('siteId');

        $query = $this->elementType::find()
            ->id($elementIds)
            ->siteId($siteId ?? '*')
            ->unique()
            ->status(null)
            ->drafts(null)
            ->savedDraftsOnly(false)
            ->trashed($this->hardDelete);

        $withDescendants = !$this->hardDelete && $this->request->getParam('withDescendants');
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
            $ownerId = $this->request->getParam('ownerId');
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

        return ElementCollection::make($elements);
    }

    /**
     * Returns any issues that should block the posted elements from being deleted.
     */
    public function actionDeletionBlockers(): Response
    {
        $this->requirePostRequest();

        $elements = $this->elements;

        if (is_subclass_of($this->elementType, NestedElementInterface::class)) {
            // filter out elements that primarily belong to a different element,
            // as they won't actually be getting deleted
            /** @phpstan-ignore-next-line */
            $elements = $elements->filter(fn(NestedElementInterface $element) => $this->elementOwnedByPrimaryOwner($element));
        }

        $blockers = Collection::make($this->elementType::deletionBlockers($elements, $this->hardDelete))
            ->filter(fn(DeletionBlockerInterface $blocker) => $blocker->isActive())
            ->map(fn(DeletionBlockerInterface $blocker) => [
                'summary' => $blocker->getSummary(),
                'details' => $blocker->getDetails(),
                'actions' => $blocker->getActions(),
            ])
            ->values()
            ->all();

        $elementPreview = Cp::elementPreviewHtml(
            elements: $this->elements->all(),
            showStatus: false,
        );

        return $this->asJson([
            'blockers' => $blockers,
            'elementPreview' => $elementPreview,
            'totalElements' => $elements->count(),
            'headHtml' => $this->view->getHeadHtml(),
            'bodyHtml' => $this->view->getBodyHtml(),
        ]);
    }

    /**
     * Deletes the posted elements.
     */
    public function actionDelete(): Response
    {
        $this->requirePostRequest();

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

    public function actionReplaceRelationsModal(): Response
    {
        $this->requireAcceptsJson();

        /** @var class-string<ElementInterface> $sourceElementType */
        $sourceElementType = $this->request->getRequiredParam('sourceElementType');
        $targetElementIds = $this->elements->ids();

        return $this->asCpModal()
            ->action('delete-elements/replace-relations')
            ->contentHtml(fn() =>
                Cp::elementSelectFieldHtml([
                    'label' => Craft::t('app', 'Choose a new {type}', [
                        'type' => $this->elementType::lowerDisplayName(),
                    ]),
                    'name' => 'newTargetId',
                    'elementType' => $this->elementType,
                    'criteria' => [
                        'id' => $targetElementIds->map(fn(int $id) => "not $id")->all(),
                    ],
                    'single' => true,
                ]) .
                Html::hiddenInput('elementType', $this->elementType) .
                $targetElementIds->map(fn(int $id) => Html::hiddenInput('elementIds[]', (string)$id))->join('') .
                Html::hiddenInput('hardDelete', $this->hardDelete ? '1' : '0') .
                Html::hiddenInput('sourceElementType', $sourceElementType)
            )
            ->submitButtonLabel(Craft::t('app', 'Replace'));
    }

    public function actionReplaceRelations(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        /** @var class-string<ElementInterface> $sourceElementType */
        $sourceElementType = $this->request->getRequiredBodyParam('sourceElementType');
        $newTargetId = $this->request->getBodyParam('newTargetId');

        if (!$newTargetId) {
            return $this->asFailure(Craft::t('app', 'No new {type} selected.', [
                'type' => $this->elementType::lowerDisplayName(),
            ]));
        }

        $oldTargetIds = $this->elements->ids()->all();
        $sourceIds = $sourceElementType::find()
            ->siteId('*')
            ->unique()
            ->relatedTo(['targetElement' => $oldTargetIds])
            ->status(null)
            ->drafts(null)
            ->withProvisionalDrafts()
            ->revisions(null)
            ->ids();

        Queue::push(new ReplaceRelations([
            'sourceElementType' => $sourceElementType,
            'targetElementType' => $this->elementType,
            'sourceIds' => $sourceIds,
            'oldTargetIds' => $oldTargetIds,
            'newTargetId' => $newTargetId,
        ]));

        return $this->asSuccess(Craft::t('app', '{numRelations, plural, =1{Relation} other{Relations}} queued to be replaced.', [
            'numRelations' => count($sourceIds),
        ]));
    }
}
