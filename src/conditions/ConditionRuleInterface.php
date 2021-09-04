<?php

namespace craft\conditions;

use craft\base\ComponentInterface;

/**
 * ConditionRuleInterface defines the common interface to be implemented by condition rule classes.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
interface ConditionRuleInterface extends ComponentInterface
{
    /**
     * @return string
     */
    public function getHtml(): string;

    /**
     * @return string
     */
    public function getInputHtml(): string;
}
