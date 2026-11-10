<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\LooseEnds;

use function CraftCms\Cms\t;

/**
 * How badly a project would be affected if the adapter were removed while a Yii2 Loose Ends item is outstanding.
 *
 * @internal
 */
enum Severity: string
{
    case Breaks = 'breaks';
    case ChangesBehavior = 'changes-behavior';
    case Cosmetic = 'cosmetic';

    public function label(): string
    {
        return match ($this) {
            self::Breaks => t('Breaks without the adapter', category: 'yii2-adapter'),
            self::ChangesBehavior => t('Behaves differently without the adapter', category: 'yii2-adapter'),
            self::Cosmetic => t('Cosmetic', category: 'yii2-adapter'),
        };
    }

    /**
     * Returns the `craft-badge` fill for the severity.
     */
    public function fill(): string
    {
        return match ($this) {
            self::Breaks => 'red',
            self::ChangesBehavior => 'amber',
            self::Cosmetic => 'gray',
        };
    }
}
