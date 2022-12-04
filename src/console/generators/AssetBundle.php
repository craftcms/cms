<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\generators;

use Craft;
use craft\helpers\StringHelper;
use craft\web\AssetBundle as BaseAssetBundle;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\PhpNamespace;
use yii\helpers\Inflector;

/**
 * Creates a new asset bundle.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4.0
 */
class AssetBundle extends BaseGenerator
{
    public function run(): bool
    {
        $name = $this->classNamePrompt('Asset bundle name:', [
            'required' => true,
        ]);
        $name = StringHelper::removeRight($name, 'Asset');

        $ns = $this->namespacePrompt('Asset bundle namespace:', [
            'default' => sprintf('%s\\web\\assets\\%s', $this->baseNamespace, strtolower($name)),
        ]);

        $namespace = (new PhpNamespace($ns))
            ->addUse(Craft::class)
            ->addUse(BaseAssetBundle::class);

        $class = $this->createClass($name . 'Asset', BaseAssetBundle::class, [
            self::CLASS_PROPERTIES => $this->properties(),
        ]);
        $namespace->add($class);

        $class->addComment(sprintf('%s asset bundle', StringHelper::toTitleCase(Inflector::camel2words($name))));

        $this->writePhpClass($namespace);

        $basePath = $this->namespacePath($ns);
        $this->controller->createDirectory("$basePath/dist");
        $this->controller->createDirectory("$basePath/src");

        return true;
    }

    private function properties(): array
    {
        return [
            'sourcePath' => new Literal("__DIR__ . '/dist'"),
            'depends',
            'js',
            'css',
        ];
    }
}
