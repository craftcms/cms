# AdminTable

`AdminTable` renders the data tables on the control panel's settings and index
screens. Plugins import it, and the helpers Craft's own pages build tables with,
from the `@craftcms/cms/admin-table` import-map module:

```ts
import {
  AdminTable,
  createCraftColumnHelper,
  useCraftTable,
  useServerPagination,
  useServerSort,
  SearchForm,
  DeleteButton,
} from '@craftcms/cms/admin-table';
```

Leave `vue` and `@craftcms/cms/*` out of the plugin's bundle (for example,
`build.rollupOptions.external: ['vue', /^@craftcms\/cms\//]`) so the page runs
on the control panel's own copies.

The full guide — columns and column helpers, row actions, server-side
pagination and sorting, search, reordering, selection, empty states, and the
component's props — lives in Storybook under **Elements → AdminTable Guide**
(`resources/js/modules/admin-table/AdminTable.mdx`), next to runnable examples
under **Elements → AdminTable Recipes**. Run `pnpm storybook` to browse it.
