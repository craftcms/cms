<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Enums;

use CraftCms\Cms\Element\Contracts\ElementInterface;

use function CraftCms\Cms\renderObjectTemplate;
use function CraftCms\Cms\t;

enum TranslationMethod: string
{
    case None = 'none';
    case Site = 'site';
    case SiteGroup = 'siteGroup';
    case Language = 'language';
    case Custom = 'custom';

    public function description(): ?string
    {
        return match ($this) {
            self::Site => t('This field is translated for each site.'),
            self::SiteGroup => t('This field is translated for each site group.'),
            self::Language => t('This field is translated for each language.'),
            default => null,
        };
    }

    public function elementKey(ElementInterface $element, ?string $translationKeyFormat = null): string
    {
        return match ($this) {
            self::None => '1',
            self::Site => (string) $element->siteId,
            self::SiteGroup => (string) $element->getSite()->groupId,
            self::Language => $element->getSite()->getLanguage(),
            self::Custom => $translationKeyFormat === null
                ? (string) $element->siteId
                : renderObjectTemplate($translationKeyFormat, $element),
        };
    }
}
