<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Gate;

/**
 * @since 6.0.0
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
     * Whether the current user can manage the addresses, which are saved with
     * their owner's permissions.
     */
    public function editable(): bool
    {
        return Gate::check('save', $this->user);
    }

    /**
     * The shared nested element manager's props for the addresses: a card grid
     * up to the card limit, or an embedded element index beyond it.
     *
     * @return array<string, mixed>
     */
    public function addresses(): array
    {
        $config = [
            'showInGrid' => true,
            'canCreate' => Gate::check('editUsers'),
        ];

        if (! $this->editable()) {
            $config['static'] = true;
        }

        return $this->user->getAddressManager()
            ->uiControl('addresses', $this->user, $this->showIndex() ? 'index' : 'cards-grid', $config)
            ->props();
    }

    private function totalAddresses(): int
    {
        return $this->total ??= Address::find()->owner($this->user)->count();
    }
}
