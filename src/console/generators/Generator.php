<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\generators;

use Craft;
use craft\helpers\App;
use Nette\PhpGenerator\PhpNamespace;
use ReflectionClass;
use yii\base\Application;
use yii\helpers\Inflector;

/**
 * Creates a new component generator.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4.0
 */
class Generator extends BaseGenerator
{
    private string $humanName;
    private string $baseClassName;
    private ?string $baseClassAlias = null;
    private string $defaultNamespace;

    public function run(): bool
    {
        $name = $this->classNamePrompt('Generator name:', [
            'required' => true,
        ]);

        $ns = $this->namespacePrompt('Generator namespace:', [
            'default' => "$this->baseNamespace\\console\\generators",
        ]);

        $this->humanName = ucfirst(Inflector::camel2words($name, false));
        $pluralHumanName = Inflector::pluralize(strtolower($this->humanName));

        $baseClass = $this->classPrompt("Base class for generated $pluralHumanName:", [
            'required' => true,
            'ensureExists' => true,
        ]);

        // Are we going to have a class name conflict when importing the base class?
        [, $this->baseClassName] = App::classParts($baseClass);
        if ($this->baseClassName === $name) {
            $this->baseClassAlias = "Base$this->baseClassName";
        }

        $this->defaultNamespace = $this->namespacePrompt("Default namespace for generated $pluralHumanName (relative to the base namespace):", [
            'ensureContained' => false,
            'default' => str_replace(' ', '', $pluralHumanName),
        ]);

        $namespace = (new PhpNamespace($ns))
            ->addUse(Craft::class)
            ->addUse(BaseGenerator::class)
            ->addUse(Inflector::class)
            ->addUse(PhpNamespace::class)
            ->addUse($baseClass, $this->baseClassAlias);

        $class = $this->createClass($name, BaseGenerator::class, [
            self::CLASS_METHODS => $this->methods(),
        ]);
        $namespace->add($class);

        $class->addComment("$this->humanName generator");

        $baseGeneratorClass = BaseGenerator::class;
        foreach (['constants', 'properties', 'methods'] as $type) {
            $class->addMethod($type)
                ->setPrivate()
                ->setReturnType('array')
                ->setBody(<<<PHP
// List any $type that should be copied into generated $pluralHumanName from $baseClass
// (see `$baseGeneratorClass::createClass()`)
return [];
PHP);
        }

        $this->writePhpClass($namespace);

        $message = "**$this->humanName generator created!**";
        if (!$this->module instanceof Application) {
            $moduleFile = (new ReflectionClass($this->module))->getFileName();
            $message .= "\n" . <<<MD
Register it for Craft’s `make` command by adding the following code to `$moduleFile`:

```
use craft\\console\\controllers\\MakeController;
use craft\\events\\RegisterComponentTypesEvent;
use yii\\base\\Event;
use $ns$name;

Event::on(
    MakeController::class,
    MakeController::EVENT_REGISTER_GENERATOR_TYPES,
    function(RegisterComponentTypesEvent \$event) {
        \$event->types[] = $name::class;
    }
);
```
MD;
        }

        $this->controller->stdout(PHP_EOL);
        $this->controller->success($message);

        return true;
    }

    private function methods(): array
    {
        $lowerHumanType = strtolower($this->humanName);
        $baseClass = $this->baseClassAlias ?? $this->baseClassName;
        $slashedDefaultNamespace = addslashes($this->defaultNamespace);

        return [
            'run' => <<<PHP
\$name = \$this->classNamePrompt('$this->humanName name:', [
    'required' => true,
]);

\$ns = \$this->namespacePrompt('$this->humanName namespace:', [
    'default' => "\$this->baseNamespace\\\\$slashedDefaultNamespace",
]);

\$namespace = (new PhpNamespace(\$ns))
    ->addUse(Craft::class)
    ->addUse(BaseGenerator::class)
    ->addUse($baseClass::class);

\$class = \$this->createClass(\$name, $baseClass::class, [
    self::CLASS_CONSTANTS => \$this->constants(),
    self::CLASS_PROPERTIES => \$this->properties(),
    self::CLASS_METHODS => \$this->methods(),
]);
\$namespace->add(\$class);

\$class->addComment(sprintf('%s $lowerHumanType', Inflector::camel2words(\$name)));

\$this->writePhpClass(\$namespace);

\$this->controller->stdout(PHP_EOL);
\$this->controller->success("**$this->humanName created!**");
return true;
PHP,
        ];
    }
}
