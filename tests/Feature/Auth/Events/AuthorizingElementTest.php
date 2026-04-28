<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\Feature\Auth\Events;

use CraftCms\Cms\Auth\Events\AuthorizingElement;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Tests\TestCase;
use CraftCms\Cms\User\Elements\User;

class AuthorizingElementTest extends TestCase
{
    public function test_event_can_be_created(): void
    {
        $user = User::findOne();
        $entry = new Entry;

        $event = new AuthorizingElement(
            user: $user,
            element: $entry,
            ability: 'save',
        );

        $this->assertSame($user, $event->user);
        $this->assertSame($entry, $event->element);
        $this->assertSame('save', $event->ability);
        $this->assertNull($event->authorized);
    }

    public function test_event_can_authorize(): void
    {
        $user = User::findOne();
        $entry = new Entry;

        $event = new AuthorizingElement(
            user: $user,
            element: $entry,
            ability: 'save',
        );

        $event->authorize();

        $this->assertTrue($event->authorized);
    }

    public function test_event_can_deny(): void
    {
        $user = User::findOne();
        $entry = new Entry;

        $event = new AuthorizingElement(
            user: $user,
            element: $entry,
            ability: 'save',
        );

        $event->deny();

        $this->assertFalse($event->authorized);
    }
}
