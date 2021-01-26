<?php

namespace craft\test;

/** @noinspection PhpIgnoredClassAliasDeclaration */
class_alias(ActiveFixture::class, 'craft\test\Fixture');

if (false) {
    /**
     * @deprecated in 3.6.0. Use [[ActiveFixture]] instead.
     */
    class Fixture {
    }
}
