module.exports = {
    selectText: '言語',
    label: '日本語',
    editLinkText: 'このページを GitHub で編集',
    // algolia: {
    //     apiKey: '1014b55e7f916b20c5d6834bf7666dc3',
    //     indexName: 'craftcms',
    //     algoliaOptions: {
    //         facetFilters: ['version:v3', 'tags:doc']
    //     }
    // },
    sidebar: {
        '/ja/element-query-params/': [
            ['../element-queries', '← エレメントクエリ'],
            {
                title: 'エレメントのクエリパラメータ',
                collapsable: false,
                children: [
                    ['asset-query-params', 'アセット'],
                    ['category-query-params', 'カテゴリ'],
                    ['entry-query-params', 'エントリ'],
                    ['matrix-block-query-params', '行列ブロック'],
                    ['tag-query-params', 'タグ'],
                    ['user-query-params', 'ユーザー'],
                ]
            }
        ],
        '/ja/templating/': [
            ['../twig-primer', '← Twig 入門書'],
            {
                title: 'タグ',
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
                    ['how-to-use-the-documentation', 'このドキュメントの使い方'],
                ]
            },
            {
                title: 'Craft のインストール',
                collapsable: false,
                children: [
                    'requirements',
                    'installation',
                ]
            },
            {
                title: 'Craft のアップデート',
                collapsable: false,
                children: [
                    'upgrade',
                    'updating',
                    'changes-in-craft-3',
                ]
            },
            {
                title: 'はじめに',
                collapsable: false,
                children: [
                    'the-pieces-of-craft',
                    'directory-structure',
                ]
            },
            {
                title: 'コアコンセプト',
                collapsable: false,
                children: [
                    '/ja/sections-and-entries',
                    'fields',
                    'categories',
                    'assets',
                    'users',
                    'globals',
                    'tags',
                    'routing',
                    'searching',
                    'sites',
                    ['localization', 'ローカライゼーション'],
                    'element-queries',
                    'content-migrations',
                    'configuration',
                ]
            },
            {
                title: 'テンプレート記法',
                collapsable: false,
                children: [
                    'twig-primer',
                    'templating/tags',
                    'templating/filters',
                    'templating/functions',
                    'templating/global-variables',
                    'templating/tests',
                    'templating/elements',
                    'templating/examples/',
                ]
            },
            {
                title: '高度なトピックス',
                collapsable: false,
                children: [
                    'relations',
                    'reference-tags',
                    'eager-loading-elements',
                ]
            },
            {
                title: 'プラグイン開発',
                collapsable: false,
                children: [
                    'plugin-intro',
                    // 'coding-guidelines',
                    // 'updating-plugins',
                    // 'changelogs-and-updates',
                    // 'plugin-settings',
                    // 'cp-section',
                    // 'asset-bundles',
                    // 'services',
                    // 'extending-twig',
                    // 'widget-types',
                    // 'field-types',
                    // 'volume-types',
                    // 'utility-types',
                    // 'element-types',
                    // 'element-action-types',
                    // 'plugin-migrations',
                    // 'plugin-store'
                ]
            }
        ]
    }
};
