module.exports = {
    selectText: 'Language',
    label: 'English',
    // text for the edit-on-github link
    editLinkText: 'Edit this page on GitHub',
    algolia: {
        apiKey: '1014b55e7f916b20c5d6834bf7666dc3',
        indexName: 'craftcms',
        algoliaOptions: {
            facetFilters: ['version:v3', 'tags:doc', 'tags:en']
        }
    },
    sidebar: {
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
                    'cp-templates',
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
                    'user-permissions',
                    'translation-categories',
                    'asset-bundles',
                    'services',
                    // 'behaviors',
                    'template-roots',
                    'extending-twig',
                    'template-hooks',
                    // 'front-end-controllers',
                ]
            }
        ],
        '/dev/': [
            {
                title: 'Front-End Development',
                collapsable: false,
                children: [
                    ['', 'Introduction'],
                    'headless',
                ]
            },
            {
                title: 'Templating',
                collapsable: false,
                children: [
                    'twig-primer',
                    'filters',
                    'functions',
                    'global-variables',
                    'tests',
                    'tags/cache',
                    'tags/css',
                    'tags/exit',
                    'tags/header',
                    'tags/hook',
                    'tags/js',
                    'tags/nav',
                    'tags/paginate',
                    'tags/redirect',
                    'tags/requirelogin',
                    'tags/requirepermission',
                    'tags/switch',
                ]
            },
            {
                title: 'Querying Elements',
                collapsable: false,
                children: [
                    'element-queries/',
                    'element-queries/asset-queries',
                    'element-queries/category-queries',
                    'element-queries/entry-queries',
                    'element-queries/global-set-queries',
                    'element-queries/matrix-block-queries',
                    'element-queries/tag-queries',
                    'element-queries/user-queries',
                    'eager-loading-elements',
                ]
            },
            {
                title: 'Templating Examples',
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
                title: 'Configuration',
                collapsable: false,
                children: [
                    'config/',
                    'config/config-settings',
                    'config/db-settings',
                    'config/environments',
                    'config/php-constants',
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
                    'relations',
                    'searching',
                    'sites',
                    ['localization', 'Localization'],
                    'static-translations',
                    'plugins',
                ]
            },
            {
                title: 'Development',
                collapsable: false,
                children: [
                    'dev/',
                    'extend/',
                ]
            },
            {
                title: 'Advanced',
                collapsable: false,
                children: [
                    'content-migrations',
                    'reference-tags',
                ]
            },
        ]
    }
};
