<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\View\HtmlFragment;
use Illuminate\Support\Facades\Gate;

/**
 * @phpstan-type NestedElementCardData array{
 *     id: int,
 *     siteId: int|null,
 *     cardAttributes: array<string, mixed>,
 *     cardLabelHtml: string,
 *     cardActionsHtml: string,
 *     cardContentHtml: string,
 *     cardThumbHtml: string,
 *     thumbAlignment: 'start'|'end',
 * }
 * @phpstan-type NestedElementCardsData array{
 *     mode: 'cards',
 *     elementType: string,
 *     ownerElementType: string,
 *     ownerId: int,
 *     ownerSiteId: int|null,
 *     attribute: string,
 *     sortable: bool,
 *     canCreate: bool,
 *     canPaste: bool,
 *     pasteableData: array{attribute: string, values: list<int|string>}|null,
 *     minElements: int|null,
 *     maxElements: int|null,
 *     createButtonLabel: string,
 *     ownerIdParam: string,
 *     fieldId: int|null,
 *     fieldHandle: string|null,
 *     baseInputName: string|null,
 *     prevalidate: bool,
 *     createAttributes?: mixed,
 *     deleteLabel: string,
 *     deleteConfirmationMessage: string,
 *     bulkDeleteConfirmationMessage: string,
 *     showInGrid: bool,
 *     selectable: bool,
 *     elements: array<int, NestedElementCardData>,
 * }
 * @phpstan-type NestedElementIndexData array{
 *     mode: 'index',
 *     elementType: string,
 *     ownerElementType: string,
 *     ownerId: int,
 *     ownerSiteId: int|null,
 *     attribute: string,
 *     sortable: bool,
 *     canCreate: bool,
 *     canPaste: bool,
 *     pasteableData: array{attribute: string, values: list<int|string>}|null,
 *     minElements: int|null,
 *     maxElements: int|null,
 *     createButtonLabel: string,
 *     ownerIdParam: string,
 *     fieldId: int|null,
 *     fieldHandle: string|null,
 *     baseInputName: string|null,
 *     prevalidate: bool,
 *     createAttributes?: mixed,
 *     indexSettings: array{
 *         namespace: string|null,
 *         allowedViewModes: array<int, string>|null,
 *         showHeaderColumn: bool,
 *         criteria: array<string, mixed>,
 *         batchSize: int,
 *         actions: array<int, array<string, mixed>>,
 *         canHaveDrafts: bool,
 *         storageKey: string|null,
 *         static: bool,
 *     },
 * }
 */
class UserAddressesViewModel extends ViewModel
{
    /**
     * Above this many addresses, an element index replaces the card grid.
     */
    private const int CARD_LIMIT = 50;

    private ?int $total = null;

    public function __construct(
        private readonly User $user,
    ) {}

    public function userId(): int
    {
        return $this->user->id;
    }

    public function showIndex(): bool
    {
        return $this->totalAddresses() > self::CARD_LIMIT;
    }

    /**
     * The nested element manager payload for a Vue-rendered addresses UI:
     * cards data (with per-element card parts) up to the card limit, or
     * embedded element index data beyond it.
     *
     * @return array<string, mixed>
     */
    public function data(): array
    {
        $config = [
            'showInGrid' => true,
            'canCreate' => Gate::check('editUsers'),
        ];

        $data = $this->showIndex()
            ? $this->user->getAddressManager()->getIndexData($this->user, $config)
            : $this->user->getAddressManager()->getCardsData($this->user, $config);

        // The view model's user is always saved, so the managers never
        // return their unsaved-owner null.
        assert($data !== null);

        return $data;
    }

    public function contentFragment(): HtmlFragment
    {
        $config = [
            'showInGrid' => true,
            'canCreate' => Gate::check('editUsers'),
        ];

        return HtmlStack::capture(fn (): string => $this->showIndex()
            ? $this->user->getAddressManager()->getIndexHtml($this->user, $config)
            : $this->user->getAddressManager()->getCardsHtml($this->user, $config));
    }

    private function totalAddresses(): int
    {
        return $this->total ??= Address::find()->owner($this->user)->count();
    }
}
