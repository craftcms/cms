<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\auth\provider;

use craft\helpers\Html;
use yii\base\Component;

/**
 *
 */
abstract class AbstractProvider extends Component implements ProviderInterface
{
    use AuthProviderTrait;

    /**
     * @return $this
     */
    protected function getProvider(): static
    {
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getSiteLoginHtml(?string $label = null, ?string $url = null): string
    {
        return Html::a($label ?: "Login via " . $this->name, $url ?: $this->getRequestUrl());
    }

    /**
     * @inheritdoc
     */
    public function getCpLoginHtml(?string $label = null, ?string $url = null): string
    {
        return Html::a($label ?: "Login via " . $this->name, $url ?: $this->getRequestUrl());
    }
}
