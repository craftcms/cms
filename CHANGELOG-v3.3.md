# Running Release Notes for Craft CMS 3.3

## Changed
- You can now use handles in Global set reference tags. ([#4645](https://github.com/craftcms/cms/issues/4645))
- Improved the `svg()` function to allow custom elements. ([#3937](https://github.com/craftcms/cms/issues/3937))

## Deprecated
- Passing a `string $class` argument into twig `svg()` function is deprecated. Use the `|attr` filter instead. 
