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
    private string $className;
    private string $namespace;
    private string $displayName;

    public function run(): bool
    {
        $this->className = $this->classNamePrompt('Asset bundle name:', [
            'required' => true,
        ]);
        $this->className = StringHelper::ensureRight($this->className, 'Asset');

        $this->namespace = $this->namespacePrompt('Asset bundle namespace:', [
            'default' => sprintf(
                '%s\\web\\assets\\%s',
                $this->baseNamespace,
                strtolower(StringHelper::removeRight($this->className, 'Asset'))
            ),
        ]);

        $this->displayName = Inflector::camel2words(StringHelper::removeRight($this->className, 'Asset'));

        $namespace = (new PhpNamespace($this->namespace))
            ->addUse(Craft::class)
            ->addUse(BaseAssetBundle::class);

        $class = $this->createClass($this->className, BaseAssetBundle::class, [
            self::CLASS_PROPERTIES => $this->properties(),
        ]);
        $namespace->add($class);

        $class->addComment("$this->displayName asset bundle");

        $this->writePhpClass($namespace);

        $basePath = $this->namespacePath($this->namespace);
        $this->controller->createDirectory("$basePath/dist");
        $this->controller->createDirectory("$basePath/src");

        $this->controller->stdout(PHP_EOL);
        $this->controller->success('**Asset bundle created!**');

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
