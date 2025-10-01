<?php

namespace craft\base;

use CraftCms\Cms\Component\Contracts\SavableComponentInterface;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     *  FieldInterface defines the common interface to be implemented by field classes.
     *  A class implementing this interface should also use [[SavableComponentTrait]] and [[FieldTrait]].
     *
     * @mixin FieldTrait
     * @mixin \yii\base\Component
     * @mixin Model
     * @mixin SavableComponentTrait
     * @phpstan-require-extends Field
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 3.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Field\Contracts\FieldInterface} instead.
     */
    interface FieldInterface extends \CraftCms\Cms\Field\Contracts\FieldInterface, ModelInterface
    {

    }
}

class_alias(\CraftCms\Cms\Field\Contracts\FieldInterface::class, FieldInterface::class);
