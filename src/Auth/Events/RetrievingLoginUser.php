<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\Events;

use CraftCms\Cms\User\Elements\User;

/**
 * @event RetrievingLoginUser The event that is triggered before attempting to find a user to sign in
 *
 * ```php
 * use CraftCms\Cms\User\Elements\User;
 * use CraftCms\Cms\User\Events\RetrievingLoginUser;
 * use Illuminate\Support\Facades\Event;
 *
 * Event::listen(
 *     RetrievingLoginUser::class,
 *     function(RetrievingLoginUser $event) {
 *         // force username-based login
 *         $event->user = User::find()
 *             ->username($event->loginName)
 *             ->addSelect(['users.password', 'users.passwordResetRequired'])
 *             ->one();
 *     }
 * );
 * ```
 */
class RetrievingLoginUser
{
    public function __construct(
        public string $loginName,
        public ?User $user = null,
    ) {}
}
