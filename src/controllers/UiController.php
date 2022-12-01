<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\web\Controller;

class UiController extends Controller
{
    public function actionIndex()
    {
        $components = Craft::$app->getUi()->getComponentTypesByName();
        $variables = [
            'components' => $components,
        ];

        return $this->renderTemplate('_ui/index', $variables);
    }

    public function actionShow(string $name)
    {
        $variables = [];
        $variables['name'] = $name;
        $variables['props'] = Craft::$app->getUi()->propsDataFor($name);
        $variables['components'] = Craft::$app->getUi()->getComponentTypesByName();

        return $this->renderTemplate('_ui/entry', $variables);
    }

    public function actionPreview(string $name)
    {
        $props = $this->request->getParam('props', []);
        $component = Craft::$app->getUi()->createAndRender($name, $props);

        return $this->renderTemplate('_ui/preview', [
            'component' => $component,
            'name' => $name,
        ]);
    }
}
