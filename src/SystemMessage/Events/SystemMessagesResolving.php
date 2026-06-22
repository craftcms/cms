<?php

declare(strict_types=1);

namespace CraftCms\Cms\SystemMessage\Events;

use CraftCms\Cms\SystemMessage\Models\SystemMessage;
use Illuminate\Support\Collection;

/**
 * @event SystemMessagesResolving The event that is triggered when registering system messages.
 *
 * ```php
 * use Illuminate\Support\Facades\Event;
 * use CraftCms\Cms\SystemMessage\Data\SystemMessage;
 * use CraftCms\Cms\SystemMessage\Events\SystemMessagesResolving;
 *
 * Event::listen(
 *     SystemMessagesResolving::class,
 *     function(SystemMessagesResolving $event) {
 *         $event->messages->push(new SystemMessage(
 *             key: 'account_approved',
 *             heading: 'When a member’s account is approved',
 *             subject: 'Your account is approved!',
 *             body: "Hey {{user.friendlyName|e}},\n\nYour account with {{systemName}} has been approved by {{approver}}!",
 *         ));
 *     },
 * );
 * ```
 *
 * Once a system message is registered, it will be editable from the System Messages utility.
 *
 * System messages can be sent via [[\CraftCms\Cms\SystemMessage\SystemMessages]]:
 *
 * ```php
 * use CraftCms\Cms\SystemMessage\SystemMessages;
 * use Illuminate\Support\Facades\Mail;
 *
 * Mail::send(
 *     app(SystemMessages::class)->mailable('account_approved', $user, [
 *         'approver' => $approver->friendlyName,
 *     ])
 * );
 * ```
 */
class SystemMessagesResolving
{
    public function __construct(
        /** @var Collection<SystemMessage> */
        public Collection $messages,
    ) {}
}
