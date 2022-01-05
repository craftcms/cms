# Garnish

[![GitHub release](https://img.shields.io/github/release/pixelandtonic/garnishjs.svg?maxAge=3600)](https://github.com/pixelandtonic/garnishjs/releases)
[![CircleCI](https://img.shields.io/circleci/project/pixelandtonic/garnishjs.svg?maxAge=3600)](https://circleci.com/gh/pixelandtonic/garnishjs)
[![Coverage Status](https://coveralls.io/repos/github/pixelandtonic/garnishjs/badge.svg)](https://coveralls.io/github/pixelandtonic/garnishjs)
[![license](https://img.shields.io/github/license/pixelandtonic/garnishjs.svg?maxAge=3600)](LICENSE)

*Garnish UI Toolkit*

## Installation

You can download the latest version of [garnishjs on GitHub](https://github.com/pixelandtonic/garnishjs/releases/latest).

To install via npm:

```bash
npm install garnishjs
```

To install via bower:

```bash
bower install garnishjs
```

## Building

To build, run `gulp build`.

Use `-d` or `--dest` options to customize the destination:

	gulp build --dest=/path/to/dest
	gulp build -d=/path/to/dest
	
Use `-v` or `--version` options to customize the version:

	gulp build --version=1.0.0
	gulp build -v=1.0.0

To watch, run `gulp watch`.

## Testing

To test, run `gulp test`.

To watch and test, run `gulp watch --test`

## UI Elements
### Disclosure
This element should be used in instances where a trigger button shows or hides content. 

Some possible applications include accordion menus, navigation dropdown menus, etc.

To create a disclosure element, use a button with the following properties:
 - An `aria-controls` attribute referencing the ID of the element to be toggled
 - An `aria-haspopup` attribute set to `"true"`.
 - An `aria-expanded` attribute set to either `"true"` or `"false"`.
 - A `data-disclosure-trigger` attribute is used to find and instantiate the UI element


```html
<button aria-controls="disclosure" aria-expanded="false" aria-haspopup="true" data-disclosure-trigger>Open Menu</button>

<div id="disclosure">
	This is the content you want to reveal.
</div>
```
#### Positioning
The disclosure container is positioned absolutely with respect to the trigger element. Because of this, both the disclosure container and the trigger need to be contained inside of a relatively positioned wrapper element.
This element needs to have the attribute `data-wrapper`.

**Note that this is different from the `CustomSelect` element, where dropdowns are positioned relative to the document.**

You can change the horizontal alignment of the disclosure by adding a `data-align` attribute with one of the following values: `left`, `center`, or `right`.

##### Adjusting positioning
You may need to align the disclosure menu positioning to a different element **inside** of the trigger.

In cases like this add a `data-align-to` attribute to the disclosure menu with the selector of the element you want to align it with.
## License

Garnish is available under the [MIT license](LICENSE).
