<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Jobs;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\Field\Contracts\TracksReferencesFieldInterface;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Queue\BatchedElementJob;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\I18N;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Collection;
use Override;
use Throwable;

class ReplaceReferences extends BatchedElementJob
{
    public function __construct(
        /** @var class-string<ElementInterface> */
        public string $sourceElementType,

        /** @var class-string<ElementInterface> */
        public string $targetElementType,

        public ?int $sourceSiteId,

        /** @var array<int, array{fieldInstanceUid: string, sourceId: int}> */
        public array $refs,

        /** @var int[] */
        public array $oldTargetIds,

        public int $newTargetId,
    ) {
        parent::__construct();
    }

    #[Override]
    protected function getQuery(): Builder
    {
        $query = $this->sourceElementType::find()
            ->id(Collection::make($this->refs)->pluck('sourceId')->unique()->values()->all())
            ->drafts(null)
            ->provisionalDrafts(null)
            ->revisions(false)
            ->trashed(false)
            ->status(null)
            ->orderBy('elements.id');

        if ($this->sourceSiteId !== null) {
            $query->siteId($this->sourceSiteId);
        } else {
            $query->siteId('*')->unique();
        }

        return $query;
    }

    protected function processElement(ElementInterface $element): void
    {
        $fieldInstanceUids = Collection::make($this->refs)
            ->filter(fn (array $ref) => $ref['sourceId'] === $element->id)
            ->pluck('fieldInstanceUid')
            ->unique();

        if ($fieldInstanceUids->isEmpty()) {
            return;
        }

        $saveElement = false;

        foreach ($fieldInstanceUids as $fieldInstanceUid) {
            $layoutElement = $element->getFieldLayout()?->getElementByUid($fieldInstanceUid);

            if (! $layoutElement instanceof CustomField) {
                continue;
            }

            $field = $layoutElement->getField();

            if (! $field instanceof TracksReferencesFieldInterface) {
                continue;
            }

            if ($field->replaceReferences($element, $this->oldTargetIds, $this->newTargetId)) {
                $saveElement = true;
            }
        }

        if (! $saveElement) {
            return;
        }

        $element->ruleset->useScenario(ElementRules::SCENARIO_ESSENTIALS);
        $element->resaving = true;

        try {
            Elements::saveElement(element: $element, runValidation: false, propagate: false);
        } catch (Throwable $e) {
            report($e);
        }
    }

    #[Override]
    protected function defaultDescription(): string
    {
        return I18N::prep('Replacing {type} references', [
            'type' => $this->targetElementType::lowerDisplayName(),
        ]);
    }
}
