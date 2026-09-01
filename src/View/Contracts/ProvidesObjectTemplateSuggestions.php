<?php

declare(strict_types=1);

namespace CraftCms\Cms\View\Contracts;

interface ProvidesObjectTemplateSuggestions
{
    /** @return array<string, string> Property paths mapped to their labels. */
    public static function objectTemplateSuggestions(): array;
}
