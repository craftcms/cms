import type {Meta, StoryObj} from '@storybook/vue3-vite';
import CodeHighlight from './CodeHighlight.vue';

const meta = {
  title: 'CP/CodeHighlight',
  component: CodeHighlight,
  parameters: {
    docs: {
      description: {
        component:
          'Shows code with syntax coloring. Give `code` as a string along ' +
          'with its `language`, or give `language="json"` and a `value`, ' +
          'which it formats as indented JSON for you.\n\n' +
          'It knows Apache, CSS, diff, Dockerfile, .env, GraphQL, HTML, ' +
          'HTTP, JavaScript, JSON, JSX, Markdown, nginx, PHP, shell, SQL, ' +
          'TOML, TypeScript, TSX, Twig, Vue and YAML, along with their ' +
          'common aliases, such as `xml` or `sh`. Any other language shows ' +
          'as plain text.\n\n' +
          "```ts\nimport {CodeHighlight} from '@craftcms/cp';\n```",
      },
    },
  },
} satisfies Meta<typeof CodeHighlight>;

export default meta;
type Story = StoryObj<typeof meta>;

const render = (args: Record<string, unknown>) => ({
  components: {CodeHighlight},
  setup: () => ({args}),
  template: '<CodeHighlight v-bind="args" />',
});

/** A value formatted as JSON, such as a queue job's payload. */
export const JsonValue: Story = {
  render,
  args: {
    language: 'json',
    value: {
      id: 4182,
      description: 'Resaving News entries',
      progress: 0.42,
      attempts: 1,
      params: {elementType: 'craft\\elements\\Entry', section: 'news'},
      error: null,
    },
  },
};

/** A string of YAML, such as a project config file. */
export const Yaml: Story = {
  render,
  args: {
    language: 'yaml',
    code: [
      'sections:',
      '  news:',
      '    name: News',
      '    type: channel',
      '    enableVersioning: true',
      '    siteSettings:',
      '      default:',
      '        uriFormat: news/{slug}',
      '        template: news/_entry',
    ].join('\n'),
  },
};

/** A Twig template snippet. */
export const Twig: Story = {
  render,
  args: {
    language: 'twig',
    code: [
      "{% set posts = craft.entries().section('news').limit(3).all() %}",
      '<ul>',
      '  {% for post in posts %}',
      '    <li><a href="{{ post.url }}">{{ post.title }}</a></li>',
      '  {% endfor %}',
      '</ul>',
    ].join('\n'),
  },
};
