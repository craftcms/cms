<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Jobs;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\Field\BaseRelationField;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Queue\BatchedElementJob;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\I18N;
use Illuminate\Contracts\Database\Query\Builder;
use Override;
use Throwable;

class ReplaceRelations extends BatchedElementJob
{
    public function __construct(
        /** @var class-string<ElementInterface> */
        public string $sourceElementType,

        /** @var class-string<ElementInterface> */
        public string $targetElementType,

        /** @var int[] */
        public array $sourceIds,

        /** @var int[] */
        public array $oldTargetIds,

        public int $newTargetId,
    ) {
        parent::__construct();
    }

    #[Override]
    protected function getQuery(): Builder
    {
        return $this->sourceElementType::find()
            ->id($this->sourceIds)
            ->siteId('*')
            ->orderBy('elements.id')
            ->orderBy('elements_sites.siteId');
    }

    protected function processElement(ElementInterface $element): void
    {
        $customFields = collect($element->getFieldLayout()?->getCustomFields());

        $relationFields = $customFields->filter(fn (FieldInterface $field): bool => (
            $field instanceof BaseRelationField &&
            $field::elementType() === $this->targetElementType
        ));

        if ($relationFields->isEmpty()) {
            return;
        }

        $saveElement = false;

        foreach ($relationFields as $field) {
            $this->processRelationField($element, $field, $saveElement);
        }

        if ($saveElement) {
            $element->ruleset->useScenario(ElementRules::SCENARIO_ESSENTIALS);
            $element->resaving = true;

            try {
                Elements::saveElement($element, false, false);
            } catch (Throwable $e) {
                report($e);
            }
        }
    }

    private function processRelationField(ElementInterface $element, BaseRelationField $field, bool &$saveElement): void
    {
        /** @var Element $element */
        $value = $element->getCustomFieldRawValue($field->handle);

        // avoid a DB query if we can
        if (! is_array($value)) {
            /** @var ElementQueryInterface $value */
            $value = $element->getFieldValue($field->handle);
            $value = $value
                ->site('*')
                ->unique()
                ->status(null)
                ->drafts(null)
                ->withProvisionalDrafts()
                ->revisions(null)
                ->trashed(null)
                ->ids();
        }

        $value = array_map(fn ($id) => (int) $id, array_values(array_filter($value)));

        $newValue = array_values(array_unique(
            array_map(fn ($id) => in_array($id, $this->oldTargetIds) ? $this->newTargetId : $id, $value)
        ));

        if ($value !== $newValue) {
            $element->setFieldValue($field->handle, $newValue);
            $saveElement = true;
        }
    }

    #[Override]
    protected function defaultDescription(): string
    {
        return I18N::prep('Replacing {type} relations', [
            'type' => $this->targetElementType::lowerDisplayName(),
        ]);
    }
}
