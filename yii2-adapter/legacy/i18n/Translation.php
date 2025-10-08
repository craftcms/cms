<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\i18n;

use CraftCms\Cms\Support\Json;
use InvalidArgumentException;
use function CraftCms\Cms\t;

/**
 * Translation helper
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
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
        return 't9n:' . Json::encode(func_get_args());
    }

    /**
     * Lazy-translates a source translation that was prepared by [[prep()]].
     *
     * @param string $translation The prepared source translation.
     * @return string The translated message.
     */
    public static function translate(string $translation): string
    {
        if (!str_starts_with($translation, 't9n:')) {
            return $translation;
        }

        try {
            $args = Json::decode(substr($translation, 4));
        } catch (InvalidArgumentException) {
            return $translation;
        }

        $category = $args[0] ?? null;
        $message = $args[1] ?? '';
        $params = $args[2] ?? [];
        $language = $args[3] ?? null;

        return t($message, $params, $category, $language);
    }
}
