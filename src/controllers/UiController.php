<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\helpers\FileHelper;
use craft\helpers\Json;
use craft\web\Controller;

class UiController extends Controller
{
    public function actionIndex()
    {
        $components = Craft::$app->getUi()->getComponentTypesByName();
        $variables = [
            'components' => $components,
        ];

        return $this->renderTemplate('_ui/docs/index', $variables);
    }

    public function actionShow(string $name, string $activeExample = null)
    {
        $variables = [];
        $variables['name'] = $name;
        $variables['props'] = Craft::$app->getUi()->propsDataFor($name);
        $variables['components'] = Craft::$app->getUi()->getComponentTypesByName();

        $exampleDir = Craft::$app->getPath()->getCpTemplatesPath() . '/_ui/';
        $fileName = str_replace(':', '/', $name);
        $exampleFiles = FileHelper::findFiles($exampleDir, [
            'only' => [$fileName . '.json'],
        ]);

        $examples = [];

        if (count($exampleFiles)) {
            $example = $exampleFiles[0];
            $examples = Json::decode(file_get_contents($example));
        }

        $variables['examples'] = $examples;

        return $this->renderTemplate('_ui/docs/entry', $variables);
    }

    public function actionPreview(string $name)
    {
        $props = $this->request->getParam('props', []);
        $props = Json::decodeIfJson($props);
        $component = Craft::$app->getUi()->createAndRender($name, $props);

        return $this->renderTemplate('_ui/docs/preview', [
            'component' => $component,
            'name' => $name,
        ]);
    }
}
