<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\LooseEnds;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Support\File;
use CraftCms\Yii2Adapter\Database\DeprecatedTable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Throwable;

use function CraftCms\Cms\currentUser;

/**
 * Applies the Yii2 Loose Ends utility’s built-in fixes, for findings the adapter can resolve on its own.
 *
 * @internal
 */
class LooseEndsFixes
{
    /**
     * The commands that drop support for unused deprecated concepts, with the table that must be empty and the field
     * type that mustn’t be in use first.
     */
    private const array DROP_SUPPORT = [
        'content.categories' => ['craft:drop-categories-support', DeprecatedTable::CATEGORIES, 'craft\fields\Categories'],
        'content.tags' => ['craft:drop-tags-support', DeprecatedTable::TAGS, 'craft\fields\Tags'],
        'content.global-sets' => ['craft:drop-global-sets-support', DeprecatedTable::GLOBALSETS, null],
    ];

    /**
     * Returns whether the current environment and user may apply fixes.
     *
     * Fixes change the database schema or the project’s files, so they follow the same rules as the commands they
     * replace: admin changes must be allowed, and the environment can’t be production.
     */
    public function allowed(): bool
    {
        return Cms::config()->allowAdminChanges
            && !app()->isProduction()
            && (currentUser()?->isAdmin() ?? false);
    }

    /**
     * Returns whether a finding has a built-in fix that can be applied safely right now.
     */
    public function canFix(string $key): bool
    {
        if (isset(self::DROP_SUPPORT[$key])) {
            [, $table, $fieldType] = self::DROP_SUPPORT[$key];

            return $this->isUnused($table, $fieldType);
        }

        return $key === 'layout.translations'
            && is_dir(base_path('translations'))
            && !file_exists(lang_path());
    }

    /**
     * Applies a finding’s built-in fix.
     *
     * @return bool Whether the fix was applied.
     */
    public function fix(string $key): bool
    {
        if (!$this->canFix($key)) {
            return false;
        }

        if (isset(self::DROP_SUPPORT[$key])) {
            return Artisan::call(self::DROP_SUPPORT[$key][0], ['--force' => true]) === 0;
        }

        return File::moveDirectory(base_path('translations'), lang_path());
    }

    /**
     * Returns whether a deprecated concept’s table has no rows at all, trashed ones included, and no field of its type
     * exists.
     */
    private function isUnused(string $table, ?string $fieldType): bool
    {
        try {
            return DB::table($table)->doesntExist()
                && ($fieldType === null || DB::table(Table::FIELDS)->where('type', $fieldType)->doesntExist());
        } catch (Throwable) {
            return false;
        }
    }
}
