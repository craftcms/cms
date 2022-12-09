<?php

namespace craft\console\generators;

use Craft;
use craft\base\Widget;
use craft\services\Dashboard;
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
        if (
            !$this->module instanceof Application &&
            !$this->addRegistrationEventCode(
                Dashboard::class,
                'EVENT_REGISTER_WIDGET_TYPES',
                "$this->namespace\\$this->className",
                $fallbackExample,
            )
        ) {
            $moduleFile = $this->moduleFile();
            $message .= "\n" . <<<MD
Add the following code to `$moduleFile` to register the widget type:

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
            'isSelectable' => 'return true;',
            'icon' => 'return null;',
            'getBodyHtml' => '// ...',
        ];
    }
}
