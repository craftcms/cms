<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\auth;

use craft\base\SavableComponent;
use craft\helpers\Html;

abstract class AbstractProvider extends SavableComponent implements ProviderInterface
{
    use AuthProviderTrait,
        AuthUserMapperTrait;

    /**
     * @inheritdoc
     */
    public function getSiteLoginHtml(?string $label = null, ?string $url = null): string
    {
        return Html::a($label ?: "Login via " . $this->name, $url ?: $this->getLoginRequestUrl());
    }

    /**
     * @inheritdoc
     */
    public function getCpLoginHtml(?string $label = null, ?string $url = null): string
    {
        return Html::a($label ?: "Login via " . $this->name, $url ?: $this->getLoginRequestUrl());
    }

}
