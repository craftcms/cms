<?php

namespace craft\errors;

use yii\base\Exception;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * ElementException represents an exception involving an element.
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 3.4.29
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Exceptions\ElementException} instead.
     */
    class ElementException extends Exception
    {
    }
}

class_alias(\CraftCms\Cms\Element\Exceptions\ElementException::class, ElementException::class);
