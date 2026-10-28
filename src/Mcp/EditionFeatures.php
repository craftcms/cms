<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp;

use CraftCms\Cms\Edition;
use CraftCms\Cms\User\Users;

/**
 * Craft features that need a higher edition, so MCP clients can say so rather than treat them as unknown.
 *
 * @since 6.0.0
 */
readonly class EditionFeatures
{
    public function __construct(private Users $users) {}

    /** @var list<array{feature: string, edition: Edition, tools: string}> */
    private const array Features = [
        ['feature' => 'Releases', 'edition' => Edition::Pro, 'tools' => 'releases.*'],
        ['feature' => 'Editorial workflows', 'edition' => Edition::Pro, 'tools' => 'configuration.* with type: workflows'],
        ['feature' => 'Custom user groups and user permissions', 'edition' => Edition::Pro, 'tools' => 'configuration.* with type: user-groups, user-permissions.*'],
    ];

    /** @return list<array{feature: string, requiredEdition: string, tools: string}> */
    public function unavailable(): array
    {
        $features = array_filter(self::Features, static fn (array $feature): bool => ! Edition::isAtLeast($feature['edition']));
        $features = array_values(array_map(static fn (array $feature): array => [
            'feature' => $feature['feature'],
            'requiredEdition' => self::name($feature['edition']),
            'tools' => $feature['tools'],
        ], $features));
        $maxUsers = $this->users->getMaxUsers(Edition::get());
        $upgrade = $this->userLimitUpgrade();

        if ($maxUsers !== null && $upgrade !== null) {
            $features[] = [
                'feature' => "More than $maxUsers ".($maxUsers === 1 ? 'user' : 'users'),
                'requiredEdition' => self::name($upgrade),
                'tools' => 'elements.create with type: users',
            ];
        }

        return $features;
    }

    /**
     * Returns why new users can’t be created, if the current edition’s user limit has been reached.
     */
    public function userLimitReason(): ?string
    {
        if ($this->users->canCreateUsers()) {
            return null;
        }

        $maxUsers = (int) $this->users->getMaxUsers(Edition::get());
        $reason = sprintf('This site has reached the %s limit of %d %s.', self::name(Edition::get()), $maxUsers, $maxUsers === 1 ? 'user' : 'users');
        $upgrade = $this->userLimitUpgrade();

        return $upgrade !== null ? "$reason Upgrading to ".self::name($upgrade).' would allow more users.' : $reason;
    }

    /** The lowest edition that allows more users than the current one. */
    private function userLimitUpgrade(): ?Edition
    {
        $maxUsers = $this->users->getMaxUsers(Edition::get());

        foreach (Edition::cases() as $edition) {
            if ($edition->value <= Edition::get()->value) {
                continue;
            }

            $editionMax = $this->users->getMaxUsers($edition);

            if ($editionMax === null || ($maxUsers !== null && $editionMax > $maxUsers)) {
                return $edition;
            }
        }

        return null;
    }

    /** The edition’s display name, such as “Craft Pro”. */
    public static function name(Edition $edition): string
    {
        return 'Craft '.ucfirst($edition->handle());
    }
}
