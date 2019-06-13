# Support classes
In order to help testing Craft provides several testing support classes that can be used
to help testing. 

## Arrayable
### `craft\test\mockclasses\arrayable\ExampleArrayble`
`ExampleArrayble` implements the `yii\base\Arrayable` interface. If your module/plugin is dependant
on undertaking an action on/with an `arrayable` you can use this class. 

## Components
### `craft\test\mockclasses\components\ComponentExample`
`ComponentExample` implements the `craft\base\ComponentInterface` interface. If your module/plugin 
requires a class that implements this interface use this one. 

### `craft\test\mockclasses\components\ExtendedComponentExample`
`ExtendedComponentExample` implements the `craft\base\ComponentInterface` interface however it does so via 
extending `craft\test\mockclasses\components\ComponentExample`. 

## Controller
### `craft\test\mockclasses\controllers\TestController`
`TestController` extends `craft\test\mockclasses\controllers\TestController`. If you need to perform
actions on any instance of `craft\web\Controller` this can be used. 

## Model
### `craft\test\mockclasses\models\ExampleModel`
`ExampleModel` implements the `craft\base\Model` with a `public $exampleParam` and a 
`public $exampleDateParam` which is linked to the `datetimeAttributes()` method. 

## Serializable
### `craft\test\mockclasses\serializable\Serializable`
A class the implements the `craft\base\Serializable` interface. 


## Other classes
### `craft\test\mockclasses\ToString`
`ToString` implements the `craft\base\Serializable` interface. 

### `craft\test\mockclasses\TwigExtension`
`TwigExtension` is a twig extension that extends the `Twig\Extension\AbstractExtension`.
 

