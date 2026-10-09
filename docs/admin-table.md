# AdminTable

`AdminTable` renders the data tables on the control panel's settings and index
screens. Plugins import it, and the helpers Craft's own pages build tables with,
from the `@craftcms/cp` import-map module:

```ts
import {
  AdminTable,
  createCraftColumnHelper,
  DeleteButton,
  SearchForm,
  useCraftTable,
} from '@craftcms/cp';
```

Tables paged and sorted on the server pass `useCraftTable` an `inertia` option
(`url`, `pagination`, `sort`, `dataProp`), and each change becomes an Inertia
visit that reloads those props.

Leave `vue` and `@craftcms/cp` out of the plugin's bundle (for example,
`build.rollupOptions.external: ['vue', '@craftcms/cp']`) so the page runs on the
control panel's own copies.

The full guide — columns and column helpers, row actions, server-side
pagination and sorting, search, reordering, selection, empty states, and the
component's props — lives in Storybook under **Elements → AdminTable Guide**
(`resources/js/modules/admin-table/AdminTable.mdx`), next to runnable examples
under **Elements → AdminTable Recipes** and the table composables under
**Composables**. Run `pnpm storybook` to browse it.
