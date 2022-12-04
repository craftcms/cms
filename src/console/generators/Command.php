<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\generators;

use Craft;
use craft\console\Controller;
use craft\helpers\StringHelper;
use Nette\PhpGenerator\PhpNamespace;
use yii\console\ExitCode;

/**
 * Creates a new console command.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4.0
 */
class Command extends BaseGenerator
{
    public function run(): bool
    {
        $name = $this->controller->prompt('Command name: (kebab-case)', [
            'required' => true,
            'pattern' => '/^[a-z]([a-z0-9\\-]*[a-z0-9])?$/',
        ]);

        $ns = $this->autoloadedNamespacePrompt('Namespace:', 'console\\controllers');

        $namespace = (new PhpNamespace($ns))
            ->addUse(Craft::class)
            ->addUse(Controller::class)
            ->addUse(ExitCode::class);

        $className = sprintf('%sController', StringHelper::toPascalCase($name));
        $class = $this->createClass($className, Controller::class, [
            self::CLASS_PROPERTIES => $this->properties(),
            self::CLASS_METHODS => $this->methods(),
        ]);
        $namespace->add($class);

        $class->addComment(sprintf('%s controller', StringHelper::toTitleCase(str_replace('-', ' ', $name))));

        $class->addMethod('actionIndex')
            ->setPublic()
            ->setReturnType('int')
            ->setComment('Default action')
            ->setBody(<<<PHP
// ...
return ExitCode::OK;
PHP);

        $this->writePhpClass($namespace);
        return true;
    }

    private function properties(): array
    {
        return [
            'defaultAction',
        ];
    }

    private function methods(): array
    {
        return [
            'options' => <<<PHP
\$options = parent::options(\$actionID);
switch (\$actionID) {
    case 'index':
        // \$options[] = '...';
        break;
}
return \$options;
PHP,
        ];
    }
}
