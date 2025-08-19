<?php

namespace craft\errors;

use yii\base\Exception;

/** @phpstan-ignore-next-line */
if (false) {

    /**
     * Class InvalidPluginException
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 3.0.0
     * @deprecated 6.0.0 {@see \CraftCms\Cms\Plugin\Exceptions\InvalidPluginException}
     */
    class InvalidPluginException extends Exception
    {
    }
}

class_alias(\CraftCms\Cms\Plugin\Exceptions\InvalidPluginException::class, InvalidPluginException::class);
