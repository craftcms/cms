<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Notifications;

use CraftCms\Cms\User\Contracts\CraftUser;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Auth;
use LogicException;
use Override;

class SendQueuedUserNotifications extends SendQueuedNotifications
{
    private bool $restoreUserElements = false;

    /**
     * @param  object  $notifiables  The recipient or recipient collection.
     * @param  Notification  $notification  The notification instance.
     * @param  list<string>|null  $channels  The delivery channels.
     */
    public function __construct(object $notifiables, Notification $notification, ?array $channels = null)
    {
        if ($notifiables instanceof User) {
            $this->restoreUserElements = true;
            $notifiables = Auth::getProvider()->retrieveById($notifiables->getAuthIdentifier());

            if (! $notifiables instanceof Model || ! $notifiables instanceof CraftUser) {
                throw new LogicException('The configured auth model must be an Eloquent model implementing CraftUser.');
            }
        }

        parent::__construct($notifiables, $notification, $channels);
    }

    #[Override]
    public function handle(ChannelManager $manager): void
    {
        if ($this->restoreUserElements) {
            $this->notifiables = $this->notifiables
                ->map(fn (CraftUser $user): User => $user->asElement());
        }

        parent::handle($manager);
    }
}
