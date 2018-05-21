module.exports = {
    title: 'Craft 3 Documentation',
    description: 'Craft 3 Documentation',
    theme: 'craftdocs',
    base: '/v3/',
    ga: 'UA-39036834-9',
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
            indexName: 'craftcms',
            algoliaOptions: {
                facetFilters: ['version:v3', 'tags:doc']
            }
        }
    },
    markdown: {
        anchor: {
            level: [2, 3]
        },
        config(md) {
            md
                .use(replaceApiLinks)
                .use(require('vuepress-theme-craftdocs/markup'))
        }
    },
}

function replaceApiLinks(md) {
    // code adapted from the markdown-it-replace-link plugin
    md.core.ruler.after(
        'inline',
        'replace-link',
        function (state) {
            state.tokens.forEach(function (blockToken) {
                if (blockToken.type === 'inline' && blockToken.children) {
                    blockToken.children.forEach(function (token, tokenIndex) {
                        if (token.type === 'link_open') {
                            token.attrs.forEach(function (attr) {
                                if (attr[0] === 'href') {
                                    let replace = replaceApiLink(attr[1]);
                                    if (replace) {
                                        attr[1] = replace;
                                        let next = blockToken.children[tokenIndex+1];
                                        if (next.type === 'text' && next.content.match(/^api:/)) {
                                            next.content = next.content.substr(4);
                                        }
                                    }
                                }
                                return false;
                            });
                        }
                    });
                }
            });
            return false;
        }
    );
}

function replaceApiLink(link) {
    link = decodeURIComponent(link)
    let m = link.match(/^(?:api:)?\\?([\w\\]+)(?:::\$?(\w+)(\(\))?)?$/)
    if (m) {
        let className = m[1]
        let subject = m[2]
        let isMethod = typeof m[3] !== 'undefined'

        if (className.match(/^craft\\/) || className.match(/^Craft/)) {
            let url = 'https://docs.craftcms.com/api/v3/'+className.replace(/\\/g, '-').toLowerCase()+'.html'
            if (subject) {
                url += '#'
                if (isMethod) {
                    url += 'method-'
                } else if (!subject.match(/^EVENT_/)) {
                    url += 'property-'
                }
                url += subject.replace(/_/g, '-').toLowerCase()
            }
            return url;
        }

        if (className.match(/^yii\\/) || className.match(/^Yii/)) {
            let url = 'https://www.yiiframework.com/doc/api/2.0/'+className.replace(/\\/g, '-').toLowerCase()
            if (subject) {
                url += '#'+subject+(isMethod ? '()' : '')+'-detail'
            }
            return url;
        }

        if (className.match(/^Twig/)) {
            let url = 'https://twig.symfony.com/api/2.x/'+className.replace(/\\/g, '/')+'.html'
            if (subject) {
                url += '#method_'+subject
            }
            return url;
        }
    }
}
