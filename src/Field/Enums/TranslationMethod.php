<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Enums;

enum TranslationMethod: string
{
    case None = 'none';
    case Site = 'site';
    case SiteGroup = 'siteGroup';
    case Language = 'language';
    case Custom = 'custom';
}
