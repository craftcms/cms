import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../../../wayfinder'
/**
* @see \CraftCms\Cms\Http\Controllers\Entries\CreateEntryController::__invoke
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Entries/CreateEntryController.php:40
* @route '/admin/actions/entries/create'
*/
const CreateEntryControllereceaa511c1a4e7bb5dd647f99d9cf1c8 = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: CreateEntryControllereceaa511c1a4e7bb5dd647f99d9cf1c8.url(options),
    method: 'post',
})

CreateEntryControllereceaa511c1a4e7bb5dd647f99d9cf1c8.definition = {
    methods: ["post"],
    url: '/admin/actions/entries/create',
} satisfies RouteDefinition<["post"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Entries\CreateEntryController::__invoke
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Entries/CreateEntryController.php:40
* @route '/admin/actions/entries/create'
*/
CreateEntryControllereceaa511c1a4e7bb5dd647f99d9cf1c8.url = (options?: RouteQueryOptions) => {
    return CreateEntryControllereceaa511c1a4e7bb5dd647f99d9cf1c8.definition.url + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Entries\CreateEntryController::__invoke
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Entries/CreateEntryController.php:40
* @route '/admin/actions/entries/create'
*/
CreateEntryControllereceaa511c1a4e7bb5dd647f99d9cf1c8.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: CreateEntryControllereceaa511c1a4e7bb5dd647f99d9cf1c8.url(options),
    method: 'post',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Entries\CreateEntryController::__invoke
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Entries/CreateEntryController.php:40
* @route '/admin/entries/{section}/new'
*/
const CreateEntryControllera8480f49557601a276bb03eb660dc187 = (args: { section: string | number } | [section: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: CreateEntryControllera8480f49557601a276bb03eb660dc187.url(args, options),
    method: 'get',
})

CreateEntryControllera8480f49557601a276bb03eb660dc187.definition = {
    methods: ["get","head"],
    url: '/admin/entries/{section}/new',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Entries\CreateEntryController::__invoke
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Entries/CreateEntryController.php:40
* @route '/admin/entries/{section}/new'
*/
CreateEntryControllera8480f49557601a276bb03eb660dc187.url = (args: { section: string | number } | [section: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { section: args }
    }

    if (Array.isArray(args)) {
        args = {
            section: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        section: args.section,
    }

    return CreateEntryControllera8480f49557601a276bb03eb660dc187.definition.url
            .replace('{section}', parsedArgs.section.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Entries\CreateEntryController::__invoke
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Entries/CreateEntryController.php:40
* @route '/admin/entries/{section}/new'
*/
CreateEntryControllera8480f49557601a276bb03eb660dc187.get = (args: { section: string | number } | [section: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: CreateEntryControllera8480f49557601a276bb03eb660dc187.url(args, options),
    method: 'get',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Entries\CreateEntryController::__invoke
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Entries/CreateEntryController.php:40
* @route '/admin/entries/{section}/new'
*/
CreateEntryControllera8480f49557601a276bb03eb660dc187.head = (args: { section: string | number } | [section: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: CreateEntryControllera8480f49557601a276bb03eb660dc187.url(args, options),
    method: 'head',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Entries\CreateEntryController::__invoke
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Entries/CreateEntryController.php:40
* @route '/admin/content/{section}/new'
*/
const CreateEntryController110d011d4d1f82c16ff1aa486f8df83c = (args: { section: string | number } | [section: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: CreateEntryController110d011d4d1f82c16ff1aa486f8df83c.url(args, options),
    method: 'get',
})

CreateEntryController110d011d4d1f82c16ff1aa486f8df83c.definition = {
    methods: ["get","head"],
    url: '/admin/content/{section}/new',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \CraftCms\Cms\Http\Controllers\Entries\CreateEntryController::__invoke
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Entries/CreateEntryController.php:40
* @route '/admin/content/{section}/new'
*/
CreateEntryController110d011d4d1f82c16ff1aa486f8df83c.url = (args: { section: string | number } | [section: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { section: args }
    }

    if (Array.isArray(args)) {
        args = {
            section: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        section: args.section,
    }

    return CreateEntryController110d011d4d1f82c16ff1aa486f8df83c.definition.url
            .replace('{section}', parsedArgs.section.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \CraftCms\Cms\Http\Controllers\Entries\CreateEntryController::__invoke
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Entries/CreateEntryController.php:40
* @route '/admin/content/{section}/new'
*/
CreateEntryController110d011d4d1f82c16ff1aa486f8df83c.get = (args: { section: string | number } | [section: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: CreateEntryController110d011d4d1f82c16ff1aa486f8df83c.url(args, options),
    method: 'get',
})

/**
* @see \CraftCms\Cms\Http\Controllers\Entries\CreateEntryController::__invoke
* @see Users/brianhanson/Development/craft6/src/Http/Controllers/Entries/CreateEntryController.php:40
* @route '/admin/content/{section}/new'
*/
CreateEntryController110d011d4d1f82c16ff1aa486f8df83c.head = (args: { section: string | number } | [section: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: CreateEntryController110d011d4d1f82c16ff1aa486f8df83c.url(args, options),
    method: 'head',
})

const CreateEntryController = {
    '/admin/actions/entries/create': CreateEntryControllereceaa511c1a4e7bb5dd647f99d9cf1c8,
    '/admin/entries/{section}/new': CreateEntryControllera8480f49557601a276bb03eb660dc187,
    '/admin/content/{section}/new': CreateEntryController110d011d4d1f82c16ff1aa486f8df83c,
}

export default CreateEntryController