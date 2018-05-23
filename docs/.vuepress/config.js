module.exports = {
    title: 'Craft 2 Documentation',
    description: 'Craft 2 Documentation',
    theme: 'craftdocs',
    base: '/v2/',
    ga: 'UA-39036834-8',
    themeConfig: {
        docsRepo: 'craftcms/docs',
        docsDir: 'docs',
        docsBranch: 'v2',
        editLinks: true,
        nav: [
            {
                text: 'Craft CMS',
                items: [
                    { text: 'Craft 2 Documentation', link: '/' },
                    { text: 'Craft 3 Documentation', link: 'https://docs.craftcms.com/v3/' },
                    { text: 'Craft 2 Class Reference', link: 'https://docs.craftcms.com/api/v2/' },
                    { text: 'Craft 3 Class Reference', link: 'https://docs.craftcms.com/api/v3/' },
                ]
            },
            { text: 'Craftnet API', link: 'https://docs.api.craftcms.com/' },
        ],
        sidebar: {
            '/plugins/': [
                {
                    title: 'Plugin Development',
                    collapsable: false,
                    children: [
                        'introduction',
                        'setting-things-up',
                        'plugin-settings',
                        'templates',
                        'resources',
                        'database',
                        'internationalization',
                        'hooks-and-events'
                    ]
                },
                {
                    title: 'Components',
                    collapsable: false,
                    children: [
                        'controllers',
                        'element-actions',
                        'widgets',
                        'field-types',
                        'models',
                        'records',
                        'services',
                        'variables'
                    ]
                },
                {
                    title: 'References',
                    collapsable: false,
                    children: [
                        'hooks-reference',
                        'events-reference'
                    ]
                },
                {
                    title: 'Guides',
                    collapsable: false,
                    children: [
                        'migrations',
                        'working-with-elements'
                    ]
                }
            ],
            '/templating/': [
                ['../templating-overview', '← Templating Overview'],
                {
                    title: 'Templating Reference',
                    collapsable: false,
                    children: [
                        'filters',
                        'functions',
                        'global-variables'
                    ]
                },
                {
                    title: 'Tags',
                    collapsable: false,
                    children: [
                        'cache',
                        'exit',
                        'header',
                        'includecss',
                        'includecssfile',
                        'includehirescss',
                        'includejs',
                        'includejsfile',
                        'nav',
                        'paginate',
                        'redirect',
                        'requirelogin',
                        'requirepermission',
                        'switch'
                    ]
                },
                {
                    title: 'Querying Elements',
                    collapsable: false,
                    children: [
                        'craft.assets',
                        'craft.categories',
                        'craft.entries',
                        'craft.tags',
                        'craft.users',
                        'eager-loading-elements'
                    ]
                },
                {
                    title: 'Variables',
                    collapsable: false,
                    children: [
                        'assetfilemodel',
                        'assetfoldermodel',
                        'assetsourcemodel',
                        'categorygroupmodel',
                        'categorymodel',
                        'datetime',
                        'elementcriteriamodel',
                        'entrymodel',
                        'entrytypemodel',
                        'globalsetmodel',
                        'matrixblockmodel',
                        'sectionmodel',
                        'taggroupmodel',
                        'tagmodel',
                        'usergroupmodel',
                        'usermodel'
                    ]
                },
                {
                    title: 'More',
                    collapsable: false,
                    children: [
                        'craft.config',
                        'craft.feeds',
                        'craft.fields',
                        'craft.globals',
                        'craft.request',
                        'craft.sections',
                        'craft.session'
                    ]
                },
                {
                    title: 'Examples',
                    collapsable: false,
                    children: [
                        'integrating-disqus',
                        'rss-feed',
                        'atom-feed',
                        'entry-form',
                        'search-form',
                        'login-form',
                        'user-profile-form',
                        'user-registration-form',
                        'forgot-password-form',
                        'set-password-form',
                    ]
                }
            ],
            '/': [
                {
                    title: 'Introduction',
                    collapsable: false,
                    children: [
                        '',
                        'code-of-conduct'
                    ]
                },
                {
                    title: 'Installing & Updating',
                    collapsable: false,
                    children: [
                        'requirements',
                        'installing',
                        'updating'
                    ]
                },
                {
                    title: 'Getting Started',
                    collapsable: false,
                    children: [
                        'folder-structure'
                    ]
                },
                {
                    title: 'Configuration',
                    collapsable: false,
                    children: [
                        'config-settings',
                        'multi-environment-configs',
                        'php-constants'
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
                        ['localization-guide', 'Localization'],
                        'reference-tags'
                    ]
                },
                {
                    title: 'Templating',
                    collapsable: false,
                    children: [
                        'templating-overview',
                        'twig-primer',
                        ['templating/filters', 'Templating Reference']
                    ]
                },
                {
                    title: 'Plugin Development',
                    collapsable: false,
                    children: [
                        ['plugins/introduction', 'Plugin Development']
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
            indexName: 'craftcms',
            algoliaOptions: {
                facetFilters: ['version:v2', 'tags:doc']
            }
        }
    },
    markdown: {
        anchor: {
            level: [2, 3]
        },
        config(md) {
            require('vuepress-theme-craftdocs/markup')(md);
            md
                .use(require('markdown-it-deflist'));
        }
    }
}
