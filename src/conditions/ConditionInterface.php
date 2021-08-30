<?php

namespace craft\conditions;

/**
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0
 */
interface ConditionInterface
{
    /**
     * @return string
     * @since 4.0
     */
    public function getHtml(): string;

    /**
     * @return array
     * @since 4.0
     */
    public function getConfig(): array;
}