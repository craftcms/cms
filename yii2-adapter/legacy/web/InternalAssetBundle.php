<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web;

use Craft;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Str;
use function CraftCms\Cms\craftAsset;

/**
 * @inheritdoc
 */
class InternalAssetBundle extends AssetBundle
{
    public function publish($am)
    {
        // Don't publish
    }

    public function registerAssetFiles($view): void
    {
        $root = Str::after($this->sourcePath, __DIR__ . '/assets/');

        foreach ($this->js as $js) {
            if (is_array($js)) {
                $file = array_shift($js);

                $options = Arr::merge($this->jsOptions, $js);

                HtmlStack::jsFile(craftAsset("legacy/$root/$file"), $options);
            } elseif ($js !== null) {
                HtmlStack::jsFile(craftAsset("legacy/$root/$js"), $this->jsOptions);
            }
        }

        foreach ($this->css as $css) {
            if (is_array($css)) {
                $file = array_shift($css);
                $options = Arr::merge($this->cssOptions, $css);

                HtmlStack::cssFile(craftAsset("legacy/$root/$file"), $options);
            } elseif ($css !== null) {
                HtmlStack::cssFile(craftAsset("legacy/$root/$css"), $this->cssOptions);
            }
        }
    }
}
