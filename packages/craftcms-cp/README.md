# @craftcms/cp

Web Components library for Craft CMS Control Panel UI.

## Features

- Built with [Lit](https://lit.dev/) and [@lion/ui](https://lion-web.netlify.app/)
- Comprehensive set of accessible, themeable UI components
- TypeScript support with full type definitions
- Storybook integration for component development
- Utility functions for common CP tasks (API clients, translations, formatting, etc.)

## Installation

```bash
npm install @craftcms/cp
```

## Usage

### Importing Components

```js
import '@craftcms/cp';
```

This imports all components and makes them available as custom elements with the `craft-` prefix.

### Importing Styles

```css
@import '@craftcms/cp';
```

This will import all the styles for all components. You'll have to be using some kind of bundler for this to work. 

### Using Individual Components

```html
<craft-button variant="primary" size="medium">
  Save
</craft-button>

<craft-input
  label="Email Address"
  type="email"
  placeholder="you@example.com">
</craft-input>

<craft-card>
  <h2>Card Title</h2>
  <p>Card content goes here</p>
</craft-card>
```

### Importing Utilities

You can import utility functions directly:

```js
import { t, formatNumber, actionClient, apiClient } from '@craftcms/cp';

// Translation
const message = t('app', 'Welcome');

// Number formatting
const formatted = formatNumber(1234.56);

// API calls
const response = await apiClient.get('/api/entries');
```

Individual utilities can also be imported:

```js
import { t } from '@craftcms/cp/utilities/translate';
import { formatNumber } from '@craftcms/cp/utilities/format';
```

## Utilities

### API Clients

```js
import { actionClient, apiClient } from '@craftcms/cp';

// Controller actions
await actionClient.post('users/save-user', data);

// API endpoints
await apiClient.get('/api/entries');
```

### Translation

```js
import { t, formatMessage } from '@craftcms/cp';

t('category', 'message');
formatMessage('Hello {name}', { name: 'World' });
```

### Formatting

```js
import { formatNumber } from '@craftcms/cp';

formatNumber(1234.56); // Locale-aware number formatting
```

### Cookie Utilities

```js
import { getCookie, setCookie, deleteCookie } from '@craftcms/cp';

setCookie('name', 'value', 7); // expires in 7 days
const value = getCookie('name');
deleteCookie('name');
```

## Development

### Scripts

```bash
# Run tests
npm test

# Run tests in watch mode
npm run test:dev

# Build the package
npm run build

# Development build with watch mode
npm run dev

# Run Storybook
npm run storybook

# Build Storybook
npm run build:storybook

# Type checking
npm run check:types

# Format code
npm run format
```

### Testing

Tests are written using Vitest with browser and happy-dom support.

```bash
npm run test:coverage
```

## Peer Dependencies

- `lit` 3.x

- [Issues](https://github.com/craftcms/npm-packages/issues)
- [Repository](https://github.com/craftcms/npm-packages)
