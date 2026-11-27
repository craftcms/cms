<?php

declare(strict_types=1);

namespace CraftCms\Cms\DeletionBlocker;

use Closure;
use CraftCms\Cms\Component\Contracts\Chippable;
use CraftCms\Cms\Route\Data\Route;
use CraftCms\Cms\Route\Routes;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Gql;
use CraftCms\Cms\Support\Facades\ImageTransforms;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\SiteGroups;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Facades\UserGroups;
use CraftCms\Cms\Support\Facades\Volumes;
use Illuminate\Container\Attributes\Singleton;
use InvalidArgumentException;

/**
 * Registers the things other than elements that the control panel shows
 * deletion blockers for, such as fields and volumes, keyed by type.
 *
 * Plugins can register their own types from a service provider, and add
 * blockers for them with {@see BlockerRegistry}:
 *
 * ```php
 * public function boot(SubjectTypeRegistry $subjectTypes): void
 * {
 *     $subjectTypes->register(
 *         'my-plugin-thing',
 *         find: fn (int|string $id) => MyPlugin::getInstance()->things->getThingById((int) $id),
 *     );
 * }
 * ```
 *
 * @since 6.0.0
 */
#[Singleton]
class SubjectTypeRegistry
{
    /**
     * @var array<string, array{find: Closure(int|string): mixed, label: (Closure(mixed): string)|null, requireAdmin: bool}>
     */
    private array $types = [];

    public function __construct()
    {
        $this->register('entry-type', fn ($id) => EntryTypes::getEntryTypeById((int) $id));
        $this->register('field', fn ($id) => Fields::getFieldById((int) $id));
        $this->register('graphql-schema', fn ($id) => Gql::getSchemaById((int) $id));
        $this->register('graphql-token', fn ($id) => Gql::getTokenById((int) $id));
        $this->register('image-transform', fn ($id) => ImageTransforms::getTransformById((int) $id));
        $this->register(
            'route',
            fn ($uid) => app(Routes::class)->getAllProjectConfigRoutes()->firstWhere('uid', (string) $uid),
            fn (Route $route) => $route->getUri(),
        );
        $this->register('section', fn ($id) => Sections::getSectionById((int) $id));
        $this->register('site', fn ($id) => Sites::getSiteById((int) $id, true));
        $this->register('site-group', fn ($id) => SiteGroups::getGroupById((int) $id));
        $this->register('user-group', fn ($id) => UserGroups::getGroupById((int) $id));
        $this->register('volume', fn ($id) => Volumes::getVolumeById((int) $id));
    }

    /**
     * @param  string  $type  A key for the type, such as `volume`
     * @param  Closure(int|string): mixed  $find  Returns the subject for an ID (or handle or UID), or `null` if it
     *                                            doesn’t exist or the current user can’t delete it
     * @param  (Closure(mixed): string)|null  $label  Returns the subject’s name. Defaults to its UI label or `name`.
     * @param  bool  $requireAdmin  Whether only admins can view its blockers
     */
    public function register(string $type, Closure $find, ?Closure $label = null, bool $requireAdmin = true): void
    {
        $this->types[$type] = [
            'find' => $find,
            'label' => $label,
            'requireAdmin' => $requireAdmin,
        ];
    }

    public function has(string $type): bool
    {
        return isset($this->types[$type]);
    }

    /**
     * Returns the subject to be deleted, or `null` if it doesn’t exist.
     *
     * @throws InvalidArgumentException if the type isn’t registered
     */
    public function find(string $type, int|string $id): mixed
    {
        return $this->type($type)['find']($id);
    }

    /**
     * Returns the name of the subject to be deleted.
     *
     * @throws InvalidArgumentException if the type isn’t registered
     */
    public function label(string $type, mixed $subject): string
    {
        $label = $this->type($type)['label'];

        return match (true) {
            $label !== null => $label($subject),
            $subject instanceof Chippable => $subject->getUiLabel(),
            is_object($subject) && isset($subject->name) => (string) $subject->name,
            default => '',
        };
    }

    /**
     * Returns whether only admins can view the blockers for a subject type.
     *
     * @throws InvalidArgumentException if the type isn’t registered
     */
    public function requiresAdmin(string $type): bool
    {
        return $this->type($type)['requireAdmin'];
    }

    /**
     * @return array{find: Closure(int|string): mixed, label: (Closure(mixed): string)|null, requireAdmin: bool}
     */
    private function type(string $type): array
    {
        return $this->types[$type] ?? throw new InvalidArgumentException("Invalid deletion subject type: $type");
    }
}
