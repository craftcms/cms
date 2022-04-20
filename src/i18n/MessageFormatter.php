<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\i18n;

/**
 * @inheritdoc
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.13
 * @internal
 */
class MessageFormatter extends \yii\i18n\MessageFormatter
{
    /**
     * @inheritdoc
     */
    public function format($pattern, $params, $language): string|false
    {
        if ($params === []) {
            return $pattern;
        }

        if (
            !class_exists(\MessageFormatter::class, false) ||
            !defined('INTL_ICU_VERSION') ||
            /** @phpstan-ignore-next-line */
            version_compare(INTL_ICU_VERSION, '49', '<')
        ) {
            return $this->fallbackFormat($pattern, $params, $language);
        }

        return parent::format($pattern, $params, $language);
    }
}
