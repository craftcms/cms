<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use CraftCms\Cms\Support\Template as BaseTemplate;
use CraftCms\Cms\Twig\TwigExceptionMapper;
use CraftCms\Cms\View\TemplateProfiler;
use yii\db\QueryInterface;

/**
 * Class Template
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see BaseTemplate} instead.
 */
class Template extends BaseTemplate
{
    public const string PROFILE_TYPE_TEMPLATE = TemplateProfiler::PROFILE_TYPE_TEMPLATE;
    public const string PROFILE_TYPE_BLOCK = TemplateProfiler::PROFILE_TYPE_BLOCK;
    public const string PROFILE_TYPE_MACRO = TemplateProfiler::PROFILE_TYPE_MACRO;

    public const string PROFILE_STAGE_BEGIN = TemplateProfiler::PROFILE_STAGE_BEGIN;
    public const string PROFILE_STAGE_END = TemplateProfiler::PROFILE_STAGE_END;

    /**
     * Returns whether a fallback variable has been defined.
     *
     * @since 4.4.0
     * @deprecated in 5.9.15
     */
    public static function fallbackExists(string $name): bool
    {
        return parent::fallbackValueExists($name);
    }

    /**
     * Provides dynamically-defined fallback variable's value.
     *
     * @since 4.4.0
     * @deprecated in 5.9.15
     */
    public static function fallback(string $name): mixed
    {
        return parent::fallbackValue($name);
    }

    /**
     * Paginates a query.
     *
     * @deprecated in 3.6.0. Use [[paginateQuery()]] instead.
     */
    public static function paginateCriteria(QueryInterface $query): array
    {
        return self::paginateQuery($query);
    }

    public static function beginProfile(string $type, string $name): void
    {
        app(TemplateProfiler::class)->beginProfile($type, $name);
    }

    public static function endProfile(string $type, string $name): void
    {
        app(TemplateProfiler::class)->endProfile($type, $name);
    }

    /**
     * Attempts to resolve a compiled template file path and line number to its source template path and line number.
     *
     * @return array|false
     * @deprecated 6.0.0 use {@see TwigExceptionMapper::resolveTemplatePathAndLine()} instead.
     */
    public static function resolveTemplatePathAndLine(string $path, ?int $line): array|false
    {
        return app(TwigExceptionMapper::class)->resolveTemplatePathAndLine($path, $line);
    }
}
