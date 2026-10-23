<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Cms\Element\Eloquent\EloquentElement;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\currentUser;

/**
 * Lets through only users who may see the index of the {@see EloquentElement} type named by the route's `elementType` default.
 *
 * @since 6.0.0
 */
class AuthorizeEloquentElementIndex
{
    public function handle(Request $request, Closure $next): Response
    {
        $elementType = $request->route('elementType');
        $user = currentUser();

        abort_unless(
            $user !== null
            && is_string($elementType)
            && is_subclass_of($elementType, EloquentElement::class)
            && $elementType::canViewIndex($user),
            403,
        );

        return $next($request);
    }
}
