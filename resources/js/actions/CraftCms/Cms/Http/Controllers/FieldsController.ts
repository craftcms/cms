import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \CraftCms\Cms\Http\Controllers\FieldsController::edit
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FieldsController.php:72
* @route '/admin/actions/fields/edit-field'
*/
const edita8774329404dfce80a2bc2455884a381 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edita8774329404dfce80a2bc2455884a381.url(options),
    method: 'get',
})

edita8774329404dfce80a2bc2455884a381.definition = {
    methods: ["get","head"],
    url: '/admin/actions/fields/edit-field',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \CraftCms\Cms\Http\Controllers\FieldsController::edit
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FieldsController.php:72
* @route '/admin/actions/fields/edit-field'
*/
edita8774329404dfce80a2bc2455884a381.url = (options?: RouteQueryOptions) => {
    return edita8774329404dfce80a2bc2455884a381.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\FieldsController::edit
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FieldsController.php:72
* @route '/admin/actions/fields/edit-field'
*/
edita8774329404dfce80a2bc2455884a381.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edita8774329404dfce80a2bc2455884a381.url(options),
    method: 'get',
})

/**
* @see \CraftCms\Cms\Http\Controllers\FieldsController::edit
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FieldsController.php:72
* @route '/admin/actions/fields/edit-field'
*/
edita8774329404dfce80a2bc2455884a381.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: edita8774329404dfce80a2bc2455884a381.url(options),
    method: 'head',
})

/**
* @see \CraftCms\Cms\Http\Controllers\FieldsController::edit
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FieldsController.php:72
* @route '/admin/settings/fields/edit/{fieldId}'
*/
const edit07fc3eb33eac72453afb72a573ed8850 = (args: { fieldId: string | number } | [fieldId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit07fc3eb33eac72453afb72a573ed8850.url(args, options),
    method: 'get',
})

edit07fc3eb33eac72453afb72a573ed8850.definition = {
    methods: ["get","head"],
    url: '/admin/settings/fields/edit/{fieldId}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \CraftCms\Cms\Http\Controllers\FieldsController::edit
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FieldsController.php:72
* @route '/admin/settings/fields/edit/{fieldId}'
*/
edit07fc3eb33eac72453afb72a573ed8850.url = (args: { fieldId: string | number } | [fieldId: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { fieldId: args }
    }

    if (Array.isArray(args)) {
        args = {
            fieldId: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        fieldId: args.fieldId,
    }

    return edit07fc3eb33eac72453afb72a573ed8850.definition.url
            .replace('{fieldId}', parsedArgs.fieldId.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\FieldsController::edit
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FieldsController.php:72
* @route '/admin/settings/fields/edit/{fieldId}'
*/
edit07fc3eb33eac72453afb72a573ed8850.get = (args: { fieldId: string | number } | [fieldId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit07fc3eb33eac72453afb72a573ed8850.url(args, options),
    method: 'get',
})

/**
* @see \CraftCms\Cms\Http\Controllers\FieldsController::edit
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FieldsController.php:72
* @route '/admin/settings/fields/edit/{fieldId}'
*/
edit07fc3eb33eac72453afb72a573ed8850.head = (args: { fieldId: string | number } | [fieldId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: edit07fc3eb33eac72453afb72a573ed8850.url(args, options),
    method: 'head',
})

export const edit = {
    '/admin/actions/fields/edit-field': edita8774329404dfce80a2bc2455884a381,
    '/admin/settings/fields/edit/{fieldId}': edit07fc3eb33eac72453afb72a573ed8850,
}

/**
* @see \CraftCms\Cms\Http\Controllers\FieldsController::renderSettings
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FieldsController.php:87
* @route '/admin/actions/fields/render-settings'
*/
export const renderSettings = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: renderSettings.url(options),
    method: 'post',
})

renderSettings.definition = {
    methods: ["post"],
    url: '/admin/actions/fields/render-settings',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\FieldsController::renderSettings
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FieldsController.php:87
* @route '/admin/actions/fields/render-settings'
*/
renderSettings.url = (options?: RouteQueryOptions) => {
    return renderSettings.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\FieldsController::renderSettings
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FieldsController.php:87
* @route '/admin/actions/fields/render-settings'
*/
renderSettings.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: renderSettings.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\FieldsController::store
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FieldsController.php:136
* @route '/admin/actions/fields/save-field'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/admin/actions/fields/save-field',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\FieldsController::store
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FieldsController.php:136
* @route '/admin/actions/fields/save-field'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\FieldsController::store
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FieldsController.php:136
* @route '/admin/actions/fields/save-field'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\FieldsController::destroy
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FieldsController.php:195
* @route '/admin/actions/fields/delete-field'
*/
export const destroy = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: destroy.url(options),
    method: 'post',
})

destroy.definition = {
    methods: ["post"],
    url: '/admin/actions/fields/delete-field',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\FieldsController::destroy
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FieldsController.php:195
* @route '/admin/actions/fields/delete-field'
*/
destroy.url = (options?: RouteQueryOptions) => {
    return destroy.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\FieldsController::destroy
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FieldsController.php:195
* @route '/admin/actions/fields/delete-field'
*/
destroy.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: destroy.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\FieldsController::renderLayoutComponentSettings
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FieldsController.php:219
* @route '/admin/actions/fields/render-layout-component-settings'
*/
export const renderLayoutComponentSettings = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: renderLayoutComponentSettings.url(options),
    method: 'post',
})

renderLayoutComponentSettings.definition = {
    methods: ["post"],
    url: '/admin/actions/fields/render-layout-component-settings',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\FieldsController::renderLayoutComponentSettings
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FieldsController.php:219
* @route '/admin/actions/fields/render-layout-component-settings'
*/
renderLayoutComponentSettings.url = (options?: RouteQueryOptions) => {
    return renderLayoutComponentSettings.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\FieldsController::renderLayoutComponentSettings
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FieldsController.php:219
* @route '/admin/actions/fields/render-layout-component-settings'
*/
renderLayoutComponentSettings.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: renderLayoutComponentSettings.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\FieldsController::applyLayoutTabSettings
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FieldsController.php:234
* @route '/admin/actions/fields/apply-layout-tab-settings'
*/
export const applyLayoutTabSettings = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: applyLayoutTabSettings.url(options),
    method: 'post',
})

applyLayoutTabSettings.definition = {
    methods: ["post"],
    url: '/admin/actions/fields/apply-layout-tab-settings',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\FieldsController::applyLayoutTabSettings
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FieldsController.php:234
* @route '/admin/actions/fields/apply-layout-tab-settings'
*/
applyLayoutTabSettings.url = (options?: RouteQueryOptions) => {
    return applyLayoutTabSettings.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\FieldsController::applyLayoutTabSettings
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FieldsController.php:234
* @route '/admin/actions/fields/apply-layout-tab-settings'
*/
applyLayoutTabSettings.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: applyLayoutTabSettings.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\FieldsController::applyLayoutElementSettings
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FieldsController.php:245
* @route '/admin/actions/fields/apply-layout-element-settings'
*/
export const applyLayoutElementSettings = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: applyLayoutElementSettings.url(options),
    method: 'post',
})

applyLayoutElementSettings.definition = {
    methods: ["post"],
    url: '/admin/actions/fields/apply-layout-element-settings',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\FieldsController::applyLayoutElementSettings
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FieldsController.php:245
* @route '/admin/actions/fields/apply-layout-element-settings'
*/
applyLayoutElementSettings.url = (options?: RouteQueryOptions) => {
    return applyLayoutElementSettings.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\FieldsController::applyLayoutElementSettings
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FieldsController.php:245
* @route '/admin/actions/fields/apply-layout-element-settings'
*/
applyLayoutElementSettings.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: applyLayoutElementSettings.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\FieldsController::renderCardPreview
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FieldsController.php:282
* @route '/admin/actions/fields/render-card-preview'
*/
export const renderCardPreview = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: renderCardPreview.url(options),
    method: 'post',
})

renderCardPreview.definition = {
    methods: ["post"],
    url: '/admin/actions/fields/render-card-preview',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\FieldsController::renderCardPreview
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FieldsController.php:282
* @route '/admin/actions/fields/render-card-preview'
*/
renderCardPreview.url = (options?: RouteQueryOptions) => {
    return renderCardPreview.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\FieldsController::renderCardPreview
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FieldsController.php:282
* @route '/admin/actions/fields/render-card-preview'
*/
renderCardPreview.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: renderCardPreview.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\FieldsController::tableData
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FieldsController.php:319
* @route '/admin/actions/fields/table-data'
*/
export const tableData = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: tableData.url(options),
    method: 'get',
})

tableData.definition = {
    methods: ["get","head"],
    url: '/admin/actions/fields/table-data',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \CraftCms\Cms\Http\Controllers\FieldsController::tableData
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FieldsController.php:319
* @route '/admin/actions/fields/table-data'
*/
tableData.url = (options?: RouteQueryOptions) => {
    return tableData.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\FieldsController::tableData
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FieldsController.php:319
* @route '/admin/actions/fields/table-data'
*/
tableData.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: tableData.url(options),
    method: 'get',
})

/**
* @see \CraftCms\Cms\Http\Controllers\FieldsController::tableData
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FieldsController.php:319
* @route '/admin/actions/fields/table-data'
*/
tableData.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: tableData.url(options),
    method: 'head',
})

/**
* @see \CraftCms\Cms\Http\Controllers\FieldsController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FieldsController.php:58
* @route '/admin/settings/fields'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/admin/settings/fields',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \CraftCms\Cms\Http\Controllers\FieldsController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FieldsController.php:58
* @route '/admin/settings/fields'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\FieldsController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FieldsController.php:58
* @route '/admin/settings/fields'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \CraftCms\Cms\Http\Controllers\FieldsController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FieldsController.php:58
* @route '/admin/settings/fields'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \CraftCms\Cms\Http\Controllers\FieldsController::create
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FieldsController.php:65
* @route '/admin/settings/fields/new'
*/
export const create = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

create.definition = {
    methods: ["get","head"],
    url: '/admin/settings/fields/new',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \CraftCms\Cms\Http\Controllers\FieldsController::create
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FieldsController.php:65
* @route '/admin/settings/fields/new'
*/
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\FieldsController::create
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FieldsController.php:65
* @route '/admin/settings/fields/new'
*/
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

/**
* @see \CraftCms\Cms\Http\Controllers\FieldsController::create
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FieldsController.php:65
* @route '/admin/settings/fields/new'
*/
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
})

const FieldsController = { edit, renderSettings, store, destroy, renderLayoutComponentSettings, applyLayoutTabSettings, applyLayoutElementSettings, renderCardPreview, tableData, index, create }

export default FieldsController