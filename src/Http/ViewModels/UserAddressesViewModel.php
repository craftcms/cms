<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\View\HtmlFragment;
use Illuminate\Support\Facades\Gate;

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
