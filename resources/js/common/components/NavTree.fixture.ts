/**
 * The navigation shape we're designing towards, as a fixture.
 *
 * PROTOTYPE. Nothing on the server produces this yet: `Navigation::getItems()`
 * builds one level plus whatever a plugin hands over, and element sources
 * never enter the nav at all. This stands in for the navigation map so the
 * interaction can be judged before the backend shape is settled.
 *
 * It deliberately includes the awkward cases: a `group` heading three levels
 * down (`Channels`), a plugin with its own subnav, and a level whose children
 * are element sources rather than pages.
 */

type NavNode = CraftCms.Cms.Cp.Data.ActionItem;

/** Fills in the fields the DTO always carries so the tree below stays legible. */
function node(label: string, extra: Partial<NavNode> = {}): NavNode {
  return {
    label,
    html: null,
    ariaLabel: null,
    href: null,
    external: false,
    icon: null,
    fontIcon: null,
    iconColor: null,
    id: null,
    variant: null,
    badgeCount: 0,
    selected: false,
    disabled: false,
    current: false,
    group: false,
    subnav: false,
    items: [],
    keywords: null,
    shortcut: null,
    action: null,
    feedback: null,
    linkAttributes: {},
    errors: undefined,
    ruleset: undefined,
    ...extra,
  } as NavNode;
}

/** A leaf: a plain destination. */
const leaf = (label: string, href: string): NavNode => node(label, {href});

/** A heading over its children — not somewhere to go. */
const group = (label: string, subnav: Array<NavNode>): NavNode =>
  node(label, {group: true, subnav});

const branch = (
  label: string,
  href: string | null,
  subnav: Array<NavNode>,
  extra: Partial<NavNode> = {}
): NavNode => node(label, {href, subnav, ...extra});

export const navFixture: Array<NavNode> = [
  node('Dashboard', {href: '/admin/dashboard', icon: 'gauge'}),

  branch(
    'Content',
    '/admin/content',
    [
      branch(
        'Entries',
        '/admin/content/entries',
        [
          leaf('All Entries', '/admin/content/entries'),
          group('Channels', [
            leaf('Blog', '/admin/content/entries/blog'),
            leaf('CMS-15980', '/admin/content/entries/cms-15980'),
            leaf('Github Issues', '/admin/content/entries/github-issues'),
          ]),
          group('Structures', [
            leaf('Structure', '/admin/content/entries/structure'),
          ]),
          group('Heading', [leaf('Alerts', '/admin/content/entries/alerts')]),
          leaf('Singles', '/admin/content/entries/singles'),
          leaf('CKEditor', '/admin/content/entries/ckeditor'),
          leaf('Issues', '/admin/content/entries/issues'),
        ],
        {selected: true}
      ),
      leaf('Globals', '/admin/content/globals'),
      leaf('Categories', '/admin/content/categories'),
      branch('Assets', '/admin/assets', [
        leaf('Uploads', '/admin/assets/uploads'),
        leaf('Cloud', '/admin/assets/cloud'),
        leaf('Temporary Uploads', '/admin/assets/temp'),
      ]),
    ],
    {icon: 'newspaper'}
  ),

  branch(
    'Administration',
    null,
    [
      leaf('Users', '/admin/users'),
      branch('GraphQL', '/admin/graphql', [
        leaf('Schemas', '/admin/graphql/schemas'),
        leaf('Tokens', '/admin/graphql/tokens'),
        leaf('GraphiQL', '/admin/graphql/explore'),
      ]),
      node('Utilities', {href: '/admin/utilities', badgeCount: 2}),
    ],
    {icon: 'sliders'}
  ),

  branch(
    'Commerce',
    '/admin/commerce',
    [
      leaf('Orders', '/admin/commerce/orders'),
      leaf('Products', '/admin/commerce/products'),
      leaf('Inventory', '/admin/commerce/inventory'),
      leaf('Inventory Locations', '/admin/commerce/inventory-locations'),
      leaf('Subscription Plans', '/admin/commerce/subscription-plans'),
      leaf('Donations', '/admin/commerce/donations'),
      leaf('Store Management', '/admin/commerce/store-management'),
      leaf('Settings', '/admin/commerce/settings'),
    ],
    {icon: 'cart-shopping'}
  ),

  node('Feed Me', {href: '/admin/feed-me', icon: 'arrow-down-to-line'}),
  node('Field Manager', {
    href: '/admin/fieldmanager',
    icon: 'wand-magic-sparkles',
  }),

  branch(
    'SEOmatic',
    '/admin/seomatic',
    [
      leaf('Dashboard', '/admin/seomatic/dashboard'),
      leaf('Global SEO', '/admin/seomatic/global'),
      leaf('Content SEO', '/admin/seomatic/content'),
      leaf('Site Settings', '/admin/seomatic/site'),
      leaf('Tracking Scripts', '/admin/seomatic/tracking'),
      leaf('Plugin Settings', '/admin/seomatic/plugin'),
    ],
    {icon: 'magnifying-glass'}
  ),

  branch(
    'Settings',
    '/admin/settings',
    [
      leaf('General', '/admin/settings/general'),
      leaf('Sites', '/admin/settings/sites'),
      leaf('Routes', '/admin/settings/routes'),
      leaf('Users', '/admin/settings/users'),
      leaf('Addresses', '/admin/settings/addresses'),
      leaf('Email', '/admin/settings/email'),
      leaf('Plugins', '/admin/settings/plugins'),
      leaf('Sections', '/admin/settings/sections'),
      leaf('Entry Types', '/admin/settings/entry-types'),
      leaf('Fields', '/admin/settings/fields'),
      leaf('Globals', '/admin/settings/globals'),
      leaf('Categories', '/admin/settings/categories'),
      leaf('Tags', '/admin/settings/tags'),
      leaf('Assets', '/admin/settings/assets'),
      leaf('Filesystems', '/admin/settings/filesystems'),
      leaf('Craft Commerce', '/admin/settings/commerce'),
      leaf('Feed Me', '/admin/settings/feed-me'),
      leaf('Field Manager', '/admin/settings/fieldmanager'),
      leaf('SEOmatic', '/admin/settings/seomatic'),
    ],
    {icon: 'gear'}
  ),

  node('Plugin Store', {href: '/admin/plugin-store', icon: 'plug'}),
];
