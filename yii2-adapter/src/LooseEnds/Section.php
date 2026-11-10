<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\LooseEnds;

use function CraftCms\Cms\t;

/**
 * A section of the Yii2 Loose Ends utility.
 *
 * @internal
 */
enum Section: string
{
    case Config = 'config';
    case Content = 'content';
    case Code = 'code';
    case Plugins = 'plugins';
    case Packages = 'packages';

    public function label(): string
    {
        return match ($this) {
            self::Config => t('Configuration and Environment', category: 'yii2-adapter'),
            self::Content => t('Content and Schema', category: 'yii2-adapter'),
            self::Code => t('Templates and Custom Code', category: 'yii2-adapter'),
            self::Plugins => t('Plugins', category: 'yii2-adapter'),
            self::Packages => t('Other Packages', category: 'yii2-adapter'),
        };
    }

    /**
     * Returns the sentence that introduces the section’s items.
     */
    public function intro(): string
    {
        return match ($this) {
            self::Config => t('The following configuration and environment issues require the Yii2 adapter:', category: 'yii2-adapter'),
            self::Content => t('The following content and schema issues require the Yii2 adapter:', category: 'yii2-adapter'),
            self::Code => t('The following templates and custom code require the Yii2 adapter:', category: 'yii2-adapter'),
            self::Plugins => t('The following plugins require the Yii2 adapter:', category: 'yii2-adapter'),
            self::Packages => t('The following packages require the Yii2 adapter:', category: 'yii2-adapter'),
        };
    }
}
