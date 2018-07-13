# Going Headless

Craft’s templating system isn’t the only way to get your content out of Craft.

If you want to use Craft as a “headless” CMS, meaning it acts as a content API instead of (or in addition to) a regular website, there are a few ways you can go about it.

::: tip
Check out CraftQuest’s [Headless Craft CMS](https://craftquest.io/courses/headless-craft) course to learn more about using Craft as a headless CMS.
:::

## GraphQL

The [CraftQL](https://github.com/markhuot/craftql) plugin by Mark Huot adds a zero-config [GraphQL](https://graphql.org/) server to your Craft installation.

## JSON API

The first-party [Element API](https://github.com/craftcms/element-api) offers a simple way to create a read-only [JSON API](http://jsonapi.org/) for your content.

## REST API

Watch Nate Iler’s [How to Create a Full REST API](http://dotall.com/sessions/how-to-create-a-full-rest-api-with-craft-3) presentation from Dot All 2017 for an in-depth look at how to create a REST API with Craft.

## Something Custom

Modules and plugins can define custom front-end [controllers](https://www.yiiframework.com/doc/guide/2.0/en/structure-controllers) that provide new HTTP endpoints. See [Extending Craft](../extend/README.md) to get started.
