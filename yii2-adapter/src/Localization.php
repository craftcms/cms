<?php

/**
 * @link https://github.com/yii2tech
 *
 * @copyright Copyright (c) 2019 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace CraftCms\Yii2Adapter;

use craft\i18n\I18N;
use CraftCms\Cms\Support\Str;

class Localization extends I18N
{
    /**
     * @var string[] list of translation categories.
     *               Messages from these categories will be translated directly via Laravel translator without involving Yii.
     *               Translation message key will be composed by concatenation of category, dot symbol ('.') and message.
     */
    public array $laravelCategories = [];

    /**
     * {@inheritdoc}
     */
    public function translate($category, $message, $params, $language): string
    {
        if (in_array($category, $this->laravelCategories, true)) {
            return __($category . '.' . $message, $params, $language);
        }

        return parent::translate($category, $message, $params, $language);
    }

    /**
     * {@inheritdoc}
     */
    public function format($message, $params, $language): string
    {
        $params = (array) $params;

        $message = parent::format($message, $params, $language);

        return $this->makeReplacements($message, $params);
    }

    /**
     * Make the Laravel-like place-holder replacements on a translated message.
     * It replaces placeholders, marked by ':', which are not processed with original {@see format()} method.
     *
     * @param  string  $message  raw message.
     * @param  array  $params  the parameters that will be used for the replacement.
     * @return string the formatted message.
     */
    protected function makeReplacements(?string $message, array $params): string
    {
        if (!$message) {
            return '';
        }

        if (empty($params)) {
            return $message;
        }

        uksort($params, function($a, $b) {
            if (mb_strlen($a) > mb_strlen($b)) {
                return -1;
            }

            return 1;
        });

        foreach ($params as $key => $value) {
            $message = str_replace(
                [':' . $key, ':' . Str::upper($key), ':' . Str::ucfirst($key)],
                [$value, Str::upper($value), Str::ucfirst($value)],
                $message
            );
        }

        return $message;
    }
}
