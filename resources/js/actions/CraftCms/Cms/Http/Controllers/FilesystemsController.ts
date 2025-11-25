import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \CraftCms\Cms\Http\Controllers\FilesystemsController::edit
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FilesystemsController.php:49
* @route '/admin/actions/fs/edit'
*/
const editc6e290cdf9b846351f6e033526f91070 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: editc6e290cdf9b846351f6e033526f91070.url(options),
    method: 'get',
})

editc6e290cdf9b846351f6e033526f91070.definition = {
    methods: ["get","head"],
    url: '/admin/actions/fs/edit',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \CraftCms\Cms\Http\Controllers\FilesystemsController::edit
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FilesystemsController.php:49
* @route '/admin/actions/fs/edit'
*/
editc6e290cdf9b846351f6e033526f91070.url = (options?: RouteQueryOptions) => {
    return editc6e290cdf9b846351f6e033526f91070.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\FilesystemsController::edit
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FilesystemsController.php:49
* @route '/admin/actions/fs/edit'
*/
editc6e290cdf9b846351f6e033526f91070.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: editc6e290cdf9b846351f6e033526f91070.url(options),
    method: 'get',
})

/**
* @see \CraftCms\Cms\Http\Controllers\FilesystemsController::edit
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FilesystemsController.php:49
* @route '/admin/actions/fs/edit'
*/
editc6e290cdf9b846351f6e033526f91070.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: editc6e290cdf9b846351f6e033526f91070.url(options),
    method: 'head',
})

/**
* @see \CraftCms\Cms\Http\Controllers\FilesystemsController::edit
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FilesystemsController.php:49
* @route '/admin/settings/filesystems/{handle}/edit'
*/
const edit03f461b3a3777918fe456fe90fc43126 = (args: { handle: string | number } | [handle: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit03f461b3a3777918fe456fe90fc43126.url(args, options),
    method: 'get',
})

edit03f461b3a3777918fe456fe90fc43126.definition = {
    methods: ["get","head"],
    url: '/admin/settings/filesystems/{handle}/edit',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \CraftCms\Cms\Http\Controllers\FilesystemsController::edit
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FilesystemsController.php:49
* @route '/admin/settings/filesystems/{handle}/edit'
*/
edit03f461b3a3777918fe456fe90fc43126.url = (args: { handle: string | number } | [handle: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { handle: args }
    }

    if (Array.isArray(args)) {
        args = {
            handle: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        handle: args.handle,
    }

    return edit03f461b3a3777918fe456fe90fc43126.definition.url
            .replace('{handle}', parsedArgs.handle.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\FilesystemsController::edit
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FilesystemsController.php:49
* @route '/admin/settings/filesystems/{handle}/edit'
*/
edit03f461b3a3777918fe456fe90fc43126.get = (args: { handle: string | number } | [handle: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit03f461b3a3777918fe456fe90fc43126.url(args, options),
    method: 'get',
})

/**
* @see \CraftCms\Cms\Http\Controllers\FilesystemsController::edit
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FilesystemsController.php:49
* @route '/admin/settings/filesystems/{handle}/edit'
*/
edit03f461b3a3777918fe456fe90fc43126.head = (args: { handle: string | number } | [handle: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: edit03f461b3a3777918fe456fe90fc43126.url(args, options),
    method: 'head',
})

export const edit = {
    '/admin/actions/fs/edit': editc6e290cdf9b846351f6e033526f91070,
    '/admin/settings/filesystems/{handle}/edit': edit03f461b3a3777918fe456fe90fc43126,
}

/**
* @see \CraftCms\Cms\Http\Controllers\FilesystemsController::save
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FilesystemsController.php:123
* @route '/admin/actions/fs/save'
*/
const save066e088ccd27a8d607b98a2de3264f78 = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: save066e088ccd27a8d607b98a2de3264f78.url(options),
    method: 'post',
})

save066e088ccd27a8d607b98a2de3264f78.definition = {
    methods: ["post"],
    url: '/admin/actions/fs/save',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\FilesystemsController::save
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FilesystemsController.php:123
* @route '/admin/actions/fs/save'
*/
save066e088ccd27a8d607b98a2de3264f78.url = (options?: RouteQueryOptions) => {
    return save066e088ccd27a8d607b98a2de3264f78.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\FilesystemsController::save
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FilesystemsController.php:123
* @route '/admin/actions/fs/save'
*/
save066e088ccd27a8d607b98a2de3264f78.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: save066e088ccd27a8d607b98a2de3264f78.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\FilesystemsController::save
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FilesystemsController.php:123
* @route '/admin/settings/filesystems/{handle}'
*/
const save1c38f1fd403c9012552642cd2f5760c3 = (args: { handle: string | number } | [handle: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: save1c38f1fd403c9012552642cd2f5760c3.url(args, options),
    method: 'post',
})

save1c38f1fd403c9012552642cd2f5760c3.definition = {
    methods: ["post"],
    url: '/admin/settings/filesystems/{handle}',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\FilesystemsController::save
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FilesystemsController.php:123
* @route '/admin/settings/filesystems/{handle}'
*/
save1c38f1fd403c9012552642cd2f5760c3.url = (args: { handle: string | number } | [handle: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { handle: args }
    }

    if (Array.isArray(args)) {
        args = {
            handle: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        handle: args.handle,
    }

    return save1c38f1fd403c9012552642cd2f5760c3.definition.url
            .replace('{handle}', parsedArgs.handle.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\FilesystemsController::save
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FilesystemsController.php:123
* @route '/admin/settings/filesystems/{handle}'
*/
save1c38f1fd403c9012552642cd2f5760c3.post = (args: { handle: string | number } | [handle: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: save1c38f1fd403c9012552642cd2f5760c3.url(args, options),
    method: 'post',
})

export const save = {
    '/admin/actions/fs/save': save066e088ccd27a8d607b98a2de3264f78,
    '/admin/settings/filesystems/{handle}': save1c38f1fd403c9012552642cd2f5760c3,
}

/**
* @see \CraftCms\Cms\Http\Controllers\FilesystemsController::deleteMethod
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FilesystemsController.php:144
* @route '/admin/actions/fs/remove'
*/
export const deleteMethod = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: deleteMethod.url(options),
    method: 'post',
})

deleteMethod.definition = {
    methods: ["post"],
    url: '/admin/actions/fs/remove',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\FilesystemsController::deleteMethod
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FilesystemsController.php:144
* @route '/admin/actions/fs/remove'
*/
deleteMethod.url = (options?: RouteQueryOptions) => {
    return deleteMethod.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\FilesystemsController::deleteMethod
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FilesystemsController.php:144
* @route '/admin/actions/fs/remove'
*/
deleteMethod.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: deleteMethod.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\FilesystemsController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FilesystemsController.php:35
* @route '/admin/settings/filesystems'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/admin/settings/filesystems',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \CraftCms\Cms\Http\Controllers\FilesystemsController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FilesystemsController.php:35
* @route '/admin/settings/filesystems'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\FilesystemsController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FilesystemsController.php:35
* @route '/admin/settings/filesystems'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \CraftCms\Cms\Http\Controllers\FilesystemsController::index
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FilesystemsController.php:35
* @route '/admin/settings/filesystems'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \CraftCms\Cms\Http\Controllers\FilesystemsController::create
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FilesystemsController.php:44
* @route '/admin/settings/filesystems/new'
*/
export const create = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

create.definition = {
    methods: ["get","head"],
    url: '/admin/settings/filesystems/new',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \CraftCms\Cms\Http\Controllers\FilesystemsController::create
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FilesystemsController.php:44
* @route '/admin/settings/filesystems/new'
*/
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\FilesystemsController::create
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FilesystemsController.php:44
* @route '/admin/settings/filesystems/new'
*/
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

/**
* @see \CraftCms\Cms\Http\Controllers\FilesystemsController::create
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/FilesystemsController.php:44
* @route '/admin/settings/filesystems/new'
*/
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
})

const FilesystemsController = { edit, save, deleteMethod, index, create, delete: deleteMethod }

export default FilesystemsController