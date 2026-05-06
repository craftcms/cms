<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements;

use CraftCms\Cms\User\Elements\User as UserElement;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @deprecated 6.0.0 use {@see UserElement} instead.
     */
    class User extends UserElement
    {
    }
}

class_alias(UserElement::class, User::class);
