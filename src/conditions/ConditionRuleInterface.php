<?php

namespace craft\conditions;

/**
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0
 */
interface ConditionRuleInterface
{
    /**
     * @return string
     */
    public function getHtml(): string;

    /**
     * @return string
     * @since 4.0
     */
    public function getInputHtml(): string;
}