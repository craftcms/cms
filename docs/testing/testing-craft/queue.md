# Queue
Queueing is a crucial part of many Craft apps. The Craft queue allows you to 
offload many resource intensive or long-running tasks/actions to a seperate process. 
Because of this added support is provided to help test this part of your module/plugin. 

## Running the Queue
Craft provides a simple way to run a queue and thus test your jobs. Queue testing can be performed from your
unit tests. Firstly you need to ensure that your test class has a `$tester` property. 
Once this class property is declared you can call the following method: 

```php
$this->tester->runQueue(MyJob::class, [
    'param1' => 'value',
    'param2' => 'value2'
]);
```
- The first argument is the class of your Job. 
- The second argument is any arguments that must be passed into your job. 

::: tip
Underneath Craft simply runs your job via `Craft::$app->queue`. All methods and actions your job should perform on 
i.e. the database will thus be performed as normal. 
:::


## Checking queue data
// TODO: Implement assert methods for Queue and document these. 
