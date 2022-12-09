<?php

namespace craft\console\generators;

use Craft;
use craft\base\Fs;
use craft\models\FsListing;
use craft\services\Fs as FsService;
use Nette\PhpGenerator\PhpNamespace;
use yii\helpers\Inflector;
use yii\web\Application;

/**
 * Creates a new filesystem type.
 */
class FilesystemType extends BaseGenerator
{
    private string $className;
    private string $namespace;
    private string $displayName;

    public function run(): bool
    {
        $this->className = $this->classNamePrompt('Filesystem type name:', [
            'required' => true,
        ]);

        $this->namespace = $this->namespacePrompt('Filesystem type namespace:', [
            'default' => "$this->baseNamespace\\fs",
        ]);

        $this->displayName = Inflector::camel2words($this->className);

        $namespace = (new PhpNamespace($this->namespace))
            ->addUse(Craft::class)
            ->addUse(Fs::class)
            ->addUse(FsListing::class);

        $class = $this->createClass($this->className, Fs::class, [
            self::CLASS_METHODS => $this->methods(),
        ]);
        $namespace->add($class);

        $class->addComment("$this->displayName filesystem type");

        $this->writePhpClass($namespace);

        $message = "**Filesystem type created!**";
        if (
            !$this->module instanceof Application &&
            !$this->addRegistrationEventCode(
                FsService::class,
                'EVENT_REGISTER_FILESYSTEM_TYPES',
                "$this->namespace\\$this->className",
                $fallbackExample,
            )
        ) {
            $moduleFile = $this->moduleFile();
            $message .= "\n" . <<<MD
Add the following code to `$moduleFile` to register the filesystem type:

```
$fallbackExample
```
MD;
        }

        $this->controller->success($message);
        return true;
    }

    private function methods(): array
    {
        return [
            'displayName' => sprintf('return %s;', $this->messagePhp($this->displayName)),
            'attributeLabels' => <<<PHP
return array_merge(parent::attributeLabels(), [
    // ...
]);
PHP,
            'defineRules' => <<<PHP
return array_merge(parent::defineRules(), [
    // ...
]);
PHP,
            'getSettingsHtml' => 'return null;',
            'getFileList' => <<<PHP
// Loop through the files and directories in \$directory and yield FsListing models representing them
// ...
PHP,
            'getFileSize' => <<<PHP
// Return the file size of \$uri in bytes
// ...
PHP,
            'getDateModified' => <<<PHP
// Return the date modified for \$uri as a Unix timestamp
// ...
PHP,
            'read' => '// ...',
            'write' => '// ...',
            'writeFileFromStream' => '// ...',
            'fileExists' => '// ...',
            'deleteFile' => '// ...',
            'renameFile' => '// ...',
            'copyFile' => '// ...',
            'getFileStream' => '// ...',
            'directoryExists' => '// ...',
            'createDirectory' => '// ...',
            'deleteDirectory' => '// ...',
            'renameDirectory' => '// ...',
        ];
    }
}
