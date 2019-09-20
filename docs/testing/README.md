# Testing

## Introduction
Testing is a crucial part of ensuring that software continues to work 
as the initial design expected. Things often tend to break when changing existing code bases
and often not directly in the places you are working on when errors occur. 

> You can’t sleep well if you are not confident that your last commit didn’t take down the whole application. 

This is where automated testing can help. If done correctly critical parts of 
your site, module and/or plugin can be tested in such a way that ensures you are
notified when anything stops working as expected and before shipping any code to production.
Reducing bugs before they are discovered by clients in production saved you the time
of debugging the bug and communicating with the client, win-win. 

## Craft testing framework
As of 3.2, Craft provides a formalized testing framework that is based on [Codeception](https://codeception.com/) 
and implements the [Yii 2 codeception module](https://codeception.com/for/yii). 

On top of all the tools that Codeception and Yii 2 natively provide for testing. 
Craft adds its own layer of support to ensure Craft specific concepts such as Elements 
& Project config are supported. 

::: tip
Ready to rock? Start by reading a description of what testing is
within [Craft](testing.md) as well as how to test using 
[Codeception](https://codeception.com/docs/01-Introduction). 
Then there is a handy [get started guide](./testing-craft/getting-started.md) 
to setup your test suite. 
:::

