module.exports = {
    selectText: 'Language',
    label: 'English',
    // text for the edit-on-github link
    editLinkText: 'Edit this page on GitHub',
    algolia: {
        apiKey: '1014b55e7f916b20c5d6834bf7666dc3',
        indexName: 'craftcms',
        algoliaOptions: {
            facetFilters: ['version:v3', 'tags:doc', 'tags:en'],
            hitsPerPage: 10
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
                    'project-config',
                    'changelogs-and-updates',
                    'plugin-editions',
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
                    'migrations',
                    'user-permissions',
                    'translation-categories',
                    'asset-bundles',
                    'services',
                    // 'behaviors',
                    'template-roots',
                    'extending-twig',
                    'template-hooks',
                    'soft-deletes',
                    'environmental-settings',
                    // 'front-end-controllers',
                ]
            }
        ],
        '/testing/': [
            {
                title: 'About testing',
                collapsable: false,
                children: [
                    ['', 'Introduction'],
                    'testing',
                    'codeception',
                    'ci'
                ]
            },
            {
                title: 'Testing Craft',
                collapsable: false,
                children: [
                    'testing-craft/getting-started',
                    'testing-craft/examples',
                    'testing-craft/testing-tips',
                    'testing-craft/plugins-and-modules',
                    'testing-craft/fixtures',
                    'testing-craft/console',
                    'testing-craft/queue',
                    'testing-craft/events'
                ]
            },
            {
                title: 'Craft testing framework',
                collapsable: false,
                children: [
                    'framework/config-options',
                    'framework/mocking',
                    'framework/support-classes',
                    'framework/full-mock',
                    'framework/assertion-helpers'
                ]
            },

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
                    'tags/dd',
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
                    'directory-structure',
                ]
            },
            {
                title: 'Installing & Updating',
                collapsable: false,
                children: [
                    'requirements',
                    'installation',
                    'updating',
                    'upgrade',
                    'changes-in-craft-3',
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
                    'config/app',
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
                title: 'Field Types',
                collapsable: false,
                children: [
                    'assets-fields',
                    'categories-fields',
                    'checkboxes-fields',
                    'color-fields',
                    'date-time-fields',
                    'dropdown-fields',
                    'entries-fields',
                    'lightswitch-fields',
                    'matrix-fields',
                    'multi-select-fields',
                    'number-fields',
                    'plain-text-fields',
                    'radio-buttons-fields',
                    'table-fields',
                    'tags-fields',
                    'users-fields',
                ]
            },
            {
                title: 'Development',
                collapsable: false,
                children: [
                    'dev/',
                    'extend/',
                    'testing/'
                ]
            },
            {
                title: 'More',
                collapsable: false,
                children: [
                    'project-config',
                    'gc',
                    'reference-tags',
                ]
            },
        ]
    }
};
