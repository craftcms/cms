<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Entries;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Http\Controllers\Elements\ElementEditorController;
use CraftCms\Cms\Http\ViewModels\ElementEditViewModel;
use CraftCms\Cms\Http\ViewModels\EntryEditViewModel;
use Override;

/**
 * Renders the Inertia entry edit screen for the canonical entry, its drafts,
 * and its revisions.
 *
 * The legacy `EditElementController` still serves the jQuery slideouts and
 * the element types that haven't been ported.
 *
 * @extends ElementEditorController<Entry>
 *
 * @since 6.0.0
 */
class EditEntryController extends ElementEditorController
{
    #[Override]
    protected function elementType(): string
    {
        return Entry::class;
    }

    #[Override]
    protected function component(): string
    {
        return 'content/Edit';
    }

    #[Override]
    protected function viewModel(ElementInterface $element, bool $canSave, bool $mergedCanonicalChanges): ElementEditViewModel
    {
        return new EntryEditViewModel(
            entry: $element,
            request: $this->request,
            canSave: $canSave,
            mergedCanonicalChanges: $mergedCanonicalChanges,
        );
    }
}
