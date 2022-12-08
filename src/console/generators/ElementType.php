<?php

namespace craft\console\generators;

use Craft;
use craft\base\Element as BaseElement;
use craft\elements\conditions\ElementCondition;
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\elements\User;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\web\CpScreenResponseBehavior;
use Nette\PhpGenerator\PhpNamespace;
use yii\helpers\Inflector;
use yii\web\Application;
use yii\web\Response;

/**
 * Creates a new element type.
 */
class ElementType extends BaseGenerator
{
    private string $className;
    private string $pluralName;
    private string $pluralKebabCasedName;
    private string $tableName;
    private string $displayName;
    private string $pluralDisplayName;

    private string $namespace;

    private string $queryName;
    private string $queryNamespace;

    private string $conditionName;
    private string $conditionNamespace;

    public function run(): bool
    {
        $this->className = $this->classNamePrompt('Element type name:', [
            'required' => true,
        ]);

        $this->pluralName = Inflector::pluralize($this->className);
        $this->pluralKebabCasedName = StringHelper::toKebabCase($this->pluralName);
        $this->tableName = strtolower($this->pluralName);
        $this->displayName = ucfirst(Inflector::camel2words($this->className, false));
        $this->pluralDisplayName = Inflector::pluralize($this->displayName);

        $this->namespace = $this->namespacePrompt('Element type namespace:', [
            'default' => "$this->baseNamespace\\elements",
        ]);

        $this->queryName = sprintf('%sQuery', $this->className);
        $this->queryNamespace = "$this->namespace\\db";

        $this->conditionName = sprintf('%sCondition', $this->className);
        $this->conditionNamespace = "$this->namespace\\conditions";

        $this->writeElementClass();
        $this->writeQueryClass();
        $this->writeConditionClass();
        $this->writeIndexTemplate();

        $message = '**Element type created!**';
        if (!$this->module instanceof Application) {
            $moduleFile = $this->moduleFile();
            $moduleId = $this->module->id;
            $message .= "\n" . <<<MD
Add the following code to `$moduleFile` to register the element type and a route to the control panel edit page:

```php
use craft\\events\\RegisterComponentTypesEvent;
use craft\\events\\RegisterUrlRulesEvent;
use craft\\services\\Elements;
use craft\\web\\UrlManager;
use yii\\base\\Event;
use $this->namespace$this->className;

Event::on(
    Elements::class,
    Elements::EVENT_REGISTER_ELEMENT_TYPES,
    function(RegisterComponentTypesEvent \$event) {
        \$event->types[] = $this->className::class;
    }
);

Event::on(
    UrlManager::class,
    UrlManager::EVENT_REGISTER_CP_URL_RULES,
    function(RegisterUrlRulesEvent \$event) {
        \$event->rules['$this->pluralKebabCasedName'] = ['template' => '$moduleId/$this->pluralKebabCasedName/_index.twig'];
        \$event->rules['$this->pluralKebabCasedName/<elementId:\d+>'] = 'elements/edit';
    }
);
```
MD;
        }

        $this->controller->stdout(PHP_EOL);
        $this->controller->success($message);
        return true;
    }

    private function writeElementClass(): void
    {
        $namespace = (new PhpNamespace($this->namespace))
            ->addUse(BaseElement::class)
            ->addUse(CpScreenResponseBehavior::class)
            ->addUse(Craft::class)
            ->addUse(ElementConditionInterface::class)
            ->addUse(ElementQueryInterface::class)
            ->addUse(Response::class)
            ->addUse(UrlHelper::class)
            ->addUse(User::class);

        $class = $this->createClass($this->className, BaseElement::class, [
            self::CLASS_METHODS => $this->elementClassMethods(),
        ]);
        $namespace->add($class);

        $class->addComment(sprintf('%s element type', StringHelper::toTitleCase($this->displayName)));

        $this->writePhpClass($namespace);
    }

    private function elementClassMethods(): array
    {
        $camelCasedName = lcfirst($this->className);
        $lowerDisplayName = strtolower($this->displayName);
        $pluralLowerDisplayName = strtolower($this->pluralDisplayName);
        $allElementsLabelPhp = $this->messagePhp("All $pluralLowerDisplayName");

        return [
            'displayName' => sprintf('return %s;', $this->messagePhp($this->displayName)),
            'lowerDisplayName' => sprintf('return %s;', $this->messagePhp($lowerDisplayName)),
            'pluralDisplayName' => sprintf('return %s;', $this->messagePhp($this->pluralDisplayName)),
            'pluralLowerDisplayName' => sprintf('return %s;', $this->messagePhp($pluralLowerDisplayName)),
            'refHandle' => sprintf("return '%s';", strtolower($this->className)),
            'trackChanges' => 'return true;',
            'hasContent' => 'return true;',
            'hasTitles' => 'return true;',
            'hasUris' => 'return true;',
            'isLocalized' => 'return false;',
            'hasStatuses' => 'return true;',
            'find' => "return Craft::createObject($this->queryName::class, [static::class]);",
            'createCondition' => "return Craft::createObject($this->conditionName::class, [static::class]);",
            'defineSources' => <<<PHP
return [
    [
        'key' => '*',
        'label' => $allElementsLabelPhp,
    ],
];
PHP,
            'defineActions' => <<<PHP
// List any bulk element actions here
return [];
PHP,
            'includeSetStatusAction' => 'return true;',
            'defineSortOptions' => <<<PHP
return [
    'title' => Craft::t('app', 'Title'),
    'slug' => Craft::t('app', 'Slug'),
    'uri' => Craft::t('app', 'URI'),
    [
        'label' => Craft::t('app', 'Date Created'),
        'orderBy' => 'elements.dateCreated',
        'attribute' => 'dateCreated',
        'defaultDir' => 'desc',
    ],
    [
        'label' => Craft::t('app', 'Date Updated'),
        'orderBy' => 'elements.dateUpdated',
        'attribute' => 'dateUpdated',
        'defaultDir' => 'desc',
    ],
    [
        'label' => Craft::t('app', 'ID'),
        'orderBy' => 'elements.id',
        'attribute' => 'id',
    ],
    // ...
];
PHP,
            'defineTableAttributes' => <<<PHP
return [
    'slug' => ['label' => Craft::t('app', 'Slug')],
    'uri' => ['label' => Craft::t('app', 'URI')],
    'link' => ['label' => Craft::t('app', 'Link'), 'icon' => 'world'],
    'id' => ['label' => Craft::t('app', 'ID')],
    'uid' => ['label' => Craft::t('app', 'UID')],
    'dateCreated' => ['label' => Craft::t('app', 'Date Created')],
    'dateUpdated' => ['label' => Craft::t('app', 'Date Updated')],
    // ...
];
PHP,
            'defineDefaultTableAttributes' => <<<PHP
return [
    'link',
    'dateCreated',
    // ...
];
PHP,
            'defineRules' => <<<PHP
return array_merge(parent::defineRules(), [
    // ...
]);
PHP,
            'getUriFormat' => <<<PHP
// If $pluralLowerDisplayName should have URLs, define their URI format here
return null;
PHP,
            'previewTargets' => <<<PHP
\$previewTargets = [];
\$url = \$this->getUrl();
if (\$url) {
    \$previewTargets[] = [
        'label' => Craft::t('app', 'Primary {type} page', [
            'type' => self::lowerDisplayName(),
        ]),
        'url' => \$url,
    ];
}
return \$previewTargets;
PHP,
            'route' => <<<PHP
// Define how $pluralLowerDisplayName should be routed when their URLs are requested
return [
    'templates/render',
    [
        'template' => 'site/template/path',
        'variables' => ['$camelCasedName' => \$this],
    ]
];
PHP,
            'canView' => <<<PHP
if (parent::canView(\$user)) {
    return true;
}
// todo: implement user permissions
return \$user->can('view$this->pluralName');
PHP,
            'canSave' => <<<PHP
if (parent::canSave(\$user)) {
    return true;
}
// todo: implement user permissions
return \$user->can('save$this->pluralName');
PHP,
        'canDuplicate' => <<<PHP
if (parent::canDuplicate(\$user)) {
    return true;
}
// todo: implement user permissions
return \$user->can('save$this->pluralName');
PHP,
            'canDelete' => <<<PHP
if (parent::canSave(\$user)) {
    return true;
}
// todo: implement user permissions
return \$user->can('delete$this->pluralName');
PHP,
            'canCreateDrafts' => 'return true;',
            'cpEditUrl' => "return sprintf('$this->pluralKebabCasedName/%s', \$this->getCanonicalId());",
            'getPostEditUrl' => "UrlHelper::cpUrl('$this->pluralKebabCasedName');",
            'prepareEditScreen' => <<<PHP
/** @var Response|CpScreenResponseBehavior \$response */
\$response->crumbs([
    [
        'label' => self::pluralDisplayName(),
        'url' => UrlHelper::cpUrl('$this->pluralKebabCasedName'),
    ],
]);
PHP,
            'afterSave' => <<<PHP
if (!\$this->propagating) {
    // todo: update the `$this->tableName` table
}

parent::afterSave(\$isNew);
PHP,
        ];
    }

    private function writeQueryClass(): void
    {
        $namespace = (new PhpNamespace($this->queryNamespace))
            ->addUse(Craft::class)
            ->addUse(ElementQuery::class);

        $class = $this->createClass($this->queryName, ElementQuery::class, [
            self::CLASS_METHODS => $this->queryClassMethods(),
        ]);
        $namespace->add($class);

        $class->addComment(sprintf('%s query', StringHelper::toTitleCase($this->displayName)));

        $this->writePhpClass($namespace);
    }

    private function queryClassMethods(): array
    {
        return [
            'beforePrepare' => <<<PHP
// todo: join the `$this->tableName` table
// \$this->joinElementTable('$this->tableName');

// todo: apply any custom query params
// ...

return parent::beforePrepare();
PHP,
        ];
    }

    private function writeConditionClass(): void
    {
        $namespace = (new PhpNamespace($this->conditionNamespace))
            ->addUse(Craft::class)
            ->addUse(ElementCondition::class);

        $class = $this->createClass($this->conditionName, ElementCondition::class, [
            self::CLASS_METHODS => $this->conditionClassMethods(),
        ]);
        $namespace->add($class);

        $class->addComment(sprintf('%s condition', StringHelper::toTitleCase($this->displayName)));

        $this->writePhpClass($namespace);
    }

    private function conditionClassMethods(): array
    {
        return [
            'conditionRuleTypes' => <<<PHP
return array_merge(parent::conditionRuleTypes(), [
    // ...
]);
PHP,
        ];
    }

    private function writeIndexTemplate(): void
    {
        $slashedName = addslashes("$this->namespace\\$this->className");
        $contents = <<<TWIG
{% extends '_layouts/elementindex' %}
{% set title = '$this->pluralDisplayName'|t('app') %}
{% set elementType = '$slashedName' %}
{% set canHaveDrafts = true %}

TWIG;
        $this->controller->writeToFile("$this->basePath/templates/$this->pluralKebabCasedName/_index.twig", $contents);
    }
}
