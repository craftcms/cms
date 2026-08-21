import type {Meta, StoryObj} from '@storybook/vue3-vite';
import {useForm} from '@inertiajs/vue3';

import AppLayout from './AppLayout.vue';

/**
 * Highlights a slot's rendered area so each extension point is easy to spot.
 */
const SlotMarker = {
    props: {
        name: {type: String, required: true},
    },
    template: `
    <div class="slot-marker">
      <span class="slot-marker__label">#{{ name }}</span>
      <slot></slot>
    </div>
  `,
};

const markerStyles = `
  .slot-marker {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.75rem;
    border: 1px dashed oklch(58% 0.2 290);
    border-radius: 4px;
    background: oklch(58% 0.2 290 / 0.06);
    font-size: 0.8125rem;
  }

  .slot-marker__label {
    font-family: var(--c-font-mono, monospace);
    font-size: 0.6875rem;
    font-weight: 600;
    color: oklch(45% 0.2 290);
    white-space: nowrap;
  }
`;

const meta = {
    component: AppLayout,
    parameters: {
        layout: 'fullscreen',
        inertia: {
            title: 'Page Title',
            crumbs: [
                {label: 'Content', href: '#'},
                {label: 'Entries', href: '#'},
            ],
            queue: {
                enabled: false,
                displayedJob: null,
                hasReservedJobs: false,
                hasWaitingJobs: false,
            },
            craft: {
                nav: [
                    {
                        label: 'Dashboard',
                        url: '#',
                        icon: 'gauge',
                        selected: false,
                    },
                    {
                        label: 'Entries',
                        url: '#',
                        icon: 'newspaper',
                        selected: true,
                    },
                    {
                        label: 'Settings',
                        url: '#',
                        icon: 'gear',
                        selected: false,
                    },
                ],
            },
        },
        docs: {
            description: {
                component: `
The Control Panel app shell. Provides the same extension points as Craft 5's
\`_layouts/cp.twig\` template:

| Craft 5 \`cp.twig\` block/variable | \`AppLayout\` equivalent |
| --- | --- |
| \`block main\` | \`main\` slot (replaces everything inside the main column) |
| \`crumbs\` | \`crumbs\` page prop / \`breadcrumbs\` slot |
| \`contextMenu\` | \`context-menu\` slot |
| \`block header\` | \`header\` slot |
| \`block pageTitle\` / \`title\` | \`title\` slot / \`title\` prop |
| \`#revision-indicators\` | \`title-badge\` slot |
| \`toolbar\` | \`toolbar\` slot |
| \`additionalButtons\` | \`additional-buttons\` slot |
| \`actionButton\` | \`actions\` slot (full override) |
| \`block submitButton\` | \`submit-button\` slot |
| \`actionMenu\` / \`formActions\` | \`formActions\` / \`formAdditionalActions\` / \`defaultFormActions\` props |
| \`contentNotice\` | \`content-notice\` slot |
| \`tabs\` | \`tabs\` slot |
| \`sidebar\` | \`sidebar\` slot — defaults to a secondary nav built from the \`subnav\` page prop, with a \`subnav-actions\` slot below it |
| \`block content\` | default slot |
| \`footer\` (content pane) | \`content-footer\` slot |
| \`details\` | \`details\` slot |
| \`errorSummary\` | \`error-summary\` slot |
| global footer | \`footer\` slot |
| \`fullPageForm\` | \`form\` prop |
| \`showHeader: false\` | pass an empty \`header\` slot |
        `,
            },
        },
    },
} satisfies Meta<typeof AppLayout>;

export default meta;
type Story = StoryObj<typeof meta>;

const sampleContent = `
  <p>Page content goes in the default slot.</p>
`;

export const Default: Story = {
    render: (args) => ({
        components: {AppLayout},
        setup: () => ({args}),
        template: `
      <AppLayout v-bind="args">
        ${sampleContent}
      </AppLayout>
    `,
    }),
    args: {
        title: 'Default Layout',
    },
};

/**
 * Every extension point filled at once, each highlighted with a dashed marker.
 */
export const AllExtensionPoints: Story = {
    render: (args) => ({
        components: {AppLayout, SlotMarker},
        setup() {
            const form = useForm({name: ''});
            return {args, form};
        },
        template: `
      <div>
        <component is="style">${markerStyles}</component>
        <AppLayout v-bind="args" :form="form">
          <template #context-menu>
            <SlotMarker name="context-menu">
              <craft-button size="small" type="button">Site: All</craft-button>
            </SlotMarker>
          </template>

          <template #title-badge>
            <SlotMarker name="title-badge">
              <craft-chip>Draft</craft-chip>
            </SlotMarker>
          </template>

          <template #toolbar>
            <SlotMarker name="toolbar">
              <craft-button size="small" type="button">Filter</craft-button>
              <craft-button size="small" type="button">Search</craft-button>
            </SlotMarker>
          </template>

          <template #additional-buttons>
            <SlotMarker name="additional-buttons">
              <craft-button type="button">Preview</craft-button>
            </SlotMarker>
          </template>

          <template #content-notice>
            <SlotMarker name="content-notice">
              This entry was updated 5 minutes ago.
            </SlotMarker>
          </template>

          <template #tabs>
            <SlotMarker name="tabs">
              <craft-button size="small" type="button">Content</craft-button>
              <craft-button size="small" type="button">SEO</craft-button>
            </SlotMarker>
          </template>

          <template #sidebar>
            <SlotMarker name="sidebar">
              Filters or secondary navigation
            </SlotMarker>
          </template>

          <template #error-summary>
            <SlotMarker name="error-summary">
              Custom error summary placement
            </SlotMarker>
          </template>

          ${sampleContent}

          <template #content-footer>
            <SlotMarker name="content-footer">
              Content pane footer (pagination, meta info, …)
            </SlotMarker>
          </template>

          <template #details>
            <SlotMarker name="details">
              Details / metadata pane
            </SlotMarker>
          </template>

          <template #footer>
            <SlotMarker name="footer">
              Global footer
            </SlotMarker>
          </template>
        </AppLayout>
      </div>
    `,
    }),
    args: {
        title: 'All Extension Points',
    },
};

/**
 * The same extension points as `AllExtensionPoints`, but furnished as a real
 * screen instead of labelled markers — an entry editor part-way through an
 * edit, so you can judge spacing, density, and how the regions read together.
 *
 * Three points are deliberately absent, because each one *replaces* a region
 * that the others live in, and using them would empty most of this story:
 * `main` (the whole main column), `header` (title through action buttons), and
 * `breadcrumbs` (the bar hosting `context-menu`). `actions` is out for the same
 * reason — it would swallow `additional-buttons` and `submit-button`, which are
 * shown here instead.
 *
 * The left column comes from the `subnav` page prop rather than the `sidebar`
 * slot: `subnav-actions` renders inside the default nav, so a screen can use
 * `sidebar` or `subnav-actions`, never both. A real entry editor wouldn't carry
 * a secondary nav at all — it's here so the region is exercised.
 */
export const AllExtensionPointsInContext: Story = {
    render: (args) => ({
        components: {AppLayout},
        setup() {
            const form = useForm({
                title: 'Summer sale: up to 40% off',
                slug: 'summer-sale-up-to-40-off',
                postDate: '2026-07-14',
                body: 'Our biggest sale of the year runs through the end of August.',
            });
            form.errors = {slug: 'Slug is already in use by another entry.'};
            form.hasErrors = true;
            form.isDirty = true;
            return {args, form};
        },
        template: `
      <AppLayout
        v-bind="args"
        :form="form"
        :form-additional-actions="[
          {label: 'Save as a draft', onClick: () => {}},
          {label: 'Duplicate', onClick: () => {}},
          {label: 'Delete', variant: 'danger', onClick: () => {}},
        ]"
      >
        <template #context-menu>
          <craft-button size="small" type="button" appearance="outline">
            <craft-icon name="earth-americas" slot="prefix"></craft-icon>
            English (Global)
          </craft-button>
        </template>

        <template #title>
          <h1 class="text-xl">Summer sale: up to 40% off</h1>
        </template>

        <template #title-badge>
          <craft-status status="pending" label="Draft"></craft-status>
          <craft-chip>Draft 3</craft-chip>
        </template>

        <template #toolbar>
          <craft-button size="small" type="button" appearance="outline">
            Revisions
            <craft-icon name="angle-down" slot="suffix"></craft-icon>
          </craft-button>
        </template>

        <template #additional-buttons>
          <craft-button type="button" appearance="outline">Preview</craft-button>
        </template>

        <template #submit-button>
          <craft-button type="submit" variant="accent">Publish</craft-button>
        </template>

        <template #subnav-actions>
          <div class="mt-4">
            <craft-button type="button" size="small" appearance="outline">
              <craft-icon name="plus" slot="prefix"></craft-icon>
              New entry
            </craft-button>
          </div>
        </template>

        <template #content-notice>
          <craft-callout data-color="info" size="small">
            You're editing a draft. It won't be visible on the site until it's
            published.
          </craft-callout>
        </template>

        <template #tabs>
          <craft-button-group role="tablist">
            <craft-button type="button" role="tab" appearance="outline" active="true" aria-selected="true">
              Content
            </craft-button>
            <craft-button type="button" role="tab" appearance="outline" aria-selected="false">
              SEO
              <craft-icon name="circle-exclamation" slot="suffix"></craft-icon>
            </craft-button>
            <craft-button type="button" role="tab" appearance="outline" aria-selected="false">
              Related
            </craft-button>
          </craft-button-group>
        </template>

        <template #error-summary>
          <craft-callout data-color="error" title="Couldn't save entry">
            <ul>
              <li>Slug is already in use by another entry.</li>
            </ul>
          </craft-callout>
        </template>

        <craft-field-group>
          <craft-input
            label="Title"
            name="title"
            value="Summer sale: up to 40% off"
            width="full"
          ></craft-input>

          <craft-input
            label="Slug"
            name="slug"
            value="summer-sale-up-to-40-off"
            width="full"
            error="Slug is already in use by another entry."
          ></craft-input>

          <craft-field label="Body" instructions="Appears above the fold on the campaign landing page.">
            <craft-textarea
              name="body"
              rows="5"
              value="Our biggest sale of the year runs through the end of August."
            ></craft-textarea>
          </craft-field>
        </craft-field-group>

        <template #content-footer>
          <span>Draft saved 4 minutes ago by Priya Raman</span>
          <craft-button type="button" size="small" appearance="outline">
            View revision history
          </craft-button>
        </template>

        <template #details>
          <dl class="cp-metadata-list">
            <div class="cp-metadata-list__item">
              <dt class="font-bold text-xs">Status</dt>
              <dd>Draft</dd>
            </div>
            <div class="cp-metadata-list__item">
              <dt class="font-bold text-xs">Author</dt>
              <dd>Priya Raman</dd>
            </div>
            <div class="cp-metadata-list__item">
              <dt class="font-bold text-xs">Post Date</dt>
              <dd>7/14/2026, 9:00 AM</dd>
            </div>
            <div class="cp-metadata-list__item">
              <dt class="font-bold text-xs">Expiry Date</dt>
              <dd>9/1/2026, 12:00 AM</dd>
            </div>
            <div class="cp-metadata-list__item">
              <dt class="font-bold text-xs">Slug</dt>
              <dd>summer-sale-up-to-40-off</dd>
            </div>
            <div class="cp-metadata-list__item">
              <dt class="font-bold text-xs">ID</dt>
              <dd>4821</dd>
            </div>
          </dl>
        </template>

        <template #footer>
          <span>Craft Pro 6.0.0</span>
        </template>
      </AppLayout>
    `,
    }),
    args: {
        title: 'Summer sale: up to 40% off',
    },
    parameters: {
        inertia: {
            title: 'Summer sale: up to 40% off',
            crumbs: [
                {label: 'Content', href: '#'},
                {label: 'Entries', href: '#'},
                {label: 'News', href: '#'},
            ],
            subnav: [
                {label: 'All entries', url: '#', selected: false},
                {label: 'News', url: '#', selected: true},
                {label: 'Blog', url: '#', selected: false},
                {label: 'Press releases', url: '#', selected: false},
            ],
        },
    },
};

/**
 * Equivalent of `fullPageForm` in Craft 5: pass an Inertia form and the layout
 * renders the save button, form action menu, and submits via the `save` event.
 */
export const FullPageForm: Story = {
    render: (args) => ({
        components: {AppLayout},
        setup() {
            const form = useForm({name: ''});
            return {args, form};
        },
        template: `
      <AppLayout
        v-bind="args"
        :form="form"
        :form-additional-actions="[{label: 'Delete', variant: 'danger', onClick: () => {}}]"
      >
        ${sampleContent}
      </AppLayout>
    `,
    }),
    args: {
        title: 'Edit Entry',
    },
};

/**
 * The default `error-summary` extension point rendering (equivalent of the
 * `errorSummary` variable in Craft 5).
 */
export const FormWithErrors: Story = {
    render: (args) => ({
        components: {AppLayout},
        setup() {
            const form = useForm({name: '', handle: ''});
            form.errors = {
                name: 'Name cannot be blank.',
                handle: 'Handle is already in use.',
            };
            form.hasErrors = true;
            return {args, form};
        },
        template: `
      <AppLayout v-bind="args" :form="form">
        ${sampleContent}
      </AppLayout>
    `,
    }),
    args: {
        title: 'Edit Entry',
    },
};

/**
 * When the server provides a `subnav` page prop, the `sidebar` slot's default
 * content renders it as a secondary nav (like Craft 5's settings subnav).
 * The `subnav-actions` slot adds extra controls below the nav.
 */
export const SecondaryNavigation: Story = {
    render: (args) => ({
        components: {AppLayout},
        setup: () => ({args}),
        template: `
      <AppLayout v-bind="args">
        <template #subnav-actions>
          <div class="mt-4">
            <craft-button type="button" size="small">New Group</craft-button>
          </div>
        </template>

        ${sampleContent}
      </AppLayout>
    `,
    }),
    args: {
        title: 'Sites',
    },
    parameters: {
        inertia: {
            subnav: [
                {label: 'All Sites', url: '#', selected: true},
                {label: 'Europe', url: '#', selected: false},
                {label: 'North America', url: '#', selected: false},
            ],
        },
    },
};

/**
 * Left `sidebar` and right `details` columns around the content, equivalent of
 * the `sidebar` and `details` blocks in Craft 5.
 */
export const SidebarAndDetails: Story = {
    render: (args) => ({
        components: {AppLayout, SlotMarker},
        setup: () => ({args}),
        template: `
      <div>
        <component is="style">${markerStyles}</component>
        <AppLayout v-bind="args">
          <template #sidebar>
            <SlotMarker name="sidebar">Source list</SlotMarker>
          </template>

          ${sampleContent}

          <template #details>
            <SlotMarker name="details">Metadata</SlotMarker>
          </template>
        </AppLayout>
      </div>
    `,
    }),
    args: {
        title: 'Sidebar and Details',
    },
};

/**
 * `contentNotice` and `tabs` render at the top of the content column, like the
 * content pane header in Craft 5.
 */
export const ContentNoticeAndTabs: Story = {
    render: (args) => ({
        components: {AppLayout, SlotMarker},
        setup: () => ({args}),
        template: `
      <div>
        <component is="style">${markerStyles}</component>
        <AppLayout v-bind="args">
          <template #content-notice>
            <SlotMarker name="content-notice">
              Showing the entry in its state on June 1, 2026.
            </SlotMarker>
          </template>

          <template #tabs>
            <SlotMarker name="tabs">
              <craft-button size="small" type="button">Content</craft-button>
              <craft-button size="small" type="button">SEO</craft-button>
            </SlotMarker>
          </template>

          ${sampleContent}
        </AppLayout>
      </div>
    `,
    }),
    args: {
        title: 'Content Notice and Tabs',
    },
};

/**
 * `context-menu` sits next to the breadcrumbs (like Craft 5's `contextMenu`,
 * e.g. the site picker); `toolbar` sits in the page header next to the actions.
 */
export const ContextMenuAndToolbar: Story = {
    render: (args) => ({
        components: {AppLayout, SlotMarker},
        setup: () => ({args}),
        template: `
      <div>
        <component is="style">${markerStyles}</component>
        <AppLayout v-bind="args">
          <template #context-menu>
            <SlotMarker name="context-menu">
              <craft-button size="small" type="button">Site: English</craft-button>
            </SlotMarker>
          </template>

          <template #toolbar>
            <SlotMarker name="toolbar">
              <craft-button size="small" type="button">Filter</craft-button>
            </SlotMarker>
          </template>

          ${sampleContent}
        </AppLayout>
      </div>
    `,
    }),
    args: {
        title: 'Context Menu and Toolbar',
    },
};

/**
 * Equivalent of overriding `block submitButton` in Craft 5, plus
 * `additionalButtons` before it.
 */
export const CustomSubmitButton: Story = {
    render: (args) => ({
        components: {AppLayout},
        setup() {
            const form = useForm({name: ''});
            return {args, form};
        },
        template: `
      <AppLayout v-bind="args" :form="form">
        <template #additional-buttons>
          <craft-button type="button">Save as draft</craft-button>
        </template>

        <template #submit-button>
          <craft-button type="submit" variant="accent">Publish</craft-button>
        </template>

        ${sampleContent}
      </AppLayout>
    `,
    }),
    args: {
        title: 'Custom Submit Button',
    },
};

/**
 * Equivalent of `showHeader: false` in Craft 5 — pass an empty `header` slot.
 */
export const HiddenHeader: Story = {
    render: (args) => ({
        components: {AppLayout},
        setup: () => ({args}),
        template: `
      <AppLayout v-bind="args">
        <template #header><span></span></template>
        ${sampleContent}
      </AppLayout>
    `,
    }),
    args: {
        title: 'Hidden Header',
    },
};
