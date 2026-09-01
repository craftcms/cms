<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\RouteToken\Data\RouteToken;
use Illuminate\Support\Facades\Context;

readonly class PreviewRequestPreparer
{
    public function __construct(
        private Elements $elements,
    ) {}

    /** @param array<string, mixed> $data */
    public function prepare(array $data): void
    {
        $tokenData = new RouteToken($data);
        $tokenData->validate(throw: true);

        $query = $tokenData->elementType::find()
            ->siteId($tokenData->siteId)
            ->status(null);

        $element = match (true) {
            ! is_null($tokenData->draftId) => $query->draftId($tokenData->draftId)->one(),
            ! is_null($tokenData->revisionId) => $query->revisionId($tokenData->revisionId)->one(),
            ! is_null($tokenData->userId) => $this->userPreviewElement($query, $tokenData),
            default => null,
        };

        if (! $element) {
            return;
        }

        if (! $element->lft && $element->getIsDerivative()) {
            $canonical = $element->getCanonical(true);
            $element->structureId = $canonical->structureId;
            $element->root = $canonical->root;
            $element->lft = $canonical->lft;
            $element->rgt = $canonical->rgt;
            $element->level = $canonical->level;
        }

        $element->previewing = true;
        $this->elements->setPlaceholderElement($element);
    }

    private function userPreviewElement(ElementQueryInterface $query, RouteToken $tokenData): ?ElementInterface
    {
        Context::addHidden(Drafts::CONTEXT_PREVIEW_USER_ID, $tokenData->userId);

        return (clone $query)
            ->draftOf($tokenData->canonicalId)
            ->provisionalDrafts()
            ->draftCreator($tokenData->userId)
            ->one() ?? $query->id($tokenData->canonicalId)->one();
    }
}
