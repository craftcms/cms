module.exports = {
    selectText: '言語',
    label: '日本語',
    editLinkText: 'このページを GitHub で編集',
    algolia: {
        apiKey: '1014b55e7f916b20c5d6834bf7666dc3',
        indexName: 'craftcms',
        algoliaOptions: {
            facetFilters: ['version:v3', 'tags:doc', 'tags:ja'],
            hitsPerPage: 10
        }
    },
    sidebar: {
        '/ja/extend/': [
            {
                title: 'Craft の拡張',
                collapsable: false,
                children: [
                    ['', 'はじめに'],
                    'coding-guidelines',
                ]
            },
            {
                title: 'モジュール開発',
                collapsable: false,
                children: [
                    'module-guide',
                ]
            },
            {
                title: 'プラグイン開発',
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
                title: 'コントロールパネルの拡張',
                collapsable: false,
                children: [
                    'cp-section',
                    'cp-templates',
                    // 'cp-components',
                    // 'cp-controllers',
                ]
            },
            {
                title: 'システムコンポーネント',
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
                title: '追加情報',
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
        '/ja/dev/': [
            {
                title: 'フロントエンド開発',
                collapsable: false,
                children: [
                    ['', '導入'],
                    'headless',
                ]
            },
            {
                title: 'テンプレート',
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
                title: 'エレメントのクエリ',
                collapsable: false,
                children: [
                    ['element-queries/', 'エレメントクエリについて'],
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
                title: 'テンプレートの実例',
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
        '/ja/': [
            {
                title: '導入',
                collapsable: false,
                children: [
                    '',
                    'coc',
                    'directory-structure',
                ]
            },
            {
                title: 'インストールとアップデート',
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
                title: 'コンフィギュレーション',
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
                title: 'コアコンセプト',
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
                    ['localization', 'ローカライゼーション'],
                    'static-translations',
                    'plugins',
                ]
            },
            {
                title: 'フィールドタイプ',
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
                title: '開発',
                collapsable: false,
                children: [
                    'dev/',
                    'extend/',
                ]
            },
            {
                title: '追加情報',
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
