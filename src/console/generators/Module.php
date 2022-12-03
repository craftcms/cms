<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\generators;

use Craft;
use Nette\PhpGenerator\PhpFile;
use yii\base\Module as YiiModule;

/**
 * Creates a new application module.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4.0
 */
class Module extends BaseGenerator
{
    private string $id;
    private string $targetDir;
    private string $rootNamespace;

    public function run(): bool
    {
        $this->id = $this->controller->prompt('Module ID: (kebab-cased)', [
            'required' => true,
            'pattern' => '/^[a-z]([a-z0-9\\-]*[a-z0-9])?$/',
        ]);

        $this->targetDir = $this->directoryPrompt('Module location:', [
            'default' => "@root/modules/$this->id",
            'ensureEmpty' => true,
            'ensureCouldAutoload' => true,
        ]);

        $this->rootNamespace = $this->ensureAutoloadable($this->targetDir, $addedRoot);

        $this->controller->stdout(PHP_EOL . 'Generating module files…' . PHP_EOL);

        if (!file_exists($this->targetDir)) {
            $this->controller->createDirectory($this->targetDir);
        }

        // Module class
        $this->writeModuleClass();
        $this->controller->stdout("Module files generated.\n\n");

        $instructions = <<<MD
**The module is ready to be installed!**

MD;
        if ($addedRoot) {
            $instructions .= <<<MD
Run the following command to ensure the module gets autoloaded:

```php
> composer dump-autoload
```

MD;
        }

        $instructions .= <<<MD
To install the module, open `config/app.php` and add the following to the `return` array:

```
'modules' => [
    '$this->id' => \\$this->rootNamespace\\Module::class,
],
```

If you want your module to be loaded during application initialization on every request,
also include `'$this->id'` in the `bootstrap` array:

```
'bootstrap' => [
    '$this->id',
],
```
MD;

        $this->controller->success($this->controller->markdownToAnsi($instructions));
        $this->controller->stdout(PHP_EOL);
        return true;
    }

    private function writeModuleClass(): void
    {
        $file = new PhpFile();

        $namespace = $file->addNamespace($this->rootNamespace)
            ->addUse(Craft::class)
            ->addUse(YiiModule::class, 'BaseModule');

        $slashedRootNamespace = addslashes($this->rootNamespace);
        $class = $this->createClass('Module', $namespace, YiiModule::class, [
            self::CLASS_METHODS => [
                'init' => <<<PHP
// Set the controllerNamespace based on whether this is a console or web request
if (Craft::\$app->getRequest()->getIsConsoleRequest()) {
    \$this->controllerNamespace = '$slashedRootNamespace\\\\console\\\\controllers';
} else {
    \$this->controllerNamespace = '$slashedRootNamespace\\\\controllers';
}

parent::init();

// Defer most setup tasks until Craft is fully initialized
Craft::\$app->onInit(function() {
    // ...
});
PHP,
            ],
        ]);

        $class->setComment(<<<EOD
$this->id module

@method static Module getInstance()
EOD
        );

        $this->writePhpFile("$this->targetDir/Module.php", $file);
    }
}
