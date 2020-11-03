/** global: Craft */
QUnit.test('Craft.getUrl()', function(assert) {
    // Setup for a site with path info
    Craft.baseUrl = 'http://craft.dev/admin';
    Craft.baseCpUrl = 'http://craft.dev/admin';
    Craft.baseSiteUrl = 'http://craft.dev/';
    Craft.actionUrl = 'http://craft.dev/index.php/admin/actions';
    Craft.omitScriptNameInUrls = true;
    Craft.usePathInfo = true;
    Craft.actionTrigger = 'actions';
    Craft.path = '';
    Craft.language = 'en_us';
    Craft.translations = [];
    Craft.maxUploadSize = 33554432;

    // Test an absolute URL
    assert.equal(Craft.getUrl('http://example.com/'), 'http://example.com/');

    assert.equal(Craft.getUrl(), 'http://craft.dev/admin');
    assert.equal(Craft.getUrl(''), 'http://craft.dev/admin');

    assert.equal(Craft.getUrl('', {'foo': 1, 'bar': 1}), 'http://craft.dev/admin?foo=1&bar=1');
    assert.equal(Craft.getUrl('', 'foo=1&bar=1'), 'http://craft.dev/admin?foo=1&bar=1');
    assert.equal(Craft.getUrl('', '?foo=1&bar=1&'), 'http://craft.dev/admin?foo=1&bar=1');
    assert.equal(Craft.getUrl('', {'foo': 1, '#': 'bar'}), 'http://craft.dev/admin?foo=1#bar');
    assert.equal(Craft.getUrl('about', {'foo': 1, 'bar': 1}), 'http://craft.dev/admin/about?foo=1&bar=1');
    assert.equal(Craft.getUrl('about', 'foo=1&bar=1'), 'http://craft.dev/admin/about?foo=1&bar=1');
    assert.equal(Craft.getUrl('about', '?foo=1&bar=1&'), 'http://craft.dev/admin/about?foo=1&bar=1');
    assert.equal(Craft.getUrl('about?foo=1', 'bar=1'), 'http://craft.dev/admin/about?foo=1&bar=1');
    assert.equal(Craft.getUrl('about?foo=1', {'bar': 1}), 'http://craft.dev/admin/about?foo=1&bar=1');
    assert.equal(Craft.getUrl('about', {'foo': 1, '#': 'bar'}), 'http://craft.dev/admin/about?foo=1#bar');

    assert.equal(Craft.getSiteUrl('', {'foo': 1, 'bar': 1}), 'http://craft.dev/?foo=1&bar=1');
    assert.equal(Craft.getSiteUrl('', 'foo=1&bar=1'), 'http://craft.dev/?foo=1&bar=1');
    assert.equal(Craft.getSiteUrl('', '?foo=1&bar=1&'), 'http://craft.dev/?foo=1&bar=1');
    assert.equal(Craft.getSiteUrl('', {'foo': 1, '#': 'bar'}), 'http://craft.dev/?foo=1#bar');
    assert.equal(Craft.getSiteUrl('about', {'foo': 1, 'bar': 1}), 'http://craft.dev/about?foo=1&bar=1');
    assert.equal(Craft.getSiteUrl('about', 'foo=1&bar=1'), 'http://craft.dev/about?foo=1&bar=1');
    assert.equal(Craft.getSiteUrl('about', '?foo=1&bar=1&'), 'http://craft.dev/about?foo=1&bar=1');
    assert.equal(Craft.getSiteUrl('about?foo=1', 'bar=1'), 'http://craft.dev/about?foo=1&bar=1');
    assert.equal(Craft.getSiteUrl('about?foo=1', {'bar': 1}), 'http://craft.dev/about?foo=1&bar=1');
    assert.equal(Craft.getSiteUrl('about', {'foo': 1, '#': 'bar'}), 'http://craft.dev/about?foo=1#bar');

    assert.equal(Craft.getActionUrl('', {'foo': 1, 'bar': 1}), 'http://craft.dev/index.php/admin/actions?foo=1&bar=1');
    assert.equal(Craft.getActionUrl('', 'foo=1&bar=1'), 'http://craft.dev/index.php/admin/actions?foo=1&bar=1');
    assert.equal(Craft.getActionUrl('', '?foo=1&bar=1&'), 'http://craft.dev/index.php/admin/actions?foo=1&bar=1');
    assert.equal(Craft.getActionUrl('', {'foo': 1, '#': 'bar'}), 'http://craft.dev/index.php/admin/actions?foo=1#bar');
    assert.equal(Craft.getActionUrl('about', {'foo': 1, 'bar': 1}), 'http://craft.dev/index.php/admin/actions/about?foo=1&bar=1');
    assert.equal(Craft.getActionUrl('about', 'foo=1&bar=1'), 'http://craft.dev/index.php/admin/actions/about?foo=1&bar=1');
    assert.equal(Craft.getActionUrl('about', '?foo=1&bar=1&'), 'http://craft.dev/index.php/admin/actions/about?foo=1&bar=1');
    assert.equal(Craft.getActionUrl('about?foo=1', 'bar=1'), 'http://craft.dev/index.php/admin/actions/about?foo=1&bar=1');
    assert.equal(Craft.getActionUrl('about?foo=1', {'bar': 1}), 'http://craft.dev/index.php/admin/actions/about?foo=1&bar=1');
    assert.equal(Craft.getActionUrl('about', {'foo': 1, '#': 'bar'}), 'http://craft.dev/index.php/admin/actions/about?foo=1#bar');


    // Don't omit the script name
    Craft.baseUrl = 'http://craft.dev/index.php/admin';
    Craft.baseCpUrl = 'http://craft.dev/index.php/admin';
    Craft.baseSiteUrl = 'http://craft.dev/';
    Craft.actionUrl = 'http://craft.dev/index.php/admin/actions';
    Craft.omitScriptNameInUrls = false;

    assert.equal(Craft.getUrl(), 'http://craft.dev/index.php/admin');
    assert.equal(Craft.getUrl(''), 'http://craft.dev/index.php/admin');

    assert.equal(Craft.getUrl('', {'foo': 1, 'bar': 1}), 'http://craft.dev/index.php/admin?foo=1&bar=1');
    assert.equal(Craft.getUrl('', 'foo=1&bar=1'), 'http://craft.dev/index.php/admin?foo=1&bar=1');
    assert.equal(Craft.getUrl('', '?foo=1&bar=1&'), 'http://craft.dev/index.php/admin?foo=1&bar=1');
    assert.equal(Craft.getUrl('', {'foo': 1, '#': 'bar'}), 'http://craft.dev/index.php/admin?foo=1#bar');
    assert.equal(Craft.getUrl('about', {'foo': 1, 'bar': 1}), 'http://craft.dev/index.php/admin/about?foo=1&bar=1');
    assert.equal(Craft.getUrl('about', 'foo=1&bar=1'), 'http://craft.dev/index.php/admin/about?foo=1&bar=1');
    assert.equal(Craft.getUrl('about', '?foo=1&bar=1&'), 'http://craft.dev/index.php/admin/about?foo=1&bar=1');
    assert.equal(Craft.getUrl('about?foo=1', 'bar=1'), 'http://craft.dev/index.php/admin/about?foo=1&bar=1');
    assert.equal(Craft.getUrl('about?foo=1', {'bar': 1}), 'http://craft.dev/index.php/admin/about?foo=1&bar=1');
    assert.equal(Craft.getUrl('about', {'foo': 1, '#': 'bar'}), 'http://craft.dev/index.php/admin/about?foo=1#bar');

    assert.equal(Craft.getSiteUrl('', {'foo': 1, 'bar': 1}), 'http://craft.dev/?foo=1&bar=1');
    assert.equal(Craft.getSiteUrl('', 'foo=1&bar=1'), 'http://craft.dev/?foo=1&bar=1');
    assert.equal(Craft.getSiteUrl('', '?foo=1&bar=1&'), 'http://craft.dev/?foo=1&bar=1');
    assert.equal(Craft.getSiteUrl('', {'foo': 1, '#': 'bar'}), 'http://craft.dev/?foo=1#bar');
    assert.equal(Craft.getSiteUrl('about', {'foo': 1, 'bar': 1}), 'http://craft.dev/index.php/about?foo=1&bar=1');
    assert.equal(Craft.getSiteUrl('about', 'foo=1&bar=1'), 'http://craft.dev/index.php/about?foo=1&bar=1');
    assert.equal(Craft.getSiteUrl('about', '?foo=1&bar=1&'), 'http://craft.dev/index.php/about?foo=1&bar=1');
    assert.equal(Craft.getSiteUrl('about?foo=1', 'bar=1'), 'http://craft.dev/index.php/about?foo=1&bar=1');
    assert.equal(Craft.getSiteUrl('about?foo=1', {'bar': 1}), 'http://craft.dev/index.php/about?foo=1&bar=1');
    assert.equal(Craft.getSiteUrl('about', {'foo': 1, '#': 'bar'}), 'http://craft.dev/index.php/about?foo=1#bar');

    assert.equal(Craft.getActionUrl('', {'foo': 1, 'bar': 1}), 'http://craft.dev/index.php/admin/actions?foo=1&bar=1');
    assert.equal(Craft.getActionUrl('', 'foo=1&bar=1'), 'http://craft.dev/index.php/admin/actions?foo=1&bar=1');
    assert.equal(Craft.getActionUrl('', '?foo=1&bar=1&'), 'http://craft.dev/index.php/admin/actions?foo=1&bar=1');
    assert.equal(Craft.getActionUrl('', {'foo': 1, '#': 'bar'}), 'http://craft.dev/index.php/admin/actions?foo=1#bar');
    assert.equal(Craft.getActionUrl('about', {'foo': 1, 'bar': 1}), 'http://craft.dev/index.php/admin/actions/about?foo=1&bar=1');
    assert.equal(Craft.getActionUrl('about', 'foo=1&bar=1'), 'http://craft.dev/index.php/admin/actions/about?foo=1&bar=1');
    assert.equal(Craft.getActionUrl('about', '?foo=1&bar=1&'), 'http://craft.dev/index.php/admin/actions/about?foo=1&bar=1');
    assert.equal(Craft.getActionUrl('about?foo=1', 'bar=1'), 'http://craft.dev/index.php/admin/actions/about?foo=1&bar=1');
    assert.equal(Craft.getActionUrl('about?foo=1', {'bar': 1}), 'http://craft.dev/index.php/admin/actions/about?foo=1&bar=1');
    assert.equal(Craft.getActionUrl('about', {'foo': 1, '#': 'bar'}), 'http://craft.dev/index.php/admin/actions/about?foo=1#bar');


    // Don't use path info
    Craft.baseUrl = 'http://craft.dev/?p=admin';
    Craft.baseCpUrl = 'http://craft.dev/?p=admin';
    Craft.baseSiteUrl = 'http://craft.dev/';
    Craft.actionUrl = 'http://craft.dev/?p=admin/actions';
    Craft.usePathInfo = false;

    assert.equal(Craft.getUrl(), 'http://craft.dev/?p=admin');
    assert.equal(Craft.getUrl(''), 'http://craft.dev/?p=admin');

    assert.equal(Craft.getUrl('', {'foo': 1, 'bar': 1}), 'http://craft.dev/?p=admin&foo=1&bar=1');
    assert.equal(Craft.getUrl('', 'foo=1&bar=1'), 'http://craft.dev/?p=admin&foo=1&bar=1');
    assert.equal(Craft.getUrl('', '?foo=1&bar=1&'), 'http://craft.dev/?p=admin&foo=1&bar=1');
    assert.equal(Craft.getUrl('', {'foo': 1, '#': 'bar'}), 'http://craft.dev/?p=admin&foo=1#bar');
    assert.equal(Craft.getUrl('about', {'foo': 1, 'bar': 1}), 'http://craft.dev/?p=admin/about&foo=1&bar=1');
    assert.equal(Craft.getUrl('about', 'foo=1&bar=1'), 'http://craft.dev/?p=admin/about&foo=1&bar=1');
    assert.equal(Craft.getUrl('about', '?foo=1&bar=1&'), 'http://craft.dev/?p=admin/about&foo=1&bar=1');
    assert.equal(Craft.getUrl('about?foo=1', 'bar=1'), 'http://craft.dev/?p=admin/about&foo=1&bar=1');
    assert.equal(Craft.getUrl('about?foo=1', {'bar': 1}), 'http://craft.dev/?p=admin/about&foo=1&bar=1');
    assert.equal(Craft.getUrl('about', {'foo': 1, '#': 'bar'}), 'http://craft.dev/?p=admin/about&foo=1#bar');

    assert.equal(Craft.getSiteUrl('', {'foo': 1, 'bar': 1}), 'http://craft.dev/?foo=1&bar=1');
    assert.equal(Craft.getSiteUrl('', 'foo=1&bar=1'), 'http://craft.dev/?foo=1&bar=1');
    assert.equal(Craft.getSiteUrl('', '?foo=1&bar=1&'), 'http://craft.dev/?foo=1&bar=1');
    assert.equal(Craft.getSiteUrl('', {'foo': 1, '#': 'bar'}), 'http://craft.dev/?foo=1#bar');
    assert.equal(Craft.getSiteUrl('about', {'foo': 1, 'bar': 1}), 'http://craft.dev/?p=about&foo=1&bar=1');
    assert.equal(Craft.getSiteUrl('about', 'foo=1&bar=1'), 'http://craft.dev/?p=about&foo=1&bar=1');
    assert.equal(Craft.getSiteUrl('about', '?foo=1&bar=1&'), 'http://craft.dev/?p=about&foo=1&bar=1');
    assert.equal(Craft.getSiteUrl('about?foo=1', 'bar=1'), 'http://craft.dev/?p=about&foo=1&bar=1');
    assert.equal(Craft.getSiteUrl('about?foo=1', {'bar': 1}), 'http://craft.dev/?p=about&foo=1&bar=1');
    assert.equal(Craft.getSiteUrl('about', {'foo': 1, '#': 'bar'}), 'http://craft.dev/?p=about&foo=1#bar');

    assert.equal(Craft.getActionUrl('', {'foo': 1, 'bar': 1}), 'http://craft.dev/?p=admin/actions&foo=1&bar=1');
    assert.equal(Craft.getActionUrl('', 'foo=1&bar=1'), 'http://craft.dev/?p=admin/actions&foo=1&bar=1');
    assert.equal(Craft.getActionUrl('', '?foo=1&bar=1&'), 'http://craft.dev/?p=admin/actions&foo=1&bar=1');
    assert.equal(Craft.getActionUrl('', {'foo': 1, '#': 'bar'}), 'http://craft.dev/?p=admin/actions&foo=1#bar');
    assert.equal(Craft.getActionUrl('about', {'foo': 1, 'bar': 1}), 'http://craft.dev/?p=admin/actions/about&foo=1&bar=1');
    assert.equal(Craft.getActionUrl('about', 'foo=1&bar=1'), 'http://craft.dev/?p=admin/actions/about&foo=1&bar=1');
    assert.equal(Craft.getActionUrl('about', '?foo=1&bar=1&'), 'http://craft.dev/?p=admin/actions/about&foo=1&bar=1');
    assert.equal(Craft.getActionUrl('about?foo=1', 'bar=1'), 'http://craft.dev/?p=admin/actions/about&foo=1&bar=1');
    assert.equal(Craft.getActionUrl('about?foo=1', {'bar': 1}), 'http://craft.dev/?p=admin/actions/about&foo=1&bar=1');
    assert.equal(Craft.getActionUrl('about', {'foo': 1, '#': 'bar'}), 'http://craft.dev/?p=admin/actions/about&foo=1#bar');

    Craft.baseUrl = "http://craft.dev/admin";
    Craft.baseCpUrl = "http://craft.dev/admin";
    Craft.baseSiteUrl = "http://craft.dev/";
    Craft.actionUrl = "http://craft.dev/index.php?p=admin/actions";
    Craft.omitScriptNameInUrls = true;
    Craft.usePathInfo = false;

    assert.equal(Craft.getActionUrl('foo/bar'), 'http://craft.dev/index.php?p=admin/actions/foo/bar');
});
