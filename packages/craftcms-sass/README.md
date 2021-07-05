# Sass Mixins for Craft CMS

Plugins that need to extend the Craft CMS Control Panel can use this library and import `_mixins.sass` from their Sass files, giving them the same mixins and variables used by core Control Panel templates.

## Installation

Installation via npm is recommended.

    npm install craftcms-sass --save-dev

## Usage

Import the mixins into your Sass files with the `@import` directive:

```scss
@import "../node_modules/craftcms-sass/mixins";
```

(The exact path will vary depending on where your `node_modules` folder is in relation to your Sass file.)
