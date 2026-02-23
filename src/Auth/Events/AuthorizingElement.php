<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\Events;

use craft\base\ElementInterface;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Laravel event fired when authorizing an element action.
 *
 * Listeners can call authorize() or deny() to short-circuit the authorization check.
 * If neither is called, authorization continues through the normal policy chain.
 */
final class AuthorizingElement
{
    use Dispatchable;

    /**
     * The authorization result.
     * - null: no decision made, continue with policy
     * - true: authorized
     * - false: denied
     */
    public ?bool $authorized = null;

    public function __construct(
        public readonly User $user,
        public readonly ElementInterface $element,
        public readonly string $ability,
    ) {}

    /**
     * Authorize the action and stop further authorization checks.
     */
    public function authorize(): void
    {
        $this->authorized = true;
    }

    /**
     * Deny the action and stop further authorization checks.
     */
    public function deny(): void
    {
        $this->authorized = false;
    }
}
