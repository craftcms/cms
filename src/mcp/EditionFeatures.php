<?php

declare(strict_types=1);

namespace craft\mcp;

use Craft;
use craft\enums\CmsEdition;
use craft\services\Users;

/**
 * Craft features that need a higher edition, so MCP clients can say so rather than treat them as unknown.
 *
 * @internal
 * @since 5.12.0
 */
readonly class EditionFeatures
{
    /** @var list<array{feature: string, edition: CmsEdition, tools: string}> */
    private const Features = [
        ['feature' => 'User group permissions', 'edition' => CmsEdition::Team, 'tools' => 'user-permissions.group.set'],
        ['feature' => 'Custom user groups', 'edition' => CmsEdition::Pro, 'tools' => 'configuration.create with type: user-groups; Craft Team only has its Team group'],
        ['feature' => 'Individual user permissions', 'edition' => CmsEdition::Pro, 'tools' => 'user-permissions.user.set'],
    ];

    public function __construct(private Users $users)
    {
    }

    /** @return list<array{feature: string, requiredEdition: string, tools: string}> */
    public function unavailable(): array
    {
        $edition = Craft::$app->edition;
        $features = array_filter(self::Features, static fn(array $feature): bool => $edition->value < $feature['edition']->value);
        $features = array_values(array_map(static fn(array $feature): array => [
            'feature' => $feature['feature'],
            'requiredEdition' => self::name($feature['edition']),
            'tools' => $feature['tools'],
        ], $features));

        $maxUsers = $this->users->getMaxUsers($edition);
        $upgrade = $this->userLimitUpgrade();

        if ($maxUsers !== null && $upgrade !== null) {
            $features[] = [
                'feature' => "More than $maxUsers " . ($maxUsers === 1 ? 'user' : 'users'),
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

        $edition = Craft::$app->edition;
        $maxUsers = (int) $this->users->getMaxUsers($edition);
        $reason = sprintf('This site has reached the %s limit of %d %s.', self::name($edition), $maxUsers, $maxUsers === 1 ? 'user' : 'users');
        $upgrade = $this->userLimitUpgrade();

        return $upgrade !== null ? "$reason Upgrading to " . self::name($upgrade) . ' would allow more users.' : $reason;
    }

    /**
     * Returns the edition’s display name, such as “Craft Pro”.
     */
    public static function name(CmsEdition $edition): string
    {
        return "Craft $edition->name";
    }

    /**
     * Returns the lowest edition that allows more users than the current one.
     */
    private function userLimitUpgrade(): ?CmsEdition
    {
        $edition = Craft::$app->edition;
        $maxUsers = $this->users->getMaxUsers($edition);

        foreach (CmsEdition::cases() as $case) {
            if ($case->value <= $edition->value) {
                continue;
            }

            $caseMax = $this->users->getMaxUsers($case);

            if ($caseMax === null || ($maxUsers !== null && $caseMax > $maxUsers)) {
                return $case;
            }
        }

        return null;
    }
}
