<?php

namespace craft\console\generators;

use Craft;
use craft\base\Widget;
use Nette\PhpGenerator\PhpNamespace;
use yii\helpers\Inflector;

/**
 * Creates a new widget type.
 */
class WidgetType extends BaseGenerator
{
    private string $className;
    private string $namespace;
    private string $displayName;

    public function run(): bool
    {
        $this->className = $this->classNamePrompt('Widget type name:', [
            'required' => true,
        ]);

        $this->namespace = $this->namespacePrompt('Widget type namespace:', [
            'default' => "$this->baseNamespace\\widgets",
        ]);

        $this->displayName = Inflector::camel2words($this->className);

        $namespace = (new PhpNamespace($this->namespace))
            ->addUse(Craft::class)
            ->addUse(Widget::class);

        $class = $this->createClass($this->className, Widget::class, [
            self::CLASS_METHODS => $this->methods(),
        ]);
        $namespace->add($class);

        $class->addComment("$this->displayName widget type");

        $this->writePhpClass($namespace);

        $this->controller->stdout(PHP_EOL);
        $this->controller->success("**Widget type created!**");
        return true;
    }

    private function methods(): array
    {
        // List any methods that should be copied into generated widget types from craft\base\Widget
        // (see `craft\console\generators\BaseGenerator::createClass()`)
        return [
            'displayName' => sprintf('return %s;', $this->messagePhp($this->displayName)),
            'isSelectable' => 'return true;',
            'icon' => 'return null;',
            'getBodyHtml' => '// ...',
        ];
    }
}
