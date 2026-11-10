<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Events;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cache-control policy at ApplyCacheControl's middleware checkpoint.
 *
 * Synchronous consumers may translate headers or store the response here. The
 * policy is a detached Symfony response, read-only by extension contract; clone
 * it before transforming it. It does not certify backend storage eligibility.
 * Incoming-cookie eligibility, arbitrary response cookies, and supported response
 * types are integration concerns. Outgoing session-cookie privacy and CSRF
 * storage restrictions remain part of Craft's policy.
 *
 * Middleware inside ApplyCacheControl has finished, but outer middleware and
 * later listeners can still change the response. Those changes are not reflected
 * in the policy snapshot and cannot undo a consumer's completed storage write.
 * Directives/defaults must be supplied before this checkpoint, not by a listener
 * expecting the request-scoped CacheControl service to be applied again.
 *
 * @since 6.0.0
 */
class CacheControlApplied
{
    /**
     * @param  list<string>  $tags  Original dependency tags, without transport encoding.
     * @param  list<array{code: string, target: string, message: string, source?: string}>  $reasons
     */
    public function __construct(
        public readonly Request $request,
        public readonly Response $response,
        public readonly Response $policy,
        public readonly array $tags,
        public readonly array $reasons,
    ) {}
}
