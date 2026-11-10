import type {Meta, StoryObj} from '@storybook/vue3-vite';
import SlideoutButton from './SlideoutButton.vue';

const meta = {
  title: 'CP/SlideoutButton',
  component: SlideoutButton,
  args: {
    url: '/admin/settings/sections/new',
  },
  parameters: {
    docs: {
      description: {
        component:
          'A dashed button that opens another control panel screen in a ' +
          'slideout, so the user can create or edit something without leaving ' +
          'the page. When that screen saves, the button emits `success` with ' +
          'the result. Listen for it and refresh whatever the save changed. ' +
          'Focus returns to the button when the slideout closes.\n\n' +
          'The slideout loads the screen at `url` from the server, so ' +
          'clicking the button in Storybook does nothing.\n\n' +
          "```ts\nimport {SlideoutButton} from '@craftcms/cp';\n```",
      },
    },
  },
} satisfies Meta<typeof SlideoutButton>;

export default meta;

type Story = StoryObj<typeof meta>;

export const Default: Story = {
  render: (args) => ({
    components: {SlideoutButton},
    setup() {
      return {args};
    },
    template: '<SlideoutButton v-bind="args">New section</SlideoutButton>',
  }),
};

/** Beside a list the saved item belongs in, which it refreshes on `success`. */
export const BelowAList: Story = {
  args: {url: '/admin/settings/fields/new'},
  render: (args) => ({
    components: {SlideoutButton},
    setup() {
      function refresh(result: unknown) {
        console.log('Field saved', result);
      }
      return {args, refresh};
    },
    template: `
      <ul class="mb-3">
        <li>Summary</li>
        <li>Featured image</li>
        <li>Body</li>
      </ul>
      <SlideoutButton v-bind="args" @success="refresh">
        <craft-icon name="plus" slot="prefix"></craft-icon>
        New field
      </SlideoutButton>
    `,
  }),
};
