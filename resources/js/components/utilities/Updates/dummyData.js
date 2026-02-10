export default {
  total: 6,
  critical: true,
  allowUpdates: true,
  updates: {
    cms: {
      status: 'eligible',
      statusText: undefined,
      renewalPrice: null,
      renewalCurrency: null,
      renewalUrl: null,
      releases: [
        {
          version: '5.10.0',
          date: '2026-02-15T00:00:00',
          critical: true,
          notes: `
          <h3>Security</h3>
          <ul>
          <li>Fixed a critical security vulnerability in user authentication.</li>
          </ul>
          <blockquote class="warning">
          <p><strong>Warning:</strong> This is a critical security update. Please update immediately.</p>
          </blockquote>
          <h3>Added</h3>
          <ul>
          <li>Added new dashboard widgets for analytics.</li>
          <li>Added support for WebP image uploads.</li>
          </ul>
          <h3>Fixed</h3>
          <ul>
          <li>Fixed a bug where entries weren't saving properly.</li>
          <li>Fixed an issue with Matrix field rendering.</li>
          </ul>
          `,
        },
        {
          version: '5.9.8',
          date: '2026-02-10T00:00:00',
          critical: false,
          notes: `
          <ul>
          <li>Minor bug fixes and performance improvements.</li>
          <li>Updated dependencies to latest versions.</li>
          </ul>
          `,
        },
        {
          version: '5.9.7',
          date: '2026-02-05T00:00:00',
          critical: false,
          notes: null,
        },
      ],
      phpConstraint: '^8.2',
      packageName: 'craftcms/cms',
      abandoned: false,
      replacementName: null,
      replacementHandle: null,
      replacementUrl: null,
      handle: 'craft',
      name: 'Craft CMS',
      latestVersion: '5.10.0',
    },
    plugins: [
      // Plugin with normal update
      {
        status: 'eligible',
        statusText: undefined,
        renewalPrice: null,
        renewalCurrency: null,
        renewalUrl: null,
        releases: [
          {
            version: '4.0.0',
            date: '2026-02-12T00:00:00',
            critical: false,
            notes: `
            <h3>Changed</h3>
            <ul>
            <li>Complete rewrite with improved performance.</li>
            <li>New admin interface.</li>
            </ul>
            `,
          },
          {
            version: '3.5.2',
            date: '2026-01-20T00:00:00',
            critical: false,
            notes: `
            <ul>
            <li>Bug fixes for compatibility with Craft 5.9.</li>
            </ul>
            `,
          },
        ],
        phpConstraint: '^8.2',
        packageName: 'craftcms/commerce',
        abandoned: false,
        replacementName: null,
        replacementHandle: null,
        replacementUrl: null,
        handle: 'commerce',
        name: 'Craft Commerce',
        latestVersion: '4.0.0',
      },
      // Plugin with expired license
      {
        status: 'expired',
        statusText:
          'Your license has expired. Renew your license to get this update.',
        renewalPrice: '$59',
        renewalCurrency: 'USD',
        renewalUrl: 'https://plugins.craftcms.com/renew/seomatic',
        releases: [
          {
            version: '5.2.0',
            date: '2026-02-08T00:00:00',
            critical: false,
            notes: `
            <ul>
            <li>Added support for new meta tags.</li>
            <li>Improved sitemap generation.</li>
            </ul>
            `,
          },
        ],
        phpConstraint: '^8.2',
        packageName: 'nystudio107/craft-seomatic',
        abandoned: false,
        replacementName: null,
        replacementHandle: null,
        replacementUrl: null,
        handle: 'seomatic',
        name: 'SEOmatic',
        latestVersion: '5.2.0',
        ctaText: 'Renew license',
        ctaUrl: 'https://plugins.craftcms.com/renew/seomatic',
      },
      // Abandoned plugin
      {
        status: 'eligible',
        statusText:
          'This plugin has been abandoned. Consider switching to Imager X as an alternative.',
        renewalPrice: null,
        renewalCurrency: null,
        renewalUrl: null,
        releases: [
          {
            version: '2.1.0',
            date: '2024-06-15T00:00:00',
            critical: false,
            notes: `
            <ul>
            <li>Final release before abandonment.</li>
            </ul>
            `,
          },
        ],
        phpConstraint: '^8.0',
        packageName: 'example/old-image-plugin',
        abandoned: true,
        replacementName: 'Imager X',
        replacementHandle: 'imager-x',
        replacementUrl: 'https://plugins.craftcms.com/imager-x',
        handle: 'old-image-plugin',
        name: 'Old Image Plugin',
        latestVersion: '2.1.0',
      },
      // Plugin with PHP version issue
      {
        status: 'phpIssue',
        statusText:
          'This update requires PHP 8.3 or later, but your server is running PHP 8.2.',
        renewalPrice: null,
        renewalCurrency: null,
        renewalUrl: null,
        releases: [
          {
            version: '3.0.0',
            date: '2026-02-01T00:00:00',
            critical: false,
            notes: `
            <h3>Breaking Changes</h3>
            <ul>
            <li>Now requires PHP 8.3 or later.</li>
            <li>Dropped support for Craft 4.</li>
            </ul>
            `,
          },
        ],
        phpConstraint: '^8.3',
        packageName: 'example/advanced-plugin',
        abandoned: false,
        replacementName: null,
        replacementHandle: null,
        replacementUrl: null,
        handle: 'advanced-plugin',
        name: 'Advanced Plugin',
        latestVersion: '3.0.0',
      },
      // Plugin with external CTA URL
      {
        status: 'eligible',
        statusText: undefined,
        renewalPrice: null,
        renewalCurrency: null,
        renewalUrl: null,
        releases: [
          {
            version: '2.5.0',
            date: '2026-02-14T00:00:00',
            critical: false,
            notes: `
            <ul>
            <li>New features and improvements.</li>
            </ul>
            `,
          },
        ],
        phpConstraint: '^8.2',
        packageName: 'example/pro-plugin',
        abandoned: false,
        replacementName: null,
        replacementHandle: null,
        replacementUrl: null,
        handle: 'pro-plugin',
        name: 'Pro Plugin',
        latestVersion: '2.5.0',
        ctaText: 'Buy upgrade',
        ctaUrl: 'https://example.com/buy-upgrade',
        altCtaText: 'Learn more',
        altCtaUrl: 'https://example.com/whats-new',
      },
      // Plugin with release without date or notes
      {
        status: 'eligible',
        statusText: undefined,
        renewalPrice: null,
        renewalCurrency: null,
        renewalUrl: null,
        releases: [
          {
            version: '1.0.1',
            date: null,
            critical: false,
            notes: null,
          },
        ],
        phpConstraint: null,
        packageName: 'example/simple-plugin',
        abandoned: false,
        replacementName: null,
        replacementHandle: null,
        replacementUrl: null,
        handle: 'simple-plugin',
        name: 'Simple Plugin',
        latestVersion: '1.0.1',
      },
    ],
  },
};
