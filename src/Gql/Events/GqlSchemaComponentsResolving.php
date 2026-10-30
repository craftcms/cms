<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Events;

use CraftCms\Cms\Gql\Data\GqlSchema;
use CraftCms\Cms\User\Elements\User;

/**
 * @event GqlSchemaComponentsResolving The event that is triggered when registering GraphQL schema components.
 *
 * Components can support requests authenticated with an access token by setting an `authenticated` key on their config: `true` if
 * the component should be available to every authenticated user, or a closure that’s passed a
 * {@see User} and returns whether the component should be available to them. Components
 * that support authenticated requests are responsible for limiting their results to what
 * {@see GqlSchema::$user} may access.
 *
 * ```php
 * use CraftCms\Cms\Gql\Events\GqlSchemaComponentsResolving;
 * use CraftCms\Cms\User\Elements\User;
 * use Illuminate\Support\Facades\Event;
 *
 * Event::listen(function (GqlSchemaComponentsResolving $event) {
 *     $event->queries['My Plugin'] = [
 *         'widgets:read' => [
 *             'label' => 'Query for widgets',
 *             'authenticated' => fn (User $user) => $user->can('myPlugin:viewWidgets'),
 *         ],
 *     ];
 * });
 * ```
 *
 * @since 6.0.0
 */
class GqlSchemaComponentsResolving
{
    public function __construct(
        /** @var array<string, mixed> */
        public array $queries,
        /** @var array<string, mixed> */
        public array $mutations,
    ) {}
}
