<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Contracts\CraftUser;
use CraftCms\Cms\User\Users;
use InvalidArgumentException;

/**
 * The sources a user keeps to themselves, saved in their preferences rather
 * than project config. They're listed after the shared sources on the user's
 * own index and nav, and nowhere else.
 *
 * @since 6.0.0
 */
class PersonalElementSources
{
    public const string PREFERENCE = 'elementSources';

    public const string KEY_PREFIX = 'user:';

    public function __construct(
        private readonly Users $users,
    ) {}

    public static function isPersonalKey(string $key): bool
    {
        return str_starts_with($key, self::KEY_PREFIX);
    }

    /**
     * @param  class-string<ElementInterface>  $elementType
     * @return list<array<string, mixed>>
     */
    public function all(CraftUser $user, string $elementType): array
    {
        $userId = $user->getCraftUserId();

        if (! $userId) {
            return [];
        }

        $sources = $this->users->getUserPreference($userId, self::PREFERENCE, [])[$elementType] ?? [];

        return is_array($sources) ? array_values(array_filter($sources, is_array(...))) : [];
    }

    /**
     * @param  class-string<ElementInterface>  $elementType
     * @return array<string, mixed>|null
     */
    public function find(CraftUser $user, string $elementType, string $key): ?array
    {
        return array_find($this->all($user, $elementType), fn (array $source) => ($source['key'] ?? null) === $key);
    }

    /**
     * Adds a source, after the user's others.
     *
     * @param  class-string<ElementInterface>  $elementType
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed> The new source
     */
    public function add(CraftUser $user, string $elementType, array $settings): array
    {
        $source = [
            ...$settings,
            'type' => ElementSources::TYPE_CUSTOM,
            'key' => self::KEY_PREFIX.Str::uuid()->toString(),
        ];

        $this->save($user, $elementType, [...$this->all($user, $elementType), $source]);

        return $source;
    }

    /**
     * @param  class-string<ElementInterface>  $elementType
     * @param  array<string, mixed>  $settings
     */
    public function update(CraftUser $user, string $elementType, string $key, array $settings): void
    {
        $sources = $this->all($user, $elementType);
        $index = array_find_key($sources, fn (array $source) => ($source['key'] ?? null) === $key);

        if ($index === null) {
            throw new InvalidArgumentException("No personal source has the key “{$key}”.");
        }

        $sources[$index] = [...$sources[$index], ...$settings];
        $this->save($user, $elementType, $sources);
    }

    /**
     * @param  class-string<ElementInterface>  $elementType
     */
    public function delete(CraftUser $user, string $elementType, string $key): void
    {
        $this->save($user, $elementType, array_values(array_filter(
            $this->all($user, $elementType),
            fn (array $source) => ($source['key'] ?? null) !== $key,
        )));
    }

    /**
     * @param  class-string<ElementInterface>  $elementType
     * @param  list<array<string, mixed>>  $sources
     */
    private function save(CraftUser $user, string $elementType, array $sources): void
    {
        $all = $this->users->getUserPreference((int) $user->getCraftUserId(), self::PREFERENCE, []);
        $all = is_array($all) ? $all : [];

        if ($sources === []) {
            unset($all[$elementType]);
        } else {
            $all[$elementType] = $sources;
        }

        $this->users->saveUserPreferences($user, [self::PREFERENCE => $all]);
        app(ElementSources::class)->forgetSources($elementType);
    }
}
