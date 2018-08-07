module.exports = {
    title: 'Craft 2 Documentation',
    description: 'Craft 2 Documentation',
    theme: 'craftdocs',
    base: '/v2/',
    shouldPrefetch: () => false,
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
                        'reference-tags',
                        ['localization-guide', 'Localization'],
                        'static-translations',
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
            level: [2, 3, 4]
        },
        toc: {
            format(content) {
                return content.replace(/[_`]/g, '')
            }
        },
        config(md) {
            md
                .use(replaceApiLinks)
                .use(require('markdown-it-deflist'))
                .use(require('vuepress-theme-craftdocs/markup'))
        }
    }
};

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
                                        if (next.type === 'text') {
                                            next.content = next.content.replace(/^(api|config):/, '');
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
    let m = link.match(/^api:\\?([\w\\]+)(?:::\$?(\w+)(\(\))?)?(?:#([\w\-]+))?$/)
    if (m) {
        let className = m[1]
        let subject = m[2]
        let isMethod = typeof m[3] !== 'undefined'
        let hash = m[4]

        if (className.match(/^Craft\\/)) {
            let url = 'https://docs.craftcms.com/api/v2/'+className.replace(/\\/g, '-').toLowerCase()+'.html'
            if (subject) {
                hash = ''
                if (isMethod) {
                    hash = 'method-'
                } else if (!subject.match(/^EVENT_/)) {
                    hash = 'property-'
                }
                hash += subject.replace(/_/g, '-').toLowerCase()
            }
            return url + (hash ? `#${hash}` : '');
        }

        if (className.match(/^[CI][A-Z]/)) {
            let url = 'https://www.yiiframework.com/doc/api/1.1/'+className.replace(/\\/g, '-').toLowerCase()
            if (subject) {
                hash = subject+(isMethod ? '()' : '')+'-detail'
            }
            return url + (hash ? `#${hash}` : '');
        }

        if (className.match(/^Twig/)) {
            let url = 'https://twig.symfony.com/api/1.x/'+className.replace(/\\/g, '/')+'.html'
            if (subject) {
                hash = '#method_'+subject
            }
            return url + (hash ? `#${hash}` : '');
        }
    }

    m = link.match(/^config:(.+)/)
    if (m) {
        return '/config-settings.md#'+m[1].toLowerCase()
    }
}
