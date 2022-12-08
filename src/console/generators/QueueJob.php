<?php

namespace craft\console\generators;

use Craft;
use craft\queue\BaseJob;
use Nette\PhpGenerator\PhpNamespace;
use yii\helpers\Inflector;

/**
 * Creates a new queue job.
 */
class QueueJob extends BaseGenerator
{
    private string $className;
    private string $namespace;
    private string $displayName;

    public function run(): bool
    {
        $this->className = $this->classNamePrompt('Queue job name:', [
            'required' => true,
        ]);

        $this->namespace = $this->namespacePrompt('Queue job namespace:', [
            'default' => "$this->baseNamespace\\jobs",
        ]);

        $this->displayName = Inflector::camel2words($this->className);

        $namespace = (new PhpNamespace($this->namespace))
            ->addUse(Craft::class)
            ->addUse(BaseJob::class);

        $class = $this->createClass($this->className, BaseJob::class, [
            self::CLASS_METHODS => $this->methods(),
        ]);
        $namespace->add($class);

        $class->addComment("$this->displayName queue job");

        $this->writePhpClass($namespace);

        $this->controller->success("**Queue job created!**");
        return true;
    }

    private function methods(): array
    {
        return [
            'execute' => '// ...',
            'defaultDescription' => 'return null;',
        ];
    }
}
