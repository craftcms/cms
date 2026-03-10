import type {Meta, StoryObj} from '@storybook/vue3';
import {ref} from 'vue';
import Slideout from './Slideout.vue';

const meta: Meta<typeof Slideout> = {
  title: 'Components/Slideout',
  component: Slideout,
  argTypes: {
    position: {
      control: 'select',
      options: ['start', 'end'],
    },
    showHeader: {control: 'boolean'},
    showFooter: {control: 'boolean'},
    closeOnEscape: {control: 'boolean'},
    closeOnBackdropClick: {control: 'boolean'},
    resizable: {control: 'boolean'},
    minWidth: {control: 'number'},
    maxWidth: {control: 'number'},
  },
  args: {
    position: 'end',
    showHeader: true,
    showFooter: true,
    closeOnEscape: true,
    closeOnBackdropClick: true,
    resizable: false,
    minWidth: 320,
    maxWidth: 1200,
    title: 'Example Slideout',
  },
};

export default meta;
type Story = StoryObj<typeof Slideout>;

export const Default: Story = {
  render: (args) => ({
    components: {Slideout},
    setup() {
      const isOpen = ref(false);
      return {args, isOpen};
    },
    template: `
      <div>
        <craft-button type="button" @click="isOpen = true">Open Slideout</craft-button>

        <Slideout v-model="isOpen" v-bind="args">
          <template #header>
            <h3 style="margin: 0;">Slideout Header</h3>
          </template>

          <p>This is the main content of the slideout.</p>
          <p>It can contain any content you need.</p>

          <template #footer>
            <div style="display: flex; gap: 0.5rem;">
              <craft-button @click="isOpen = false" type="button">Cancel</craft-button>
              <craft-button type="button">Save</craft-button>
            </div>
          </template>
        </Slideout>
      </div>
    `,
  }),
};

export const NestedSlideouts: Story = {
  render: () => ({
    components: {Slideout},
    setup() {
      const outerOpen = ref(false);
      const innerOpen = ref(false);
      return {outerOpen, innerOpen};
    },
    template: `
      <div>
        <craft-button type="button" @click="outerOpen = true">Open Slideout</craft-button>

        <Slideout v-model="outerOpen" title="Outer Slideout">
          <p>This is the outer slideout.</p>
          <p>Click the button below to open a nested slideout.</p>

          <craft-button type="button" @click="innerOpen = true">Open Nested Slideout</craft-button>

          <Slideout v-model="innerOpen" title="Inner Slideout">
            <p>This is the inner/nested slideout.</p>
            <p>It should appear on top of the outer slideout and be independently closable.</p>

            <template #secondary-action>
              <craft-button type="button" @click="innerOpen = false">Close Inner</craft-button>
            </template>
          </Slideout>

          <template #secondary-action>
            <craft-button type="button" @click="outerOpen = false">Close</craft-button>
          </template>
        </Slideout>
      </div>
    `,
  }),
};

export const PositionStart: Story = {
  render: () => ({
    components: {Slideout},
    setup() {
      const isOpen = ref(false);
      return {isOpen};
    },
    template: `
      <div>
        <craft-button type="button" @click="isOpen = true">Open Slideout (Start)</craft-button>

        <Slideout v-model="isOpen" position="start" label="Start Position Slideout">
          <template #header>
            <h3 style="margin: 0;">Start Position</h3>
          </template>

          <p>This slideout opens from the start (left in LTR) side.</p>
        </Slideout>
      </div>
    `,
  }),
};

export const Overflow: Story = {
  render: () => ({
    components: {Slideout},
    setup() {
      const isOpen = ref(false);
      return {isOpen};
    },
    template: `
      <div>
        <craft-button type="button" @click="isOpen = true">Open Slideout</craft-button>

        <Slideout v-model="isOpen" label="Start Position Slideout" title="Start Position">
          <div style="display: grid; gap: 1rem;">

            <h1>HTML Ipsum Presents</h1>

            <p><strong>Pellentesque habitant morbi tristique</strong> senectus et netus et malesuada fames ac turpis
              egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit
              amet quam egestas semper. <em>Aenean ultricies mi vitae est.</em> Mauris placerat eleifend leo. Quisque
              sit amet est et sapien ullamcorper pharetra. Vestibulum erat wisi, condimentum sed, <code>commodo
                vitae</code>, ornare sit amet, wisi. Aenean fermentum, elit eget tincidunt condimentum, eros ipsum
              rutrum orci, sagittis tempus lacus enim ac dui. <a href="#">Donec non enim</a> in turpis pulvinar
              facilisis. Ut felis.</p>

            <h2>Header Level 2</h2>

            <ol>
              <li>Lorem ipsum dolor sit amet, consectetuer adipiscing elit.</li>
              <li>Aliquam tincidunt mauris eu risus.</li>
            </ol>

            <blockquote><p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus magna. Cras in mi at felis
              aliquet congue. Ut a est eget ligula molestie gravida. Curabitur massa. Donec eleifend, libero at sagittis
              mollis, tellus est malesuada tellus, at luctus turpis elit sit amet quam. Vivamus pretium ornare est.</p>
            </blockquote>

            <h3>Header Level 3</h3>

            <ul>
              <li>Lorem ipsum dolor sit amet, consectetuer adipiscing elit.</li>
              <li>Aliquam tincidunt mauris eu risus.</li>
            </ul>

            <pre><code>
\t\t\t\t#header h1 a {
\t\t\t\t  display: block;
\t\t\t\t  width: 300px;
\t\t\t\t  height: 80px;
\t\t\t\t}
\t\t\t\t</code></pre>

            <p>Nostrud sunt consectetur non esse non dolore laboris enim nisi. Mollit qui occaecat irure elit minim
              incididunt commodo incididunt nulla et. Anim incididunt minim eu et reprehenderit non duis tempor dolor
              tempor dolor officia amet irure. Laboris aliquip ullamco esse ut do nulla ullamco labore. Deserunt ex
              occaecat magna cupidatat nulla amet. Amet nisi adipisicing ex occaecat qui ad velit minim elit sint. Anim
              culpa et ullamco eu pariatur fugiat ex incididunt ad laboris qui reprehenderit pariatur non. Laborum id ex
              eiusmod qui nulla proident consectetur fugiat cillum.</p>

            <p>Consequat do nulla voluptate Lorem. Labore et laborum non duis. Cillum minim ullamco sit duis minim
              proident occaecat. Consectetur est ea irure commodo fugiat.</p>

            <p>Eu esse sunt anim magna ex aliqua aliqua officia ullamco proident laborum eiusmod laboris aute laborum.
              Velit culpa consequat nulla Lorem officia aliquip labore officia aliqua dolore amet cupidatat. Sint
              aliquip Lorem ipsum est irure sunt ad cupidatat pariatur culpa aliquip elit veniam nulla amet. Eu deserunt
              sunt ipsum nisi magna elit ex exercitation eiusmod deserunt veniam. Qui elit fugiat aliquip ullamco.</p>

            <p>Ea occaecat exercitation est id in officia irure qui sint esse aliqua dolore qui ad. Consectetur veniam
              incididunt elit minim ipsum. Dolor sit culpa exercitation nostrud occaecat enim nulla ea esse. Aute
              consequat consectetur sint sint est est anim cillum reprehenderit ut pariatur. Labore dolor voluptate
              culpa Lorem laborum in do aute id laborum duis commodo. Minim do ad culpa sit Lorem Lorem occaecat elit ex
              magna irure nostrud nostrud. Nisi eiusmod sit nisi esse consectetur fugiat culpa labore reprehenderit
              voluptate proident. Velit aliqua consectetur ex reprehenderit sit amet sint anim magna consectetur
              officia.</p>

            <p>Consectetur sint amet Lorem occaecat laborum consequat. Est do cupidatat amet cillum veniam officia. Sit
              elit reprehenderit magna ipsum ut nostrud ut ipsum aute. Pariatur in sunt occaecat deserunt commodo dolor
              magna commodo reprehenderit ut ullamco. Nulla excepteur ad pariatur anim commodo nisi do laborum non et
              ipsum adipisicing. Id sit exercitation occaecat laborum ipsum est adipisicing tempor ullamco. Veniam
              proident do labore id.</p>

            <p>Nisi et exercitation laborum exercitation voluptate voluptate tempor exercitation minim minim. Labore
              adipisicing voluptate est voluptate officia velit minim mollit occaecat pariatur fugiat velit commodo
              consectetur ex. Deserunt nisi cillum dolor Lorem id irure consequat eiusmod dolore velit adipisicing.
              Adipisicing ullamco et excepteur enim velit aute velit cillum enim aliqua anim. Elit occaecat aute ex.
              Ipsum cupidatat cillum exercitation commodo excepteur veniam culpa Lorem laboris irure proident id et.
              Tempor fugiat labore nulla ad velit dolor est deserunt non excepteur. Incididunt est duis aliquip velit
              commodo deserunt dolore aliquip cupidatat amet est deserunt.</p>

            <p>Do velit mollit pariatur ad reprehenderit in pariatur est velit. Laboris commodo ipsum cupidatat
              adipisicing est esse fugiat incididunt eu veniam ex aliquip sint Lorem. Qui amet reprehenderit duis ea
              culpa. Nostrud culpa exercitation dolor exercitation. Non nisi commodo eiusmod ex non nisi et laboris. Ad
              tempor veniam deserunt non amet nostrud esse laborum quis. Ullamco consectetur commodo occaecat
              consectetur nostrud exercitation irure occaecat pariatur minim nulla.</p>

            <p>Mollit do magna officia adipisicing incididunt irure. Deserunt adipisicing et nisi consequat
              reprehenderit veniam consectetur commodo exercitation ad fugiat. Ea non ipsum sint qui ex velit culpa
              veniam cupidatat cillum labore. Tempor ea non id pariatur fugiat laborum mollit tempor laborum dolore
              incididunt dolore reprehenderit. Excepteur labore Lorem eu ad id incididunt in duis voluptate officia ex
              excepteur irure voluptate. Anim deserunt mollit exercitation minim laborum nostrud aliqua laboris. Lorem
              amet consequat enim tempor in reprehenderit magna deserunt eu eiusmod esse. Aliqua qui ex id.</p>
          </div>

          <template #primary-action>
            <craft-button type="button">Save</craft-button>
          </template>
        </Slideout>
      </div>
    `,
  }),
};

export const Minimal: Story = {
  render: () => ({
    components: {Slideout},
    setup() {
      const isOpen = ref(false);
      return {isOpen};
    },
    template: `
      <div>
        <craft-button type="button" @click="isOpen = true">Open Minimal Slideout</craft-button>

        <Slideout
          v-model="isOpen"
          :showHeader="false"
          :showFooter="false"
          label="Minimal Slideout"
        >
          <p>This slideout has no header or footer.</p>
          <p>Just the body content.</p>
          <craft-button type="button" @click="isOpen = false">Close</craft-button>
        </Slideout>
      </div>
    `,
  }),
};

export const Resizable: Story = {
  render: () => ({
    components: {Slideout},
    setup() {
      const isOpen = ref(false);
      return {isOpen};
    },
    template: `
      <div>
        <craft-button type="button" @click="isOpen = true">Open Resizable Slideout</craft-button>

        <Slideout
          v-model="isOpen"
          title="Resizable Slideout"
          resizable
          :minWidth="320"
          :maxWidth="1200"
        >
          <p>This slideout can be resized by dragging the left edge.</p>
          <p>The resize handle appears as a blue bar when you hover over it.</p>
          <p>Try resizing this slideout, then close it and reopen to see that the width is preserved.</p>

          <template #secondary-action>
            <craft-button type="button" @click="isOpen = false">Close</craft-button>
          </template>
        </Slideout>
      </div>
    `,
  }),
};

export const ResizableNested: Story = {
  render: () => ({
    components: {Slideout},
    setup() {
      const outerOpen = ref(false);
      const innerOpen = ref(false);
      return {outerOpen, innerOpen};
    },
    template: `
      <div>
        <craft-button type="button" @click="outerOpen = true">Open Resizable Slideout</craft-button>

        <Slideout v-model="outerOpen" title="Outer Slideout" resizable>
          <p>This slideout is resizable. Drag the left edge to resize.</p>
          <p>When you resize this slideout, all subsequent slideouts will open at the same width.</p>

          <craft-button type="button" @click="innerOpen = true">Open Nested Slideout</craft-button>

          <Slideout v-model="innerOpen" title="Inner Slideout" resizable>
            <p>This nested slideout inherits the width from the parent.</p>
            <p>You can also resize this one, and the width will be shared with any new slideouts.</p>

            <template #secondary-action>
              <craft-button type="button" @click="innerOpen = false">Close Inner</craft-button>
            </template>
          </Slideout>

          <template #secondary-action>
            <craft-button type="button" @click="outerOpen = false">Close</craft-button>
          </template>
        </Slideout>
      </div>
    `,
  }),
};
