# Front End Resources

If your plugin has any CSS, Javascript, images, or other front-end resources, you can place them in a resources/ subfolder within your plugin’s folder.

Craft provides a helper function, `UrlHelper::getResourceUrl('path/to/file.ext')`, which returns the URL to a resource file. Templates have a similar function: `{{ resourceUrl('path/to/file.ext') }}`. The URL returned by these functions will work even if the craft/ folder is placed above the web root.

By default, Craft will search for the resource file in its own craft/app/resources/ folder. If it can’t find the file there, it will check if the first segment of the resource path is set to a plugin handle. If it is, Craft will look for the resource file within that plugin’s resources/ folder.

So if an image were located at craft/plugins/cocktailrecipes/resources/images/gin.png, it would be accessible via `UrlHelper::getResourceUrl('cocktailrecipes/images/gin.png')` and `{{ resourceUrl('cocktailrecipes/images/gin.png') }}`.

[TemplatesService](https://docs.craftcms.com/api/v2/services/TemplatesService.html) also provides the handy functions, `includeCssResource('path/to/file.css')` and `includeJsResource('path/to/file.js')`, which queue up CSS and JS files to be included in the template within `<link>` and `<script>` tags. These functions also have template tag counterparts, `{% includeCssResource "path/to/file.css" %}` and `{% includeJsResource "path/to/file.js" %}`.
