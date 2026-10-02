<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\i18n;

use CraftCms\Cms\Support\Facades\I18N;

/**
 * Translation helper
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Support\Facades\I18N} instead.
 */
abstract class Translation
{
    /**
     * Prepares a source translation to be lazy-translated with [[translate()]].
     *
     * @param string $category The message category.
     * @param string $message The message to be translated.
     * @param array $params The parameters that will be used to replace the corresponding placeholders in the message.
     * @param string|null $language The language code (e.g. `en-US`, `en`). If this is `null`, the current
     * [[\yii\base\Application::language|application language]] will be used by default.
     * @return string The translated message.
     */
    public static function prep(string $category, string $message, array $params = [], ?string $language = null): string
    {
        return I18N::prep($message, $params, $category, $language);
    }

    /**
     * Lazy-translates a source translation that was prepared by [[prep()]].
     *
     * @param string $translation The prepared source translation.
     * @return string The translated message.
     */
    public static function translate(string $translation): string
    {
        return I18N::translate($translation);
    }
}
