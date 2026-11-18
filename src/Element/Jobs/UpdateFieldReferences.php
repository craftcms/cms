<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Jobs;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\Contracts\TracksReferencesFieldInterface;
use CraftCms\Cms\Field\FieldReferences;
use CraftCms\Cms\Queue\BatchedElementJob;
use CraftCms\Cms\Support\Facades\I18N;
use Illuminate\Contracts\Database\Query\Builder;
use Override;

/**
 * Rebuilds the tracked references of the reference-tracking fields of elements with the given field layouts,
 * for every site, without saving the elements.
 *
 * @since 6.0.0
 */
class UpdateFieldReferences extends BatchedElementJob
{
    public function __construct(
        /** @var class-string<ElementInterface> */
        protected string $elementType,

        /** @var int[] */
        public array $fieldLayoutIds,
    ) {
        parent::__construct();
    }

    #[Override]
    protected function getQuery(): Builder
    {
        return $this->elementType::find()
            ->whereIn('elements.fieldLayoutId', $this->fieldLayoutIds)
            ->siteId('*')
            ->drafts(null)
            ->provisionalDrafts(null)
            ->revisions(false)
            ->trashed(null)
            ->status(null)
            ->orderBy('elements.id')
            ->orderBy('elements_sites.siteId');
    }

    protected function processElement(ElementInterface $element): void
    {
        foreach ($element->getFieldLayout()?->getCustomFields() ?? [] as $field) {
            if ($field instanceof TracksReferencesFieldInterface) {
                app(FieldReferences::class)->updateReferences($field, $element);
            }
        }
    }

    #[Override]
    protected function defaultDescription(): string
    {
        return I18N::prep('Updating {type} references', [
            'type' => $this->elementType::lowerDisplayName(),
        ]);
    }
}
