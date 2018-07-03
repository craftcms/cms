module.exports = {
    selectText: 'Language',
    label: 'English',
    // text for the edit-on-github link
    editLinkText: 'Edit this page on GitHub',
    algolia: {
        apiKey: '1014b55e7f916b20c5d6834bf7666dc3',
        indexName: 'craftcms',
        algoliaOptions: {
            facetFilters: ['version:v3', 'tags:doc']
        }
    },
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
        '/extend/': [
            {
                title: 'Extending Craft',
                collapsable: false,
                children: [
                    ['', 'Introduction'],
                    'coding-guidelines',
                ]
            },
            {
                title: 'Module Development',
                collapsable: false,
                children: [
                    'module-guide',
                ]
            },
            {
                title: 'Plugin Development',
                collapsable: false,
                children: [
                    'plugin-guide',
                    'updating-plugins',
                    'plugin-settings',
                    'plugin-migrations',
                    'changelogs-and-updates',
                    'plugin-store',
                ]
            },
            {
                title: 'Extending the Control Panel',
                collapsable: false,
                children: [
                    'cp-section',
                    // 'cp-components',
                    // 'cp-controllers',
                ]
            },
            {
                title: 'System Components',
                collapsable: false,
                children: [
                    'widget-types',
                    'field-types',
                    'volume-types',
                    'utility-types',
                    'element-types',
                    'element-action-types',
                ]
            },
            {
                title: 'More',
                collapsable: false,
                children: [
                    'asset-bundles',
                    'services',
                    // 'behaviors',
                    'extending-twig',
                    // 'front-end-controllers',
                ]
            }
        ],
        '/templating/': [
            {
                title: 'Templating in Craft',
                collapsable: false,
                children: [
                    ['', 'Introduction'],
                    'twig-primer',
                ]
            },
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
                title: 'Front-End Development',
                collapsable: false,
                children: [
                    'templating/',
                    // 'APIs'
                ]
            },
            {
                title: 'Advanced Topics',
                collapsable: false,
                children: [
                    'relations',
                    'reference-tags',
                    'eager-loading-elements',
                    'extend/',
                ]
            }
        ]
    }
};
