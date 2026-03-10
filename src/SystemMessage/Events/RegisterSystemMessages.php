<?php

declare(strict_types=1);

namespace CraftCms\Cms\SystemMessage\Events;

use Illuminate\Support\Collection;

/**
 * @event RegisterSystemMessages The event that is triggered when registering system messages.
 *
 * ```php
 * use Illuminate\Support\Facades\Event;
 * use CraftCms\Cms\SystemMessage\Data\SystemMessage;
 * use CraftCms\Cms\SystemMessage\Events\RegisterSystemMessages;
 *
 * Event::listen(
 *     RegisterSystemMessages::class,
 *     function(RegisterSystemMessages $event) {
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
final class RegisterSystemMessages
{
    public function __construct(
        /** @var Collection<\CraftCms\Cms\SystemMessage\Models\SystemMessage> */
        public Collection $messages,
    ) {}
}
