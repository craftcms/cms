import type {Meta, StoryObj} from '@storybook/vue3-vite';
import {ref} from 'vue';
import HtmlFragmentRenderer from './HtmlFragmentRenderer.vue';

const meta = {
  title: 'CP/HtmlFragmentRenderer',
  component: HtmlFragmentRenderer,
  args: {
    as: 'div',
    fragment: {
      html:
        '<p>Your site has <strong>3 pending updates</strong>.</p>' +
        '<p><a href="#">Review updates</a></p>',
      headHtml: '',
      bodyHtml: '',
    },
  },
  argTypes: {
    render: {control: false},
  },
  parameters: {
    docs: {
      description: {
        component:
          'Puts server-rendered HTML on the page, along with the styles and ' +
          'scripts it needs. Pass a fragment with three parts: `html` goes in ' +
          'the component, `headHtml` goes in the document head, and ' +
          '`bodyHtml` goes at the end of the body. Stylesheets and scripts ' +
          'already on the page are not loaded twice. Everything it added is ' +
          'removed again when the fragment changes or the component unmounts. ' +
          'It emits `ready` with its element once all of it is in place.\n\n' +
          'Use it for markup a plugin renders with Twig, such as a widget body ' +
          'or a settings form.\n\n' +
          "```ts\nimport {HtmlFragmentRenderer} from '@craftcms/cp';\n```",
      },
    },
  },
} satisfies Meta<typeof HtmlFragmentRenderer>;

export default meta;

type Story = StoryObj<typeof meta>;

export const Default: Story = {};

/** `headHtml` can carry the styles the markup needs. */
export const WithStyles: Story = {
  args: {
    fragment: {
      headHtml:
        '<style>.weather-widget{display:flex;gap:1rem;align-items:center}' +
        '.weather-widget__temp{font-size:2rem;font-weight:700}</style>',
      html:
        '<div class="weather-widget">' +
        '<span class="weather-widget__temp">18°C</span>' +
        '<span>Partly cloudy in Portland. Rain expected after 4pm.</span>' +
        '</div>',
      bodyHtml: '',
    },
  },
};

/** `as` sets the container's tag, here a list. */
export const CustomContainer: Story = {
  args: {
    as: 'ul',
    fragment: {
      html:
        '<li>Backup completed at 02:00</li>' +
        '<li>Search index rebuilt</li>' +
        '<li>Cleared 412 stale caches</li>',
      headHtml: '',
      bodyHtml: '',
    },
  },
};

/** A new fragment replaces the old one, and `ready` fires again. */
export const SwappingFragments: Story = {
  render: () => ({
    components: {HtmlFragmentRenderer},
    setup() {
      const fragments = [
        {
          html: '<p>Showing entries from <strong>this week</strong>.</p>',
          headHtml: '',
          bodyHtml: '',
        },
        {
          html: '<p>Showing entries from <strong>this month</strong>.</p>',
          headHtml: '',
          bodyHtml: '',
        },
      ];
      const index = ref(0);
      const readyCount = ref(0);
      return {fragments, index, readyCount};
    },
    template: `
      <craft-button type="button" class="mb-3" @click="index = 1 - index">
        Switch range
      </craft-button>
      <HtmlFragmentRenderer :fragment="fragments[index]" @ready="readyCount++" />
      <p class="text-sm">ready fired {{ readyCount }} times</p>
    `,
  }),
};
