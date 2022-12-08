<?php

namespace craft\console\generators;

use Craft;
use craft\base\Widget;
use Nette\PhpGenerator\PhpNamespace;
use yii\helpers\Inflector;
use yii\web\Application;

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

        $message = "**Widget type created!**";
        if (!$this->module instanceof Application) {
            $moduleFile = $this->moduleFile();
            $message .= "\n" . <<<MD
Add the following code to `$moduleFile` to register the widget type:

```php
use craft\\events\\RegisterComponentTypesEvent;
use craft\\services\\Dashboard;
use yii\\base\\Event;
use $this->namespace$this->className;

Event::on(
    Dashboard::class,
    Dashboard::EVENT_REGISTER_ELEMENT_TYPES,
    function(RegisterComponentTypesEvent \$event) {
        \$event->types[] = $this->className::class;
    }
);
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
            'isSelectable' => 'return true;',
            'icon' => 'return null;',
            'getBodyHtml' => '// ...',
        ];
    }
}
