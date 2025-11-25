import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../../../wayfinder'
/**
* @see \CraftCms\Cms\Http\Controllers\Settings\SectionsController::tableData
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/SectionsController.php:191
* @route '/admin/actions/sections/table-data'
*/
export const tableData = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: tableData.url(options),
    method: 'get',
})

tableData.definition = {
    methods: ["get","head"],
    url: '/admin/actions/sections/table-data',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\SectionsController::tableData
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/SectionsController.php:191
* @route '/admin/actions/sections/table-data'
*/
tableData.url = (options?: RouteQueryOptions) => {
    return tableData.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\SectionsController::tableData
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/SectionsController.php:191
* @route '/admin/actions/sections/table-data'
*/
tableData.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: tableData.url(options),
    method: 'get',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\SectionsController::tableData
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/SectionsController.php:191
* @route '/admin/actions/sections/table-data'
*/
tableData.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: tableData.url(options),
    method: 'head',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\SectionsController::edit
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/SectionsController.php:76
* @route '/admin/actions/sections/edit/{section}'
*/
const editfdacbcd9a811b1db4b001774593f6c14 = (args: { section: string | number | { id: string | number } } | [section: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: editfdacbcd9a811b1db4b001774593f6c14.url(args, options),
    method: 'get',
})

editfdacbcd9a811b1db4b001774593f6c14.definition = {
    methods: ["get","head"],
    url: '/admin/actions/sections/edit/{section}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\SectionsController::edit
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/SectionsController.php:76
* @route '/admin/actions/sections/edit/{section}'
*/
editfdacbcd9a811b1db4b001774593f6c14.url = (args: { section: string | number | { id: string | number } } | [section: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { section: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { section: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            section: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        section: typeof args.section === 'object'
        ? args.section.id
        : args.section,
    }

    return editfdacbcd9a811b1db4b001774593f6c14.definition.url
            .replace('{section}', parsedArgs.section.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\SectionsController::edit
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/SectionsController.php:76
* @route '/admin/actions/sections/edit/{section}'
*/
editfdacbcd9a811b1db4b001774593f6c14.get = (args: { section: string | number | { id: string | number } } | [section: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: editfdacbcd9a811b1db4b001774593f6c14.url(args, options),
    method: 'get',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\SectionsController::edit
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/SectionsController.php:76
* @route '/admin/actions/sections/edit/{section}'
*/
editfdacbcd9a811b1db4b001774593f6c14.head = (args: { section: string | number | { id: string | number } } | [section: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: editfdacbcd9a811b1db4b001774593f6c14.url(args, options),
    method: 'head',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\SectionsController::edit
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/SectionsController.php:76
* @route '/admin/settings/sections/{section}'
*/
const editc6f3ecea2e40726bfd8a3197589451a4 = (args: { section: string | number | { id: string | number } } | [section: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: editc6f3ecea2e40726bfd8a3197589451a4.url(args, options),
    method: 'get',
})

editc6f3ecea2e40726bfd8a3197589451a4.definition = {
    methods: ["get","head"],
    url: '/admin/settings/sections/{section}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\SectionsController::edit
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/SectionsController.php:76
* @route '/admin/settings/sections/{section}'
*/
editc6f3ecea2e40726bfd8a3197589451a4.url = (args: { section: string | number | { id: string | number } } | [section: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { section: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { section: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            section: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        section: typeof args.section === 'object'
        ? args.section.id
        : args.section,
    }

    return editc6f3ecea2e40726bfd8a3197589451a4.definition.url
            .replace('{section}', parsedArgs.section.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\SectionsController::edit
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/SectionsController.php:76
* @route '/admin/settings/sections/{section}'
*/
editc6f3ecea2e40726bfd8a3197589451a4.get = (args: { section: string | number | { id: string | number } } | [section: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: editc6f3ecea2e40726bfd8a3197589451a4.url(args, options),
    method: 'get',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\SectionsController::edit
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/SectionsController.php:76
* @route '/admin/settings/sections/{section}'
*/
editc6f3ecea2e40726bfd8a3197589451a4.head = (args: { section: string | number | { id: string | number } } | [section: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: editc6f3ecea2e40726bfd8a3197589451a4.url(args, options),
    method: 'head',
})

export const edit = {
    '/admin/actions/sections/edit/{section}': editfdacbcd9a811b1db4b001774593f6c14,
    '/admin/settings/sections/{section}': editc6f3ecea2e40726bfd8a3197589451a4,
}

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\SectionsController::store
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/SectionsController.php:116
* @route '/admin/actions/sections/save-section'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/admin/actions/sections/save-section',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\SectionsController::store
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/SectionsController.php:116
* @route '/admin/actions/sections/save-section'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\SectionsController::store
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/SectionsController.php:116
* @route '/admin/actions/sections/save-section'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\SectionsController::destroy
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/SectionsController.php:180
* @route '/admin/actions/sections/delete-section'
*/
export const destroy = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: destroy.url(options),
    method: 'post',
})

destroy.definition = {
    methods: ["post"],
    url: '/admin/actions/sections/delete-section',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\SectionsController::destroy
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/SectionsController.php:180
* @route '/admin/actions/sections/delete-section'
*/
destroy.url = (options?: RouteQueryOptions) => {
    return destroy.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\SectionsController::destroy
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/SectionsController.php:180
* @route '/admin/actions/sections/delete-section'
*/
destroy.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: destroy.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\SectionsController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/SectionsController.php:40
* @route '/admin/settings/sections'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/admin/settings/sections',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\SectionsController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/SectionsController.php:40
* @route '/admin/settings/sections'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\SectionsController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/SectionsController.php:40
* @route '/admin/settings/sections'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\SectionsController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/SectionsController.php:40
* @route '/admin/settings/sections'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\SectionsController::create
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/SectionsController.php:47
* @route '/admin/settings/sections/new'
*/
export const create = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

create.definition = {
    methods: ["get","head"],
    url: '/admin/settings/sections/new',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\SectionsController::create
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/SectionsController.php:47
* @route '/admin/settings/sections/new'
*/
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\SectionsController::create
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/SectionsController.php:47
* @route '/admin/settings/sections/new'
*/
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\SectionsController::create
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/SectionsController.php:47
* @route '/admin/settings/sections/new'
*/
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
})

const SectionsController = { tableData, edit, store, destroy, index, create }

export default SectionsController