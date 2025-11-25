import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \Illuminate\Routing\ViewController::__invoke
* @see Users/brianhanson/Development/craft6/vendor/laravel/framework/src/Illuminate/Routing/ViewController.php:32
* @route '/admin/settings/addresses'
*/
const ViewController957fba5f967e92e019c514d4f835157a = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: ViewController957fba5f967e92e019c514d4f835157a.url(options),
    method: 'get',
})

ViewController957fba5f967e92e019c514d4f835157a.definition = {
    methods: ["get","head"],
    url: '/admin/settings/addresses',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Illuminate\Routing\ViewController::__invoke
* @see Users/brianhanson/Development/craft6/vendor/laravel/framework/src/Illuminate/Routing/ViewController.php:32
* @route '/admin/settings/addresses'
*/
ViewController957fba5f967e92e019c514d4f835157a.url = (options?: RouteQueryOptions) => {
    return ViewController957fba5f967e92e019c514d4f835157a.definition.url + queryParams(options)
}

/**
* @see \Illuminate\Routing\ViewController::__invoke
* @see Users/brianhanson/Development/craft6/vendor/laravel/framework/src/Illuminate/Routing/ViewController.php:32
* @route '/admin/settings/addresses'
*/
ViewController957fba5f967e92e019c514d4f835157a.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: ViewController957fba5f967e92e019c514d4f835157a.url(options),
    method: 'get',
})

/**
* @see \Illuminate\Routing\ViewController::__invoke
* @see Users/brianhanson/Development/craft6/vendor/laravel/framework/src/Illuminate/Routing/ViewController.php:32
* @route '/admin/settings/addresses'
*/
ViewController957fba5f967e92e019c514d4f835157a.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: ViewController957fba5f967e92e019c514d4f835157a.url(options),
    method: 'head',
})

/**
* @see \Illuminate\Routing\ViewController::__invoke
* @see Users/brianhanson/Development/craft6/vendor/laravel/framework/src/Illuminate/Routing/ViewController.php:32
* @route '/admin/entries/{sectionHandle}'
*/
const ViewController5858bfebf0a10128ee51515b3e655eb8 = (args: { sectionHandle: string | number } | [sectionHandle: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: ViewController5858bfebf0a10128ee51515b3e655eb8.url(args, options),
    method: 'get',
})

ViewController5858bfebf0a10128ee51515b3e655eb8.definition = {
    methods: ["get","head"],
    url: '/admin/entries/{sectionHandle}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Illuminate\Routing\ViewController::__invoke
* @see Users/brianhanson/Development/craft6/vendor/laravel/framework/src/Illuminate/Routing/ViewController.php:32
* @route '/admin/entries/{sectionHandle}'
*/
ViewController5858bfebf0a10128ee51515b3e655eb8.url = (args: { sectionHandle: string | number } | [sectionHandle: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { sectionHandle: args }
    }

    if (Array.isArray(args)) {
        args = {
            sectionHandle: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        sectionHandle: args.sectionHandle,
    }

    return ViewController5858bfebf0a10128ee51515b3e655eb8.definition.url
            .replace('{sectionHandle}', parsedArgs.sectionHandle.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \Illuminate\Routing\ViewController::__invoke
* @see Users/brianhanson/Development/craft6/vendor/laravel/framework/src/Illuminate/Routing/ViewController.php:32
* @route '/admin/entries/{sectionHandle}'
*/
ViewController5858bfebf0a10128ee51515b3e655eb8.get = (args: { sectionHandle: string | number } | [sectionHandle: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: ViewController5858bfebf0a10128ee51515b3e655eb8.url(args, options),
    method: 'get',
})

/**
* @see \Illuminate\Routing\ViewController::__invoke
* @see Users/brianhanson/Development/craft6/vendor/laravel/framework/src/Illuminate/Routing/ViewController.php:32
* @route '/admin/entries/{sectionHandle}'
*/
ViewController5858bfebf0a10128ee51515b3e655eb8.head = (args: { sectionHandle: string | number } | [sectionHandle: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: ViewController5858bfebf0a10128ee51515b3e655eb8.url(args, options),
    method: 'head',
})

/**
* @see \Illuminate\Routing\ViewController::__invoke
* @see Users/brianhanson/Development/craft6/vendor/laravel/framework/src/Illuminate/Routing/ViewController.php:32
* @route '/admin/content/{page}'
*/
const ViewControllerc6c2aa17c0c960ea505b70926dafb6c3 = (args: { page: string | number } | [page: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: ViewControllerc6c2aa17c0c960ea505b70926dafb6c3.url(args, options),
    method: 'get',
})

ViewControllerc6c2aa17c0c960ea505b70926dafb6c3.definition = {
    methods: ["get","head"],
    url: '/admin/content/{page}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Illuminate\Routing\ViewController::__invoke
* @see Users/brianhanson/Development/craft6/vendor/laravel/framework/src/Illuminate/Routing/ViewController.php:32
* @route '/admin/content/{page}'
*/
ViewControllerc6c2aa17c0c960ea505b70926dafb6c3.url = (args: { page: string | number } | [page: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { page: args }
    }

    if (Array.isArray(args)) {
        args = {
            page: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        page: args.page,
    }

    return ViewControllerc6c2aa17c0c960ea505b70926dafb6c3.definition.url
            .replace('{page}', parsedArgs.page.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \Illuminate\Routing\ViewController::__invoke
* @see Users/brianhanson/Development/craft6/vendor/laravel/framework/src/Illuminate/Routing/ViewController.php:32
* @route '/admin/content/{page}'
*/
ViewControllerc6c2aa17c0c960ea505b70926dafb6c3.get = (args: { page: string | number } | [page: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: ViewControllerc6c2aa17c0c960ea505b70926dafb6c3.url(args, options),
    method: 'get',
})

/**
* @see \Illuminate\Routing\ViewController::__invoke
* @see Users/brianhanson/Development/craft6/vendor/laravel/framework/src/Illuminate/Routing/ViewController.php:32
* @route '/admin/content/{page}'
*/
ViewControllerc6c2aa17c0c960ea505b70926dafb6c3.head = (args: { page: string | number } | [page: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: ViewControllerc6c2aa17c0c960ea505b70926dafb6c3.url(args, options),
    method: 'head',
})

/**
* @see \Illuminate\Routing\ViewController::__invoke
* @see Users/brianhanson/Development/craft6/vendor/laravel/framework/src/Illuminate/Routing/ViewController.php:32
* @route '/admin/content/{page}/{sectionHandle}'
*/
const ViewController669f9f407e56e7fbbb9a1c021e58dd13 = (args: { page: string | number, sectionHandle: string | number } | [page: string | number, sectionHandle: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: ViewController669f9f407e56e7fbbb9a1c021e58dd13.url(args, options),
    method: 'get',
})

ViewController669f9f407e56e7fbbb9a1c021e58dd13.definition = {
    methods: ["get","head"],
    url: '/admin/content/{page}/{sectionHandle}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Illuminate\Routing\ViewController::__invoke
* @see Users/brianhanson/Development/craft6/vendor/laravel/framework/src/Illuminate/Routing/ViewController.php:32
* @route '/admin/content/{page}/{sectionHandle}'
*/
ViewController669f9f407e56e7fbbb9a1c021e58dd13.url = (args: { page: string | number, sectionHandle: string | number } | [page: string | number, sectionHandle: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            page: args[0],
            sectionHandle: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        page: args.page,
        sectionHandle: args.sectionHandle,
    }

    return ViewController669f9f407e56e7fbbb9a1c021e58dd13.definition.url
            .replace('{page}', parsedArgs.page.toString())
            .replace('{sectionHandle}', parsedArgs.sectionHandle.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \Illuminate\Routing\ViewController::__invoke
* @see Users/brianhanson/Development/craft6/vendor/laravel/framework/src/Illuminate/Routing/ViewController.php:32
* @route '/admin/content/{page}/{sectionHandle}'
*/
ViewController669f9f407e56e7fbbb9a1c021e58dd13.get = (args: { page: string | number, sectionHandle: string | number } | [page: string | number, sectionHandle: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: ViewController669f9f407e56e7fbbb9a1c021e58dd13.url(args, options),
    method: 'get',
})

/**
* @see \Illuminate\Routing\ViewController::__invoke
* @see Users/brianhanson/Development/craft6/vendor/laravel/framework/src/Illuminate/Routing/ViewController.php:32
* @route '/admin/content/{page}/{sectionHandle}'
*/
ViewController669f9f407e56e7fbbb9a1c021e58dd13.head = (args: { page: string | number, sectionHandle: string | number } | [page: string | number, sectionHandle: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: ViewController669f9f407e56e7fbbb9a1c021e58dd13.url(args, options),
    method: 'head',
})

const ViewController = {
    '/admin/settings/addresses': ViewController957fba5f967e92e019c514d4f835157a,
    '/admin/entries/{sectionHandle}': ViewController5858bfebf0a10128ee51515b3e655eb8,
    '/admin/content/{page}': ViewControllerc6c2aa17c0c960ea505b70926dafb6c3,
    '/admin/content/{page}/{sectionHandle}': ViewController669f9f407e56e7fbbb9a1c021e58dd13,
}

export default ViewController