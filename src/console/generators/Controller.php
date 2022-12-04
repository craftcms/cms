<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\generators;

use Craft;
use craft\helpers\App;
use craft\helpers\StringHelper;
use craft\web\Controller as BaseController;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\PhpNamespace;
use yii\base\Application;
use yii\web\Response;

/**
 * Creates a new web controller.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4.0
 */
class Controller extends BaseGenerator
{
    public function run(): bool
    {
        $relId = $this->controller->prompt('Controller ID: (kebab-case)', [
            'required' => true,
            'pattern' => '/^([a-z][a-z0-9]*\\/)*[a-z]([a-z0-9\\-]*[a-z0-9])?$/',
        ]);

        $idParts = explode('/', $relId);
        $id = array_pop($idParts);
        $className = sprintf('%sController', StringHelper::toPascalCase($id));

        $ns = $this->namespacePrompt('Controller namespace:', [
            'default' => "$this->baseNamespace\\controllers",
        ]);
        $ns = App::normalizeNamespace(sprintf('%s\\%s', $ns, implode('\\', $idParts)));

        $namespace = (new PhpNamespace($ns))
            ->addUse(Craft::class)
            ->addUse(BaseController::class)
            ->addUse(Response::class);

        $class = $this->createClass($className, BaseController::class, [
            self::CLASS_PROPERTIES => $this->properties(),
        ]);
        $namespace->add($class);

        $class->addComment(sprintf('%s controller', StringHelper::toTitleCase(str_replace('-', ' ', $id))));

        $uniqueId = $this->module instanceof Application ? $id : sprintf('%s/%s', $this->module->getUniqueId(), $id);
        $class->addMethod('actionFooBar')
            ->setPublic()
            ->setReturnType(Response::class)
            ->setComment("$uniqueId action")
            ->setBody(<<<PHP
// ...
PHP);

        $this->writePhpClass($namespace);
        return true;
    }

    private function properties(): array
    {
        return [
            'allowAnonymous' => new Literal('self::ALLOW_ANONYMOUS_NEVER'),
        ];
    }
}
