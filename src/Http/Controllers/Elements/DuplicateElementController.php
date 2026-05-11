<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Contracts\NestedElementInterface;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\Exceptions\InvalidElementException;
use CraftCms\Cms\Http\Requests\ElementRequest;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\ElementResponse;
use CraftCms\Cms\Support\Facades\BulkOps;
use CraftCms\Cms\Support\Json;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

readonly class DuplicateElementController
{
    use RespondsWithFlash;

    public function __construct(
        protected ElementRequest $request,
        private Elements $elements,
    ) {}

    public function duplicate(): Response
    {
        $element = $this->request->element();

        if (! $element || $element->getIsRevision()) {
            abort(400, 'No element was identified by the request.');
        }

        $isExplicitDraft = $element->getIsDraft() && ! $element->getIsUnpublishedDraft() && ! $element->isProvisionalDraft;

        // save as a new is now available to people who can create drafts
        $asUnpublishedDraft = $this->request->boolean('asUnpublishedDraft') && $element::hasDrafts();
        $asUnpublishedDraft || $isExplicitDraft
            ? Gate::authorize('duplicateAsDraft', $element)
            : Gate::authorize('duplicate', $element);

        $newAttributes = [
            'isProvisionalDraft' => false,
            'draftId' => $isExplicitDraft ? $element->draftId : null,
        ];

        if ($asUnpublishedDraft &&
            ($element->getIsCanonical() || $element->isProvisionalDraft) &&
            $element->slug === $element->getCanonical()->slug
        ) {
            $newAttributes += [
                'slug' => null,
            ];
        }

        if ($element instanceof NestedElementInterface) {
            $newAttributes += [
                'primaryOwnerId' => $element->getOwnerId(),
                'ownerId' => $element->getOwnerId(),
                'sortOrder' => null,
            ];
        }

        try {
            $newElement = $this->elements->duplicateElement(
                $element,
                $newAttributes,
                asUnpublishedDraft: $asUnpublishedDraft,
            );
        } catch (InvalidElementException $e) {
            return new ElementResponse()->failure($e->element, t('Couldn’t duplicate {type}.', [
                'type' => $element::lowerDisplayName(),
            ]));
        }

        // If the original element is a provisional draft,
        // delete the draft as the changes are likely no longer wanted.
        if ($this->request->boolean('deleteProvisionalDraft') && $element->isProvisionalDraft) {
            $this->elements->deleteElement($element);
        }

        return new ElementResponse()->success($newElement, t('{type} duplicated.', [
            'type' => $element::displayName(),
        ]));
    }

    public function bulkDuplicate(): Response
    {
        $this->request->validate([
            'elements' => ['required', 'array'],
            'newAttributes' => ['required', 'array'],

            // No funny business
            'newAttributes.id' => ['missing'],
            'newAttributes.uid' => ['missing'],
            'newAttributes.canonicalId' => ['missing'],
        ]);

        $elementInfo = $this->request->array('elements');
        $newAttributes = $this->request->array('newAttributes');

        $newElementInfo = [];

        $result = DB::transaction(function () use ($elementInfo, $newAttributes, &$newElementInfo) {
            return BulkOps::ensure(function () use ($elementInfo, $newAttributes, &$newElementInfo) {
                foreach ($elementInfo as $info) {
                    $element = $this->request->element($info);

                    if (! $element instanceof ElementInterface) {
                        Log::warning(sprintf('Unable to duplicate element: %s', Json::encode($info)), [__METHOD__]);

                        continue;
                    }

                    $safeNewAttributes = collect($newAttributes)
                        ->only($element->safeAttributes())
                        ->except(['id', 'uid', 'canonicalId', 'siteSettingsId'])
                        ->all();

                    // if element is a revision, we need to nullify some additional attributes
                    if ($element->getIsRevision()) {
                        $safeNewAttributes['revisionId'] = null;

                        if ($element->dateDeleted !== null) {
                            $safeNewAttributes['dateDeleted'] = null;
                            $safeNewAttributes['deletedWithOwner'] = null;
                            $safeNewAttributes['trashed'] = false;
                        }
                    }

                    try {
                        $newElement = $this->elements->duplicateElement(
                            $element,
                            $safeNewAttributes + $element::baseBulkDuplicateAttributes(),
                            false,
                            checkAuthorization: true,
                        );
                    } catch (InvalidElementException $e) {
                        return new ElementResponse()->failure($e->element, t('Couldn’t duplicate {type}.', [
                            'type' => $element::lowerDisplayName(),
                        ]));
                    }

                    $newElementInfo[] = $newElement->toArray($newElement->attributes());
                }

                return null;
            });
        });

        if ($result !== null) {
            return $result;
        }

        /** @var class-string<ElementInterface> $elementType */
        $elementType = $elementInfo[0]['type'];

        return $this->asSuccess(mb_ucfirst(t('{type} duplicated.', [
            'type' => count($elementInfo) === 1 ? $elementType::displayName() : $elementType::pluralDisplayName(),
        ])), [
            'newElements' => $newElementInfo,
        ]);
    }
}
