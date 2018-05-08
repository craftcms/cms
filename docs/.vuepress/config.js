module.exports = {
    title: 'Craft 3 Documentation',
    description: 'Craft 3 Documentation',
    theme: 'craftdocs',
    base: '/v3/',
    dest: './web/',
    themeConfig: {
        docsRepo: 'craftcms/docs',
        docsDir: 'docs',
        docsBranch: 'v3',
        editLinks: true,
        nav: [
            {
                text: 'Craft CMS',
                items: [
                    { text: 'Craft 2 Documentation', link: 'https://docs.craftcms.com/v2/' },
                    { text: 'Craft 3 Documentation', link: '/' },
                    { text: 'Craft 2 Class Reference', link: 'https://docs.craftcms.com/api/v2/' },
                    { text: 'Craft 3 Class Reference', link: 'https://docs.craftcms.com/api/v3/' },
                ]
            },
            { text: 'Craftnet API', link: 'https://docs.api.craftcms.com/' },
        ],
        sidebar: {
            '/element-query-params/': [
                ['../element-queries', '← Element Queries'],
                {
                    title: 'Element Query Params',
                    collapsable: false,
                    children: [
                        ['asset-query-params', 'Assets'],
                        ['category-query-params', 'Categories'],
                        ['entry-query-params', 'Entries'],
                        ['matrix-block-query-params', 'Matrix Blocks'],
                        ['tag-query-params', 'Tags'],
                        ['user-query-params', 'Users'],
                    ]
                }
            ],
            '/templating/': [
                ['../twig-primer', '← Twig Primer'],
                {
                    title: 'Tags',
                    collapsable: false,
                    children: [
                        'tags/cache',
                        'tags/css',
                        'tags/exit',
                        'tags/header',
                        'tags/js',
                        'tags/nav',
                        'tags/paginate',
                        'tags/redirect',
                        'tags/requirelogin',
                        'tags/requirepermission',
                        'tags/switch',
                    ]
                },
                'filters',
                'functions',
                'global-variables',
                'tests',
                'elements',
                {
                    title: 'Examples',
                    collapsable: false,
                    children: [
                        'examples/integrating-disqus',
                        'examples/rss-feed',
                        'examples/atom-feed',
                        'examples/entry-form',
                        'examples/search-form',
                        'examples/login-form',
                        'examples/user-profile-form',
                        'examples/user-registration-form',
                        'examples/forgot-password-form',
                        'examples/set-password-form',
                    ]
                }
            ],
            '/': [
                {
                    title: 'Introduction',
                    collapsable: false,
                    children: [
                        '',
                        'coc',
                        'how-to-use-the-documentation'
                    ]
                },
                {
                    title: 'Installing Craft',
                    collapsable: false,
                    children: [
                        'requirements',
                        'installation'
                    ]
                },
                {
                    title: 'Updating Craft',
                    collapsable: false,
                    children: [
                        'upgrade',
                        'updating',
                        'changes-in-craft-3'
                    ]
                },
                {
                    title: 'Getting Started',
                    collapsable: false,
                    children: [
                        'the-pieces-of-craft',
                        'directory-structure'
                    ]
                },
                {
                    title: 'Core Concepts',
                    collapsable: false,
                    children: [
                        'sections-and-entries',
                        'fields',
                        'categories',
                        'assets',
                        'users',
                        'globals',
                        'tags',
                        'relations',
                        'routing',
                        'searching',
                        'sites',
                        ['localization', 'Localization'],
                        'element-queries',
                        'content-migrations',
                        'configuration'
                    ]
                },
                {
                    title: 'Templating',
                    collapsable: false,
                    children: [
                        'twig-primer',
                        'templating/tags',
                        'templating/filters',
                        'templating/functions',
                        'templating/global-variables',
                        'templating/tests',
                        'templating/elements',
                        'templating/examples/'
                    ]
                },
                {
                    title: 'Advanced Topics',
                    collapsable: false,
                    children: [
                        'eager-loading-elements'
                    ]
                },
                {
                    title: 'Plugin Development',
                    collapsable: false,
                    children: [
                        'plugin-intro',
                        'coding-guidelines',
                        'updating-plugins',
                        'changelogs-and-updates',
                        'plugin-settings',
                        'cp-section',
                        'asset-bundles',
                        'services',
                        'extending-twig',
                        'widget-types',
                        'field-types',
                        'volume-types',
                        'utility-types',
                        'element-types',
                        'element-action-types',
                        'plugin-migrations',
                        'plugin-store'
                    ]
                }
            ]
        },
        codeLanguages: {
            twig: 'Twig',
            php: 'PHP',
        },
        algolia: {
            apiKey: '1014b55e7f916b20c5d6834bf7666dc3',
            indexName: 'craftcms'
        }
    },
    markdown: {
        anchor: {
            level: [2, 3]
        },
        config: require('vuepress-theme-craftdocs/markup')
    },
    // configureWebpack: {
    //     optimization: {
    //         minimize: false
    //     }
    // },
}
