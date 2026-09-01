<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Http\Requests\ElementIndexRequest;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Gate;
use Override;

use function CraftCms\Cms\t;

/**
 * The Inertia payload for the user index screen (`users/Index`).
 *
 * A `slug` path segment (`users/admins`, `users/{groupHandle}`) selects the
 * matching source — every user source publishes its slug in `data.slug`.
 */
class UserIndexViewModel extends ContentIndexViewModel
{
    public function __construct(
        ElementIndexRequest $request,
        private readonly ?string $slug = null,
    ) {
        parent::__construct(User::class, $request);
    }

    /**
     * The raw slug from the route, echoed back so client-side index reloads
     * (sort, filter, pagination) stay on the current source instead of
     * bouncing back to “all users”.
     */
    public function slug(): ?string
    {
        return $this->slug;
    }

    public function canRegisterUsers(): bool
    {
        return Gate::allows('save', new User);
    }

    /** The "New user" button label, matching the legacy index's wording. */
    public function newUserLabel(): string
    {
        return mb_ucfirst(t('New {type}', [
            'type' => User::lowerDisplayName(),
        ]));
    }

    /**
     * Maps the slug path segment onto its source key. User sources carry their
     * slug in `data.slug` — `all`, `admins`, `credentialed`, `inactive`, and
     * each user group's handle. An unrecognized slug falls through to the
     * default source rather than 404ing, mirroring the legacy index.
     */
    #[Override]
    protected function defaultSourceKey(): ?string
    {
        if ($this->slug === null || $this->slug === '') {
            return null;
        }

        foreach ($this->sources() as $source) {
            if (isset($source['key']) && ($source['data']['slug'] ?? null) === $this->slug) {
                return $source['key'];
            }
        }

        return null;
    }
}
