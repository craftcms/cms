import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../../../wayfinder'
/**
* @see \CraftCms\Cms\Http\Controllers\Settings\EntryTypesController::tableData
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/EntryTypesController.php:185
* @route '/admin/actions/entry-types/table-data'
*/
export const tableData = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: tableData.url(options),
    method: 'get',
})

tableData.definition = {
    methods: ["get","head"],
    url: '/admin/actions/entry-types/table-data',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\EntryTypesController::tableData
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/EntryTypesController.php:185
* @route '/admin/actions/entry-types/table-data'
*/
tableData.url = (options?: RouteQueryOptions) => {
    return tableData.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\EntryTypesController::tableData
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/EntryTypesController.php:185
* @route '/admin/actions/entry-types/table-data'
*/
tableData.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: tableData.url(options),
    method: 'get',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\EntryTypesController::tableData
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/EntryTypesController.php:185
* @route '/admin/actions/entry-types/table-data'
*/
tableData.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: tableData.url(options),
    method: 'head',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\EntryTypesController::edit
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/EntryTypesController.php:91
* @route '/admin/actions/entry-types/edit/{entryType}'
*/
const editd7498d4f51298bfd43723ce318c978ae = (args: { entryType: string | number | { id: string | number } } | [entryType: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: editd7498d4f51298bfd43723ce318c978ae.url(args, options),
    method: 'get',
})

editd7498d4f51298bfd43723ce318c978ae.definition = {
    methods: ["get","head"],
    url: '/admin/actions/entry-types/edit/{entryType}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\EntryTypesController::edit
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/EntryTypesController.php:91
* @route '/admin/actions/entry-types/edit/{entryType}'
*/
editd7498d4f51298bfd43723ce318c978ae.url = (args: { entryType: string | number | { id: string | number } } | [entryType: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { entryType: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { entryType: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            entryType: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        entryType: typeof args.entryType === 'object'
        ? args.entryType.id
        : args.entryType,
    }

    return editd7498d4f51298bfd43723ce318c978ae.definition.url
            .replace('{entryType}', parsedArgs.entryType.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\EntryTypesController::edit
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/EntryTypesController.php:91
* @route '/admin/actions/entry-types/edit/{entryType}'
*/
editd7498d4f51298bfd43723ce318c978ae.get = (args: { entryType: string | number | { id: string | number } } | [entryType: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: editd7498d4f51298bfd43723ce318c978ae.url(args, options),
    method: 'get',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\EntryTypesController::edit
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/EntryTypesController.php:91
* @route '/admin/actions/entry-types/edit/{entryType}'
*/
editd7498d4f51298bfd43723ce318c978ae.head = (args: { entryType: string | number | { id: string | number } } | [entryType: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: editd7498d4f51298bfd43723ce318c978ae.url(args, options),
    method: 'head',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\EntryTypesController::edit
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/EntryTypesController.php:91
* @route '/admin/settings/entry-types/{entryType}'
*/
const edit25e1f42aec008916c7d3a3c3600ab7d3 = (args: { entryType: string | number | { id: string | number } } | [entryType: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit25e1f42aec008916c7d3a3c3600ab7d3.url(args, options),
    method: 'get',
})

edit25e1f42aec008916c7d3a3c3600ab7d3.definition = {
    methods: ["get","head"],
    url: '/admin/settings/entry-types/{entryType}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\EntryTypesController::edit
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/EntryTypesController.php:91
* @route '/admin/settings/entry-types/{entryType}'
*/
edit25e1f42aec008916c7d3a3c3600ab7d3.url = (args: { entryType: string | number | { id: string | number } } | [entryType: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { entryType: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { entryType: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            entryType: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        entryType: typeof args.entryType === 'object'
        ? args.entryType.id
        : args.entryType,
    }

    return edit25e1f42aec008916c7d3a3c3600ab7d3.definition.url
            .replace('{entryType}', parsedArgs.entryType.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\EntryTypesController::edit
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/EntryTypesController.php:91
* @route '/admin/settings/entry-types/{entryType}'
*/
edit25e1f42aec008916c7d3a3c3600ab7d3.get = (args: { entryType: string | number | { id: string | number } } | [entryType: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit25e1f42aec008916c7d3a3c3600ab7d3.url(args, options),
    method: 'get',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\EntryTypesController::edit
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/EntryTypesController.php:91
* @route '/admin/settings/entry-types/{entryType}'
*/
edit25e1f42aec008916c7d3a3c3600ab7d3.head = (args: { entryType: string | number | { id: string | number } } | [entryType: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: edit25e1f42aec008916c7d3a3c3600ab7d3.url(args, options),
    method: 'head',
})

export const edit = {
    '/admin/actions/entry-types/edit/{entryType}': editd7498d4f51298bfd43723ce318c978ae,
    '/admin/settings/entry-types/{entryType}': edit25e1f42aec008916c7d3a3c3600ab7d3,
}

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\EntryTypesController::create
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/EntryTypesController.php:69
* @route '/admin/actions/entry-types/new'
*/
const create661e2cc8492649ff61e60dfed6574b7d = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create661e2cc8492649ff61e60dfed6574b7d.url(options),
    method: 'get',
})

create661e2cc8492649ff61e60dfed6574b7d.definition = {
    methods: ["get","head"],
    url: '/admin/actions/entry-types/new',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\EntryTypesController::create
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/EntryTypesController.php:69
* @route '/admin/actions/entry-types/new'
*/
create661e2cc8492649ff61e60dfed6574b7d.url = (options?: RouteQueryOptions) => {
    return create661e2cc8492649ff61e60dfed6574b7d.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\EntryTypesController::create
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/EntryTypesController.php:69
* @route '/admin/actions/entry-types/new'
*/
create661e2cc8492649ff61e60dfed6574b7d.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create661e2cc8492649ff61e60dfed6574b7d.url(options),
    method: 'get',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\EntryTypesController::create
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/EntryTypesController.php:69
* @route '/admin/actions/entry-types/new'
*/
create661e2cc8492649ff61e60dfed6574b7d.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create661e2cc8492649ff61e60dfed6574b7d.url(options),
    method: 'head',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\EntryTypesController::create
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/EntryTypesController.php:69
* @route '/admin/settings/entry-types/new'
*/
const createce40b2584bc1252430852e04086b043f = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: createce40b2584bc1252430852e04086b043f.url(options),
    method: 'get',
})

createce40b2584bc1252430852e04086b043f.definition = {
    methods: ["get","head"],
    url: '/admin/settings/entry-types/new',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\EntryTypesController::create
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/EntryTypesController.php:69
* @route '/admin/settings/entry-types/new'
*/
createce40b2584bc1252430852e04086b043f.url = (options?: RouteQueryOptions) => {
    return createce40b2584bc1252430852e04086b043f.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\EntryTypesController::create
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/EntryTypesController.php:69
* @route '/admin/settings/entry-types/new'
*/
createce40b2584bc1252430852e04086b043f.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: createce40b2584bc1252430852e04086b043f.url(options),
    method: 'get',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\EntryTypesController::create
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/EntryTypesController.php:69
* @route '/admin/settings/entry-types/new'
*/
createce40b2584bc1252430852e04086b043f.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: createce40b2584bc1252430852e04086b043f.url(options),
    method: 'head',
})

export const create = {
    '/admin/actions/entry-types/new': create661e2cc8492649ff61e60dfed6574b7d,
    '/admin/settings/entry-types/new': createce40b2584bc1252430852e04086b043f,
}

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\EntryTypesController::store
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/EntryTypesController.php:212
* @route '/admin/actions/entry-types/save'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/admin/actions/entry-types/save',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\EntryTypesController::store
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/EntryTypesController.php:212
* @route '/admin/actions/entry-types/save'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\EntryTypesController::store
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/EntryTypesController.php:212
* @route '/admin/actions/entry-types/save'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\EntryTypesController::destroy
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/EntryTypesController.php:265
* @route '/admin/actions/entry-types/delete'
*/
export const destroy = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: destroy.url(options),
    method: 'post',
})

destroy.definition = {
    methods: ["post"],
    url: '/admin/actions/entry-types/delete',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\EntryTypesController::destroy
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/EntryTypesController.php:265
* @route '/admin/actions/entry-types/delete'
*/
destroy.url = (options?: RouteQueryOptions) => {
    return destroy.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\EntryTypesController::destroy
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/EntryTypesController.php:265
* @route '/admin/actions/entry-types/delete'
*/
destroy.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: destroy.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\EntryTypesController::renderOverrideSettings
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/EntryTypesController.php:290
* @route '/admin/actions/entry-types/render-override-settings'
*/
export const renderOverrideSettings = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: renderOverrideSettings.url(options),
    method: 'post',
})

renderOverrideSettings.definition = {
    methods: ["post"],
    url: '/admin/actions/entry-types/render-override-settings',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\EntryTypesController::renderOverrideSettings
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/EntryTypesController.php:290
* @route '/admin/actions/entry-types/render-override-settings'
*/
renderOverrideSettings.url = (options?: RouteQueryOptions) => {
    return renderOverrideSettings.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\EntryTypesController::renderOverrideSettings
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/EntryTypesController.php:290
* @route '/admin/actions/entry-types/render-override-settings'
*/
renderOverrideSettings.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: renderOverrideSettings.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\EntryTypesController::applyOverrideSettings
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/EntryTypesController.php:315
* @route '/admin/actions/entry-types/apply-override-settings'
*/
export const applyOverrideSettings = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: applyOverrideSettings.url(options),
    method: 'post',
})

applyOverrideSettings.definition = {
    methods: ["post"],
    url: '/admin/actions/entry-types/apply-override-settings',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\EntryTypesController::applyOverrideSettings
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/EntryTypesController.php:315
* @route '/admin/actions/entry-types/apply-override-settings'
*/
applyOverrideSettings.url = (options?: RouteQueryOptions) => {
    return applyOverrideSettings.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\EntryTypesController::applyOverrideSettings
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/EntryTypesController.php:315
* @route '/admin/actions/entry-types/apply-override-settings'
*/
applyOverrideSettings.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: applyOverrideSettings.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\EntryTypesController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/EntryTypesController.php:64
* @route '/admin/settings/entry-types'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/admin/settings/entry-types',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\EntryTypesController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/EntryTypesController.php:64
* @route '/admin/settings/entry-types'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\EntryTypesController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/EntryTypesController.php:64
* @route '/admin/settings/entry-types'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Settings\EntryTypesController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Settings/EntryTypesController.php:64
* @route '/admin/settings/entry-types'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

const EntryTypesController = { tableData, edit, create, store, destroy, renderOverrideSettings, applyOverrideSettings, index }

export default EntryTypesController