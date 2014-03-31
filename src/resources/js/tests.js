test( 'Craft.getUrl()', function()
{
	// Setup for a site with path info
	Craft.baseUrl = 'http://craft.dev/admin';
	Craft.baseCpUrl = 'http://craft.dev/admin';
	Craft.baseSiteUrl = 'http://craft.dev/';
	Craft.actionUrl = 'http://craft.dev/index.php/admin/actions';
	Craft.resourceUrl = 'http://craft.dev/admin/resources';
	Craft.omitScriptNameInUrls = true;
	Craft.usePathInfo = true;
	Craft.resourceTrigger = 'resources';
	Craft.actionTrigger = 'actions';
	Craft.path = '';
	Craft.locale = 'en_us';
	Craft.translations = [];
	Craft.maxUploadSize = 33554432;

	// Test an absolute URL
	equal(Craft.getUrl('http://example.com/'), 'http://example.com/');

	equal(Craft.getUrl(), 'http://craft.dev/admin');
	equal(Craft.getUrl(''), 'http://craft.dev/admin');

	equal(Craft.getUrl('', {'foo': 1, 'bar': 1}), 'http://craft.dev/admin?foo=1&bar=1');
	equal(Craft.getUrl('', 'foo=1&bar=1'), 'http://craft.dev/admin?foo=1&bar=1');
	equal(Craft.getUrl('', '?foo=1&bar=1&'), 'http://craft.dev/admin?foo=1&bar=1');
	equal(Craft.getUrl('', {'foo': 1, '#': 'bar'}), 'http://craft.dev/admin?foo=1#bar');
	equal(Craft.getUrl('about', {'foo': 1, 'bar': 1}), 'http://craft.dev/admin/about?foo=1&bar=1');
	equal(Craft.getUrl('about', 'foo=1&bar=1'), 'http://craft.dev/admin/about?foo=1&bar=1');
	equal(Craft.getUrl('about', '?foo=1&bar=1&'), 'http://craft.dev/admin/about?foo=1&bar=1');
	equal(Craft.getUrl('about?foo=1', 'bar=1'), 'http://craft.dev/admin/about?foo=1&bar=1');
	equal(Craft.getUrl('about?foo=1', {'bar': 1}), 'http://craft.dev/admin/about?foo=1&bar=1');
	equal(Craft.getUrl('about', {'foo': 1, '#': 'bar'}), 'http://craft.dev/admin/about?foo=1#bar');

	equal(Craft.getSiteUrl('', {'foo': 1, 'bar': 1}), 'http://craft.dev/?foo=1&bar=1');
	equal(Craft.getSiteUrl('', 'foo=1&bar=1'), 'http://craft.dev/?foo=1&bar=1');
	equal(Craft.getSiteUrl('', '?foo=1&bar=1&'), 'http://craft.dev/?foo=1&bar=1');
	equal(Craft.getSiteUrl('', {'foo': 1, '#': 'bar'}), 'http://craft.dev/?foo=1#bar');
	equal(Craft.getSiteUrl('about', {'foo': 1, 'bar': 1}), 'http://craft.dev/about?foo=1&bar=1');
	equal(Craft.getSiteUrl('about', 'foo=1&bar=1'), 'http://craft.dev/about?foo=1&bar=1');
	equal(Craft.getSiteUrl('about', '?foo=1&bar=1&'), 'http://craft.dev/about?foo=1&bar=1');
	equal(Craft.getSiteUrl('about?foo=1', 'bar=1'), 'http://craft.dev/about?foo=1&bar=1');
	equal(Craft.getSiteUrl('about?foo=1', {'bar': 1}), 'http://craft.dev/about?foo=1&bar=1');
	equal(Craft.getSiteUrl('about', {'foo': 1, '#': 'bar'}), 'http://craft.dev/about?foo=1#bar');

	equal(Craft.getResourceUrl('', {'foo': 1, 'bar': 1}), 'http://craft.dev/admin/resources?foo=1&bar=1');
	equal(Craft.getResourceUrl('', 'foo=1&bar=1'), 'http://craft.dev/admin/resources?foo=1&bar=1');
	equal(Craft.getResourceUrl('', '?foo=1&bar=1&'), 'http://craft.dev/admin/resources?foo=1&bar=1');
	equal(Craft.getResourceUrl('', {'foo': 1, '#': 'bar'}), 'http://craft.dev/admin/resources?foo=1#bar');
	equal(Craft.getResourceUrl('about', {'foo': 1, 'bar': 1}), 'http://craft.dev/admin/resources/about?foo=1&bar=1');
	equal(Craft.getResourceUrl('about', 'foo=1&bar=1'), 'http://craft.dev/admin/resources/about?foo=1&bar=1');
	equal(Craft.getResourceUrl('about', '?foo=1&bar=1&'), 'http://craft.dev/admin/resources/about?foo=1&bar=1');
	equal(Craft.getResourceUrl('about?foo=1', 'bar=1'), 'http://craft.dev/admin/resources/about?foo=1&bar=1');
	equal(Craft.getResourceUrl('about?foo=1', {'bar': 1}), 'http://craft.dev/admin/resources/about?foo=1&bar=1');
	equal(Craft.getResourceUrl('about', {'foo': 1, '#': 'bar'}), 'http://craft.dev/admin/resources/about?foo=1#bar');

	equal(Craft.getActionUrl('', {'foo': 1, 'bar': 1}), 'http://craft.dev/index.php/admin/actions?foo=1&bar=1');
	equal(Craft.getActionUrl('', 'foo=1&bar=1'), 'http://craft.dev/index.php/admin/actions?foo=1&bar=1');
	equal(Craft.getActionUrl('', '?foo=1&bar=1&'), 'http://craft.dev/index.php/admin/actions?foo=1&bar=1');
	equal(Craft.getActionUrl('', {'foo': 1, '#': 'bar'}), 'http://craft.dev/index.php/admin/actions?foo=1#bar');
	equal(Craft.getActionUrl('about', {'foo': 1, 'bar': 1}), 'http://craft.dev/index.php/admin/actions/about?foo=1&bar=1');
	equal(Craft.getActionUrl('about', 'foo=1&bar=1'), 'http://craft.dev/index.php/admin/actions/about?foo=1&bar=1');
	equal(Craft.getActionUrl('about', '?foo=1&bar=1&'), 'http://craft.dev/index.php/admin/actions/about?foo=1&bar=1');
	equal(Craft.getActionUrl('about?foo=1', 'bar=1'), 'http://craft.dev/index.php/admin/actions/about?foo=1&bar=1');
	equal(Craft.getActionUrl('about?foo=1', {'bar': 1}), 'http://craft.dev/index.php/admin/actions/about?foo=1&bar=1');
	equal(Craft.getActionUrl('about', {'foo': 1, '#': 'bar'}), 'http://craft.dev/index.php/admin/actions/about?foo=1#bar');


	// Don't omit the script name
	Craft.baseUrl = 'http://craft.dev/index.php/admin';
	Craft.baseCpUrl = 'http://craft.dev/index.php/admin';
	Craft.baseSiteUrl = 'http://craft.dev/';
	Craft.actionUrl = 'http://craft.dev/index.php/admin/actions';
	Craft.resourceUrl = 'http://craft.dev/index.php/admin/resources';
	Craft.omitScriptNameInUrls = false;

	equal(Craft.getUrl(), 'http://craft.dev/index.php/admin');
	equal(Craft.getUrl(''), 'http://craft.dev/index.php/admin');

	equal(Craft.getUrl('', {'foo': 1, 'bar': 1}), 'http://craft.dev/index.php/admin?foo=1&bar=1');
	equal(Craft.getUrl('', 'foo=1&bar=1'), 'http://craft.dev/index.php/admin?foo=1&bar=1');
	equal(Craft.getUrl('', '?foo=1&bar=1&'), 'http://craft.dev/index.php/admin?foo=1&bar=1');
	equal(Craft.getUrl('', {'foo': 1, '#': 'bar'}), 'http://craft.dev/index.php/admin?foo=1#bar');
	equal(Craft.getUrl('about', {'foo': 1, 'bar': 1}), 'http://craft.dev/index.php/admin/about?foo=1&bar=1');
	equal(Craft.getUrl('about', 'foo=1&bar=1'), 'http://craft.dev/index.php/admin/about?foo=1&bar=1');
	equal(Craft.getUrl('about', '?foo=1&bar=1&'), 'http://craft.dev/index.php/admin/about?foo=1&bar=1');
	equal(Craft.getUrl('about?foo=1', 'bar=1'), 'http://craft.dev/index.php/admin/about?foo=1&bar=1');
	equal(Craft.getUrl('about?foo=1', {'bar': 1}), 'http://craft.dev/index.php/admin/about?foo=1&bar=1');
	equal(Craft.getUrl('about', {'foo': 1, '#': 'bar'}), 'http://craft.dev/index.php/admin/about?foo=1#bar');

	equal(Craft.getSiteUrl('', {'foo': 1, 'bar': 1}), 'http://craft.dev/?foo=1&bar=1');
	equal(Craft.getSiteUrl('', 'foo=1&bar=1'), 'http://craft.dev/?foo=1&bar=1');
	equal(Craft.getSiteUrl('', '?foo=1&bar=1&'), 'http://craft.dev/?foo=1&bar=1');
	equal(Craft.getSiteUrl('', {'foo': 1, '#': 'bar'}), 'http://craft.dev/?foo=1#bar');
	equal(Craft.getSiteUrl('about', {'foo': 1, 'bar': 1}), 'http://craft.dev/index.php/about?foo=1&bar=1');
	equal(Craft.getSiteUrl('about', 'foo=1&bar=1'), 'http://craft.dev/index.php/about?foo=1&bar=1');
	equal(Craft.getSiteUrl('about', '?foo=1&bar=1&'), 'http://craft.dev/index.php/about?foo=1&bar=1');
	equal(Craft.getSiteUrl('about?foo=1', 'bar=1'), 'http://craft.dev/index.php/about?foo=1&bar=1');
	equal(Craft.getSiteUrl('about?foo=1', {'bar': 1}), 'http://craft.dev/index.php/about?foo=1&bar=1');
	equal(Craft.getSiteUrl('about', {'foo': 1, '#': 'bar'}), 'http://craft.dev/index.php/about?foo=1#bar');

	equal(Craft.getResourceUrl('', {'foo': 1, 'bar': 1}), 'http://craft.dev/index.php/admin/resources?foo=1&bar=1');
	equal(Craft.getResourceUrl('', 'foo=1&bar=1'), 'http://craft.dev/index.php/admin/resources?foo=1&bar=1');
	equal(Craft.getResourceUrl('', '?foo=1&bar=1&'), 'http://craft.dev/index.php/admin/resources?foo=1&bar=1');
	equal(Craft.getResourceUrl('', {'foo': 1, '#': 'bar'}), 'http://craft.dev/index.php/admin/resources?foo=1#bar');
	equal(Craft.getResourceUrl('about', {'foo': 1, 'bar': 1}), 'http://craft.dev/index.php/admin/resources/about?foo=1&bar=1');
	equal(Craft.getResourceUrl('about', 'foo=1&bar=1'), 'http://craft.dev/index.php/admin/resources/about?foo=1&bar=1');
	equal(Craft.getResourceUrl('about', '?foo=1&bar=1&'), 'http://craft.dev/index.php/admin/resources/about?foo=1&bar=1');
	equal(Craft.getResourceUrl('about?foo=1', 'bar=1'), 'http://craft.dev/index.php/admin/resources/about?foo=1&bar=1');
	equal(Craft.getResourceUrl('about?foo=1', {'bar': 1}), 'http://craft.dev/index.php/admin/resources/about?foo=1&bar=1');
	equal(Craft.getResourceUrl('about', {'foo': 1, '#': 'bar'}), 'http://craft.dev/index.php/admin/resources/about?foo=1#bar');

	equal(Craft.getActionUrl('', {'foo': 1, 'bar': 1}), 'http://craft.dev/index.php/admin/actions?foo=1&bar=1');
	equal(Craft.getActionUrl('', 'foo=1&bar=1'), 'http://craft.dev/index.php/admin/actions?foo=1&bar=1');
	equal(Craft.getActionUrl('', '?foo=1&bar=1&'), 'http://craft.dev/index.php/admin/actions?foo=1&bar=1');
	equal(Craft.getActionUrl('', {'foo': 1, '#': 'bar'}), 'http://craft.dev/index.php/admin/actions?foo=1#bar');
	equal(Craft.getActionUrl('about', {'foo': 1, 'bar': 1}), 'http://craft.dev/index.php/admin/actions/about?foo=1&bar=1');
	equal(Craft.getActionUrl('about', 'foo=1&bar=1'), 'http://craft.dev/index.php/admin/actions/about?foo=1&bar=1');
	equal(Craft.getActionUrl('about', '?foo=1&bar=1&'), 'http://craft.dev/index.php/admin/actions/about?foo=1&bar=1');
	equal(Craft.getActionUrl('about?foo=1', 'bar=1'), 'http://craft.dev/index.php/admin/actions/about?foo=1&bar=1');
	equal(Craft.getActionUrl('about?foo=1', {'bar': 1}), 'http://craft.dev/index.php/admin/actions/about?foo=1&bar=1');
	equal(Craft.getActionUrl('about', {'foo': 1, '#': 'bar'}), 'http://craft.dev/index.php/admin/actions/about?foo=1#bar');


	// Don't use path info
	Craft.baseUrl = 'http://craft.dev/?p=admin';
	Craft.baseCpUrl = 'http://craft.dev/?p=admin';
	Craft.baseSiteUrl = 'http://craft.dev/';
	Craft.actionUrl = 'http://craft.dev/?p=admin/actions';
	Craft.resourceUrl = 'http://craft.dev/?p=admin/resources';
	Craft.usePathInfo = false;

	equal(Craft.getUrl(), 'http://craft.dev/?p=admin');
	equal(Craft.getUrl(''), 'http://craft.dev/?p=admin');

	equal(Craft.getUrl('', {'foo': 1, 'bar': 1}), 'http://craft.dev/?p=admin&foo=1&bar=1');
	equal(Craft.getUrl('', 'foo=1&bar=1'), 'http://craft.dev/?p=admin&foo=1&bar=1');
	equal(Craft.getUrl('', '?foo=1&bar=1&'), 'http://craft.dev/?p=admin&foo=1&bar=1');
	equal(Craft.getUrl('', {'foo': 1, '#': 'bar'}), 'http://craft.dev/?p=admin&foo=1#bar');
	equal(Craft.getUrl('about', {'foo': 1, 'bar': 1}), 'http://craft.dev/?p=admin/about&foo=1&bar=1');
	equal(Craft.getUrl('about', 'foo=1&bar=1'), 'http://craft.dev/?p=admin/about&foo=1&bar=1');
	equal(Craft.getUrl('about', '?foo=1&bar=1&'), 'http://craft.dev/?p=admin/about&foo=1&bar=1');
	equal(Craft.getUrl('about?foo=1', 'bar=1'), 'http://craft.dev/?p=admin/about&foo=1&bar=1');
	equal(Craft.getUrl('about?foo=1', {'bar': 1}), 'http://craft.dev/?p=admin/about&foo=1&bar=1');
	equal(Craft.getUrl('about', {'foo': 1, '#': 'bar'}), 'http://craft.dev/?p=admin/about&foo=1#bar');

	equal(Craft.getSiteUrl('', {'foo': 1, 'bar': 1}), 'http://craft.dev/?foo=1&bar=1');
	equal(Craft.getSiteUrl('', 'foo=1&bar=1'), 'http://craft.dev/?foo=1&bar=1');
	equal(Craft.getSiteUrl('', '?foo=1&bar=1&'), 'http://craft.dev/?foo=1&bar=1');
	equal(Craft.getSiteUrl('', {'foo': 1, '#': 'bar'}), 'http://craft.dev/?foo=1#bar');
	equal(Craft.getSiteUrl('about', {'foo': 1, 'bar': 1}), 'http://craft.dev/?p=about&foo=1&bar=1');
	equal(Craft.getSiteUrl('about', 'foo=1&bar=1'), 'http://craft.dev/?p=about&foo=1&bar=1');
	equal(Craft.getSiteUrl('about', '?foo=1&bar=1&'), 'http://craft.dev/?p=about&foo=1&bar=1');
	equal(Craft.getSiteUrl('about?foo=1', 'bar=1'), 'http://craft.dev/?p=about&foo=1&bar=1');
	equal(Craft.getSiteUrl('about?foo=1', {'bar': 1}), 'http://craft.dev/?p=about&foo=1&bar=1');
	equal(Craft.getSiteUrl('about', {'foo': 1, '#': 'bar'}), 'http://craft.dev/?p=about&foo=1#bar');

	equal(Craft.getResourceUrl('', {'foo': 1, 'bar': 1}), 'http://craft.dev/?p=admin/resources&foo=1&bar=1');
	equal(Craft.getResourceUrl('', 'foo=1&bar=1'), 'http://craft.dev/?p=admin/resources&foo=1&bar=1');
	equal(Craft.getResourceUrl('', '?foo=1&bar=1&'), 'http://craft.dev/?p=admin/resources&foo=1&bar=1');
	equal(Craft.getResourceUrl('', {'foo': 1, '#': 'bar'}), 'http://craft.dev/?p=admin/resources&foo=1#bar');
	equal(Craft.getResourceUrl('about', {'foo': 1, 'bar': 1}), 'http://craft.dev/?p=admin/resources/about&foo=1&bar=1');
	equal(Craft.getResourceUrl('about', 'foo=1&bar=1'), 'http://craft.dev/?p=admin/resources/about&foo=1&bar=1');
	equal(Craft.getResourceUrl('about', '?foo=1&bar=1&'), 'http://craft.dev/?p=admin/resources/about&foo=1&bar=1');
	equal(Craft.getResourceUrl('about?foo=1', 'bar=1'), 'http://craft.dev/?p=admin/resources/about&foo=1&bar=1');
	equal(Craft.getResourceUrl('about?foo=1', {'bar': 1}), 'http://craft.dev/?p=admin/resources/about&foo=1&bar=1');
	equal(Craft.getResourceUrl('about', {'foo': 1, '#': 'bar'}), 'http://craft.dev/?p=admin/resources/about&foo=1#bar');

	equal(Craft.getActionUrl('', {'foo': 1, 'bar': 1}), 'http://craft.dev/?p=admin/actions&foo=1&bar=1');
	equal(Craft.getActionUrl('', 'foo=1&bar=1'), 'http://craft.dev/?p=admin/actions&foo=1&bar=1');
	equal(Craft.getActionUrl('', '?foo=1&bar=1&'), 'http://craft.dev/?p=admin/actions&foo=1&bar=1');
	equal(Craft.getActionUrl('', {'foo': 1, '#': 'bar'}), 'http://craft.dev/?p=admin/actions&foo=1#bar');
	equal(Craft.getActionUrl('about', {'foo': 1, 'bar': 1}), 'http://craft.dev/?p=admin/actions/about&foo=1&bar=1');
	equal(Craft.getActionUrl('about', 'foo=1&bar=1'), 'http://craft.dev/?p=admin/actions/about&foo=1&bar=1');
	equal(Craft.getActionUrl('about', '?foo=1&bar=1&'), 'http://craft.dev/?p=admin/actions/about&foo=1&bar=1');
	equal(Craft.getActionUrl('about?foo=1', 'bar=1'), 'http://craft.dev/?p=admin/actions/about&foo=1&bar=1');
	equal(Craft.getActionUrl('about?foo=1', {'bar': 1}), 'http://craft.dev/?p=admin/actions/about&foo=1&bar=1');
	equal(Craft.getActionUrl('about', {'foo': 1, '#': 'bar'}), 'http://craft.dev/?p=admin/actions/about&foo=1#bar');

	Craft.baseUrl = "http://craft.dev/admin";
	Craft.baseCpUrl = "http://craft.dev/admin";
	Craft.baseSiteUrl = "http://craft.dev/";
	Craft.actionUrl = "http://craft.dev/index.php?p=admin/actions";
	Craft.resourceUrl = "http://craft.dev/admin/resources";
	Craft.omitScriptNameInUrls = true;
	Craft.usePathInfo = false;

	equal(Craft.getActionUrl('update/prepare'), 'http://craft.dev/index.php?p=admin/actions/update/prepare');

});
