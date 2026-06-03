<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Jobs;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Elements as ElementsService;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\Field\BaseRelationField;
use CraftCms\Cms\Field\Link;
use CraftCms\Cms\Field\LinkTypes\BaseElementLinkType;
use CraftCms\Cms\Field\LinkTypes\BaseLinkType;
use CraftCms\Cms\Queue\BatchedElementJob;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\I18N;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Collection;
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

        /** @var Collection<BaseRelationField> $relationFields */
        $relationFields = $customFields->filter(fn ($field) => (
            $field instanceof BaseRelationField &&
            $field::elementType() === $this->targetElementType
        ));

        $targetRefHandle = $this->targetElementType::refHandle();
        /** @var Collection<Link> $linkFields */
        $linkFields = $customFields->filter(fn ($field) => (
            $field instanceof Link &&
            collect($field->getLinkTypes())->contains(fn (BaseLinkType $linkType) => (
                $linkType instanceof BaseElementLinkType &&
                $linkType::id() === $targetRefHandle
            ))
        ));

        if ($relationFields->isEmpty() && $linkFields->isEmpty()) {
            return;
        }

        $saveElement = false;

        foreach ($relationFields as $field) {
            $this->processRelationField($element, $field, $saveElement);
        }

        foreach ($linkFields as $field) {
            $this->processLinkField($element, $field, $saveElement);
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

    private function processLinkField(ElementInterface $element, Link $field, bool &$saveElement): void
    {
        /** @var Element $element */
        $value = $element->getCustomFieldRawValue($field->handle);

        if (empty($value['value']) || ! preg_match(ElementsService::REF_TAG_PATTERN, (string) $value['value'], $matches)) {
            return;
        }

        $elementType = $matches['elementType'];
        $ref = $matches['ref'];
        $siteId = $matches['site'] ?? null;
        $attribute = $matches['attr'] ?? null;

        if (! is_numeric($ref) || ! in_array((int) $ref, $this->oldTargetIds)) {
            return;
        }

        $element->setFieldValue($field->handle, [
            'type' => $value['type'],
            'value' => sprintf(
                '{%s:%s%s%s}',
                $elementType,
                $this->newTargetId,
                $siteId ? "@$siteId" : '',
                $attribute ? ":$attribute" : '',
            ),
        ]);
        $saveElement = true;
    }

    #[Override]
    protected function defaultDescription(): string
    {
        return I18N::prep('Replacing {type} relations', [
            'type' => $this->targetElementType::lowerDisplayName(),
        ]);
    }
}
