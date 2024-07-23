<?php

namespace craft\filters;

use Craft;

class Headers extends \yii\base\ActionFilter
{
    use SiteFilterTrait;

    public array $headers = [];

    public function beforeAction($action): bool
    {
        foreach ($this->headers as $name => $value) {
            Craft::$app->getResponse()->getHeaders()->set($name, $value);
        }

        return true;
    }
}
