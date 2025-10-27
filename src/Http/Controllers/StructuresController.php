<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers;

use craft\base\ElementInterface;
use CraftCms\Cms\Http\EnforcesPermissions;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Structure\Data\Structure;
use CraftCms\Cms\Structure\Structures;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class StructuresController
{
    use EnforcesPermissions;
    use RespondsWithFlash;

    private ?Structure $structure;

    private ?ElementInterface $element;

    public function __construct(
        private Request $request,
        private Structures $structures,
    ) {
        [
            'structureId' => $structureId,
            'elementId' => $elementId,
            'siteId' => $siteId,
        ] = $request->validate([
            'structureId' => ['required', 'integer'],
            'elementId' => ['required', 'integer'],
            'siteId' => ['required', 'integer'],
        ]);

        $this->requirePermission("editStructure:$structureId");

        abort_if(
            is_null($this->structure = $structures->getStructureById($structureId)),
            404,
            'Structure not found.'
        );

        $elementsService = \Craft::$app->getElements();

        abort_if(
            is_null($elementType = $elementsService->getElementTypeById($elementId)),
            404,
            'Element not found.'
        );

        $this->element = $elementType::find()
            ->drafts(null)
            ->provisionalDrafts(null)
            ->id($elementId)
            ->siteId($siteId)
            ->status(null)
            ->structureId($structureId)
            ->one();

        abort_if(! isset($this->element), 404, 'Element not found.');
    }

    public function getElementLevelDelta(): JsonResponse
    {
        return new JsonResponse([
            'delta' => $this->structures->getElementLevelDelta($this->structure->id, $this->element),
        ]);
    }

    public function moveElement(): Response
    {
        $parentElementId = $this->request->get('parentId');
        $prevElementId = $this->request->get('prevId');

        if ($prevElementId) {
            $prevElement = \Craft::$app->getElements()->getElementById($prevElementId, null, $this->element->siteId);
            $success = $this->structures->moveAfter($this->structure->id, $this->element, $prevElement);
        } elseif ($parentElementId) {
            $parentElement = \Craft::$app->getElements()->getElementById($parentElementId, null, $this->element->siteId);
            $success = $this->structures->prepend($this->structure->id, $this->element, $parentElement);
        } else {
            $success = $this->structures->prependToRoot($this->structure->id, $this->element);
        }

        if ($success) {
            return $this->asSuccess();
        }

        return $this->asFailure();
    }
}
