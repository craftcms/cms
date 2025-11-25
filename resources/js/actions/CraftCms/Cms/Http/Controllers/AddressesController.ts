import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../../wayfinder'
/**
* @see \CraftCms\Cms\Http\Controllers\AddressesController::fields
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/AddressesController.php:30
* @route '/admin/actions/addresses/fields'
*/
export const fields = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: fields.url(options),
    method: 'post',
})

fields.definition = {
    methods: ["post"],
    url: '/admin/actions/addresses/fields',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\AddressesController::fields
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/AddressesController.php:30
* @route '/admin/actions/addresses/fields'
*/
fields.url = (options?: RouteQueryOptions) => {
    return fields.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\AddressesController::fields
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/AddressesController.php:30
* @route '/admin/actions/addresses/fields'
*/
fields.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: fields.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\AddressesController::saveFieldLayout
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/AddressesController.php:56
* @route '/admin/actions/addresses/save-field-layout'
*/
export const saveFieldLayout = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: saveFieldLayout.url(options),
    method: 'post',
})

saveFieldLayout.definition = {
    methods: ["post"],
    url: '/admin/actions/addresses/save-field-layout',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\AddressesController::saveFieldLayout
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/AddressesController.php:56
* @route '/admin/actions/addresses/save-field-layout'
*/
saveFieldLayout.url = (options?: RouteQueryOptions) => {
    return saveFieldLayout.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\AddressesController::saveFieldLayout
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/AddressesController.php:56
* @route '/admin/actions/addresses/save-field-layout'
*/
saveFieldLayout.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: saveFieldLayout.url(options),
    method: 'post',
})

const AddressesController = { fields, saveFieldLayout }

export default AddressesController