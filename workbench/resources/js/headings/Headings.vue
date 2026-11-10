<script setup lang="ts">
  import {provide, ref} from 'vue';
  import CraftInput from '@craftcms/ui/vue/CraftInput.vue';
  import CpContainer from '@/common/components/CpContainer.vue';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import HeadingSample from './HeadingSample.vue';

  const showLabels = ref(true);
  provide('showHeadingLabels', showLabels);

  const scale = [
    {level: 1, token: '--c-text-xl', use: 'Page titles'},
    {level: 2, token: '--c-text-lg', use: 'Sections within a page'},
    {
      level: 3,
      token: '--c-text-md',
      use: 'Subsections, tab names, panel titles',
    },
    {level: 4, token: '--c-text-base', use: 'Cards and widgets'},
    {level: 5, token: '--c-text-sm', use: 'Groups within a card'},
    {level: 6, token: '--c-text-sm', use: 'Small group labels'},
  ] as const;

  const name = ref('Blog Post');
  const handle = ref('blogPost');
  const uiLabelFormat = ref('{title}');

  const tabs = [
    {
      name: 'Content',
      fields: [
        {label: 'Title', handle: 'title', required: true},
        {label: 'Summary', handle: 'summary', required: true},
        {label: 'Featured Image', handle: 'featuredImage'},
        {label: 'Body', handle: 'body'},
      ],
    },
    {
      name: 'SEO',
      fields: [
        {label: 'Meta Description', handle: 'metaDescription'},
        {label: 'Social Image', handle: 'socialImage'},
      ],
    },
  ];

  const library = [
    {
      heading: 'Fields',
      groups: [
        {name: 'Text', items: ['Pull Quote', 'Byline']},
        {name: 'Relations', items: ['Related Posts', 'Authors', 'Topics']},
      ],
    },
    {
      heading: 'UI Elements',
      groups: [{name: 'Layout', items: ['Heading', 'Tip', 'Horizontal Rule']}],
    },
  ];
</script>

<template>
  <LayoutSlot name="content-actions">
    <craft-button
      type="button"
      :aria-pressed="String(showLabels)"
      @click="showLabels = !showLabels"
    >
      {{ showLabels ? 'Hide labels' : 'Show labels' }}
    </craft-button>
  </LayoutSlot>

  <LayoutSlot name="content-details">
    <div class="grid gap-md p-md">
      <HeadingSample :level="3">Details</HeadingSample>
      <dl class="cp-metadata-list">
        <div class="cp-metadata-list__item">
          <dt>ID</dt>
          <dd>4</dd>
        </div>
        <div class="cp-metadata-list__item">
          <dt>Created at</dt>
          <dd>Mar 2, 2026</dd>
        </div>
        <div class="cp-metadata-list__item">
          <dt>Updated at</dt>
          <dd>Nov 9, 2026</dd>
        </div>
      </dl>
      <HeadingSample :level="4">Used by</HeadingSample>
      <ul class="grid gap-sm">
        <li class="flex justify-between gap-sm">
          <span>Blog</span><span class="text-(--c-text-quiet)">Section</span>
        </li>
        <li class="flex justify-between gap-sm">
          <span>Related Content</span>
          <span class="text-(--c-text-quiet)">Matrix field</span>
        </li>
      </ul>
    </div>
  </LayoutSlot>

  <CpContainer>
    <div class="grid gap-lg">
      <craft-pane appearance="raised">
        <div class="grid gap-md">
          <div class="grid gap-xs">
            <HeadingSample :level="2">Scale</HeadingSample>
            <p class="text-sm text-(--c-text-quiet)">
              Each class with its size token. The page title above is
              <code>.cp-h1</code>; body text is <code>--c-text-base</code>.
            </p>
          </div>
          <table class="data fullwidth">
            <thead>
              <tr>
                <th scope="col">Sample</th>
                <th scope="col">Token</th>
                <th scope="col">Use</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in scale" :key="row.level">
                <td>
                  <HeadingSample :level="row.level" tag="div"
                    >Field Layout</HeadingSample
                  >
                </td>
                <td>
                  <code>{{ row.token }}</code>
                </td>
                <td>{{ row.use }}</td>
              </tr>
              <tr>
                <td>Body text, for comparison</td>
                <td><code>--c-text-base</code></td>
                <td>Paragraphs, inputs, table cells</td>
              </tr>
            </tbody>
          </table>
        </div>
      </craft-pane>

      <craft-pane appearance="raised">
        <div class="grid gap-lg">
          <div class="grid gap-xs">
            <HeadingSample :level="2">General</HeadingSample>
            <p class="text-sm text-(--c-text-quiet)">
              How this entry type is named and identified.
            </p>
          </div>
          <div class="grid gap-md md:grid-cols-2">
            <CraftInput
              v-model="name"
              label="Name"
              name="name"
              help-text="What this entry type will be called in the control panel."
              required
            />
            <CraftInput
              v-model="handle"
              label="Handle"
              name="handle"
              help-text="How you’ll refer to this entry type in the templates."
              required
            />
          </div>

          <hr />

          <div class="grid gap-xs">
            <HeadingSample :level="3">Title</HeadingSample>
            <p class="text-sm text-(--c-text-quiet)">
              Entries of this type get a title field unless you turn it off.
            </p>
          </div>
          <CraftInput
            v-model="uiLabelFormat"
            label="UI Label Format"
            name="uiLabelFormat"
            help-text="How entries of this type should be labeled in the control panel."
          />
        </div>
      </craft-pane>

      <craft-pane appearance="raised">
        <div class="grid gap-md">
          <div class="grid gap-xs">
            <HeadingSample :level="2">Field Layout</HeadingSample>
            <p class="text-sm text-(--c-text-quiet)">
              Drag fields into tabs. Required fields show an asterisk.
            </p>
          </div>

          <div class="headings-designer">
            <div class="cp-workspace flex flex-wrap items-start gap-md">
              <craft-card v-for="tab in tabs" :key="tab.name" class="w-56">
                <HeadingSample slot="label" :level="3">{{
                  tab.name
                }}</HeadingSample>
                <div class="grid gap-sm">
                  <craft-card v-for="field in tab.fields" :key="field.handle">
                    <div class="flex items-baseline justify-between gap-sm">
                      <HeadingSample :level="4">{{
                        field.label
                      }}</HeadingSample>
                      <span
                        v-if="field.required"
                        class="font-bold text-(--c-color-danger-text,red)"
                        aria-label="Required"
                        >*</span
                      >
                    </div>
                    <code class="text-sm text-(--c-text-quiet)">{{
                      field.handle
                    }}</code>
                  </craft-card>
                </div>
              </craft-card>
            </div>

            <aside class="grid content-start gap-md" aria-label="Library">
              <section
                v-for="section in library"
                :key="section.heading"
                class="grid gap-sm"
              >
                <HeadingSample :level="5">{{ section.heading }}</HeadingSample>
                <div
                  v-for="group in section.groups"
                  :key="group.name"
                  class="grid gap-xs"
                >
                  <HeadingSample
                    :level="6"
                    class="uppercase tracking-wide text-(--c-text-quiet)"
                    >{{ group.name }}</HeadingSample
                  >
                  <div class="flex flex-wrap gap-xs">
                    <craft-chip
                      v-for="item in group.items"
                      :key="item"
                      size="small"
                      >{{ item }}</craft-chip
                    >
                  </div>
                </div>
              </section>
            </aside>
          </div>
        </div>
      </craft-pane>
    </div>
  </CpContainer>
</template>

<style scoped>
  .headings-designer {
    display: grid;
    grid-template-columns: minmax(0, 1fr) calc(220rem / 16);
    gap: var(--c-spacing-lg);

    @media (width < 48rem) {
      grid-template-columns: minmax(0, 1fr);
    }
  }
</style>
