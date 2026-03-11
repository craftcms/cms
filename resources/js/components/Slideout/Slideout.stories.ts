import type {Meta, StoryObj} from '@storybook/vue3';
import {computed, defineComponent, h, ref, resolveComponent} from 'vue';
import {expect, userEvent, waitFor, within} from 'storybook/test';
import Slideout from './Slideout.vue';

const meta: Meta<typeof Slideout> = {
  title: 'Components/Slideout',
  component: Slideout,
  argTypes: {
    position: {
      control: 'select',
      options: ['start', 'end'],
    },
    closeOnEscape: {control: 'boolean'},
    closeOnBackdropClick: {control: 'boolean'},
    resizable: {control: 'boolean'},
    minWidth: {control: 'number'},
    maxWidth: {control: 'number'},
  },
  args: {
    position: 'end',
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

        <Slideout :active="isOpen" v-bind="args" title="Header">
          <p>This is the main content of the slideout.</p>
          <p>It can contain any content you need.</p>

          <template #primary-action>
            <craft-button type="button">Save</craft-button>
          </template>
          <template #secondary-action>
            <craft-button @click="isOpen = false" type="button">Cancel</craft-button>
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
        <craft-button data-testid="open-outer" type="button" @click="outerOpen = true">Open Slideout</craft-button>

        <Slideout :active="outerOpen" title="Outer Slideout">
          <div class="stack">
            <div>
              <p>This is the outer slideout.</p>
              <p>Click the button below to open a nested slideout.</p>
            </div>

            <craft-button data-testid="open-inner" type="button" @click="innerOpen = true">Open Nested Slideout</craft-button>
          </div>

          <Slideout :active="innerOpen" title="Inner Slideout">
            <div class="stack">
              <div>
                <p>This is the inner/nested slideout.</p>
                <p>It should appear on top of the outer slideout and be independently closable.</p>
              </div>
            </div>

            <template #secondary-action>
              <craft-button data-testid="close-inner" type="button" @click="innerOpen = false">Close Inner</craft-button>
            </template>
          </Slideout>

          <template #secondary-action>
            <craft-button data-testid="close-outer" type="button" @click="outerOpen = false">Close</craft-button>
          </template>
        </Slideout>
      </div>
    `,
  }),
  play: async ({canvasElement}) => {
    const canvas = within(canvasElement);
    const user = userEvent.setup();

    const openOuterBtn = canvas.getByTestId('open-outer');

    // --- Open the outer slideout ---
    await user.click(openOuterBtn);
    await waitFor(() => {
      expect(
        document.querySelectorAll('.craft-slideout-panel--open').length
      ).toBe(1);
    });

    // --- Open the inner slideout ---
    const openInnerBtn = document.querySelector<HTMLElement>(
      '[data-testid="open-inner"]'
    )!;
    await user.click(openInnerBtn);
    await waitFor(() => {
      expect(
        document.querySelectorAll('.craft-slideout-panel--open').length
      ).toBe(2);
    });

    // --- Close inner via its Close button — outer should remain open ---
    const closeInnerBtn = document.querySelector<HTMLElement>(
      '[data-testid="close-inner"]'
    )!;
    await user.click(closeInnerBtn);
    await waitFor(() => {
      expect(
        document.querySelectorAll('.craft-slideout-panel--open').length
      ).toBe(1);
    });

    // --- Re-open inner, then close it with Escape — outer stays open ---
    await user.click(openInnerBtn);
    await waitFor(() => {
      expect(
        document.querySelectorAll('.craft-slideout-panel--open').length
      ).toBe(2);
    });

    await user.keyboard('{Escape}');
    await waitFor(() => {
      expect(
        document.querySelectorAll('.craft-slideout-panel--open').length
      ).toBe(1);
    });

    // --- Close outer with Escape ---
    await user.keyboard('{Escape}');
    await waitFor(() => {
      expect(
        document.querySelectorAll('.craft-slideout-panel--open').length
      ).toBe(0);
    });

    // --- Re-open both, then close outer directly — both should close ---
    await user.click(openOuterBtn);
    await waitFor(() => {
      expect(
        document.querySelectorAll('.craft-slideout-panel--open').length
      ).toBe(1);
    });

    await user.click(
      document.querySelector<HTMLElement>('[data-testid="open-inner"]')!
    );
    await waitFor(() => {
      expect(
        document.querySelectorAll('.craft-slideout-panel--open').length
      ).toBe(2);
    });

    // Close outer via its button while inner is still open
    const closeOuterBtn = document.querySelector<HTMLElement>(
      '[data-testid="close-outer"]'
    )!;
    await user.click(closeOuterBtn);
    await waitFor(() => {
      expect(
        document.querySelectorAll('.craft-slideout-panel--open').length
      ).toBe(0);
    });
  },
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

        <Slideout :active="isOpen" position="start" title="Start Position Slideout">
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

        <Slideout :active="isOpen" title="Overflow">
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

const NestedSlideout = defineComponent({
  name: 'NestedSlideout',
  props: {
    id: {type: Number, required: true},
    remaining: {type: Number, required: true},
    slideouts: {type: Array as () => number[], required: true},
  },
  emits: ['open', 'close'],
  setup(props, {emit}) {
    return () => {
      const Self = resolveComponent('NestedSlideout');
      const nextId = props.id + 1;
      const hasNext = props.remaining > 0;

      return h(
        Slideout,
        {
          active: props.slideouts?.includes(props.id),
          title: 'Slideout',
          resizable: true,
        },
        {
          default: () => [
            h('p', 'This slideout is resizable. Drag the left edge to resize.'),
            h(
              'p',
              'When you resize this slideout, all subsequent slideouts will open at the same width.'
            ),
            hasNext &&
              h(
                'craft-button',
                {type: 'button', onClick: () => emit('open', nextId)},
                'Open Nested Slideout'
              ),
            hasNext &&
              h(Self, {
                id: nextId,
                remaining: props.remaining - 1,
                slideouts: props.slideouts,
                onOpen: (id: number) => emit('open', id),
                onClose: (id: number) => emit('close', id),
              }),
          ],
          'secondary-action': () =>
            h(
              'craft-button',
              {type: 'button', onClick: () => emit('close', props.id)},
              'Close'
            ),
        }
      );
    };
  },
});

export const Resizable: Story = {
  render: () => ({
    components: {NestedSlideout},
    setup() {
      const slideouts = ref<number[]>([]);
      const open = (id: number) => slideouts.value.push(id);
      const close = (id: number) =>
        (slideouts.value = slideouts.value.filter((s) => s !== id));
      return {slideouts, open, close};
    },
    template: `
      <craft-button type="button" @click="open(1)">Open Slideout</craft-button>
      <NestedSlideout
        :id="1"
        :remaining="5"
        :slideouts="slideouts"
        @open="open"
        @close="close"
      />
    `,
  }),
};

export const FocusTrap: Story = {
  render: () => ({
    components: {Slideout},
    setup() {
      const isOpen = ref(false);
      return {isOpen};
    },
    template: `
      <div>
        <button data-testid="outside-before" type="button">Outside Before</button>
        <button data-testid="trigger" type="button" @click="isOpen = true">Open Slideout</button>
        <button data-testid="outside-after" type="button">Outside After</button>

        <Slideout :active="isOpen" title="Focus Trap Test">
          <div style="display: flex; flex-direction: column; gap: 0.5rem;">
            <label>
              First Name
              <input data-testid="input-first" type="text" />
            </label>
            <label>
              Last Name
              <input data-testid="input-last" type="text" />
            </label>
            <label>
              Notes
              <textarea data-testid="textarea-notes"></textarea>
            </label>
          </div>

          <template #secondary-action>
            <button data-testid="cancel-btn" type="button" @click="isOpen = false">Cancel</button>
          </template>
          <template #primary-action>
            <button data-testid="save-btn" type="button">Save</button>
          </template>
        </Slideout>
      </div>
    `,
  }),
  play: async ({canvasElement}) => {
    const canvas = within(canvasElement);
    const user = userEvent.setup();

    // Open the slideout
    const trigger = canvas.getByTestId('trigger');
    await user.click(trigger);

    // Wait for the slideout to open and focus to be set
    const dialog = await waitFor(() => {
      const el = document.querySelector<HTMLElement>(
        '.craft-slideout-panel--open'
      );
      expect(el).not.toBeNull();
      return el!;
    });

    const panel = within(dialog);

    // Initial focus should be on the first focusable element inside the panel
    await waitFor(() => {
      const firstInput = panel.getByTestId('input-first');
      expect(document.activeElement).toBe(firstInput);
    });

    // Tab forward through all focusable elements
    await user.tab();
    expect(document.activeElement).toBe(panel.getByTestId('input-last'));

    await user.tab();
    expect(document.activeElement).toBe(panel.getByTestId('textarea-notes'));

    await user.tab();
    expect(document.activeElement).toBe(panel.getByTestId('cancel-btn'));

    await user.tab();
    expect(document.activeElement).toBe(panel.getByTestId('save-btn'));

    // Tab from the last focusable element should wrap to the first
    await user.tab();
    expect(document.activeElement).toBe(panel.getByTestId('input-first'));

    // Shift+Tab from the first should wrap to the last
    await user.tab({shift: true});
    expect(document.activeElement).toBe(panel.getByTestId('save-btn'));

    // Focus should never escape to elements outside the slideout
    const outsideBefore = canvas.getByTestId('outside-before');
    const outsideAfter = canvas.getByTestId('outside-after');
    expect(dialog.contains(document.activeElement)).toBe(true);
    expect(document.activeElement).not.toBe(outsideBefore);
    expect(document.activeElement).not.toBe(outsideAfter);
  },
};

export const FocusTrapRestore: Story = {
  render: () => ({
    components: {Slideout},
    setup() {
      const isOpen = ref(false);
      return {isOpen};
    },
    template: `
      <div>
        <button data-testid="trigger" type="button" @click="isOpen = true">Open Slideout</button>

        <Slideout :active="isOpen" title="Focus Restore Test" :closeOnEscape="true">
          <p>Press Escape to close and verify focus returns to the trigger.</p>

          <template #primary-action>
            <button data-testid="save-btn" type="button">Save</button>
          </template>
        </Slideout>
      </div>
    `,
  }),
  play: async ({canvasElement}) => {
    const canvas = within(canvasElement);
    const user = userEvent.setup();

    // Focus and click the trigger
    const trigger = canvas.getByTestId('trigger');
    await user.click(trigger);

    // Wait for slideout to open
    await waitFor(() => {
      const el = document.querySelector('.craft-slideout-panel--open');
      expect(el).not.toBeNull();
    });

    // Close via Escape
    await user.keyboard('{Escape}');

    // Focus should return to the trigger element
    await waitFor(() => {
      expect(document.activeElement).toBe(trigger);
    });
  },
};

export const FocusTrapNested: Story = {
  render: () => ({
    components: {Slideout},
    setup() {
      const outerOpen = ref(false);
      const innerOpen = ref(false);
      return {outerOpen, innerOpen};
    },
    template: `
      <div>
        <button data-testid="trigger" type="button" @click="outerOpen = true">Open Outer</button>

        <Slideout :active="outerOpen" title="Outer Slideout">
          <button data-testid="outer-btn" type="button">Outer Button</button>
          <button data-testid="open-inner" type="button" @click="innerOpen = true">Open Inner</button>

          <Slideout :active="innerOpen" title="Inner Slideout">
            <button data-testid="inner-btn-1" type="button">Inner Button 1</button>
            <button data-testid="inner-btn-2" type="button">Inner Button 2</button>

            <template #primary-action>
              <button data-testid="inner-save" type="button">Save Inner</button>
            </template>
          </Slideout>

          <template #primary-action>
            <button data-testid="outer-save" type="button">Save Outer</button>
          </template>
        </Slideout>
      </div>
    `,
  }),
  play: async ({canvasElement}) => {
    const canvas = within(canvasElement);
    const user = userEvent.setup();

    // Open outer slideout
    await user.click(canvas.getByTestId('trigger'));
    await waitFor(() => {
      expect(
        document.querySelector('.craft-slideout-panel--open')
      ).not.toBeNull();
    });

    // Open inner slideout
    const openInner = document.querySelector<HTMLElement>(
      '[data-testid="open-inner"]'
    )!;
    await user.click(openInner);

    // Wait for inner slideout to open
    await waitFor(() => {
      const panels = document.querySelectorAll('.craft-slideout-panel--open');
      expect(panels.length).toBe(2);
    });

    const innerPanels = document.querySelectorAll(
      '.craft-slideout-panel--open'
    );
    const innerPanel = innerPanels[innerPanels.length - 1] as HTMLElement;
    const inner = within(innerPanel);

    // Focus should be trapped in the inner (topmost) slideout
    // Tab through inner slideout elements
    const innerBtn1 = inner.getByTestId('inner-btn-1');
    innerBtn1.focus();

    await user.tab();
    expect(document.activeElement).toBe(inner.getByTestId('inner-btn-2'));

    await user.tab();
    expect(document.activeElement).toBe(inner.getByTestId('inner-save'));

    // Tab from last inner element should wrap within the inner slideout
    await user.tab();
    expect(document.activeElement).toBe(inner.getByTestId('inner-btn-1'));

    // Outer slideout buttons should not receive focus
    expect(document.activeElement).not.toBe(
      document.querySelector('[data-testid="outer-btn"]')
    );
    expect(document.activeElement).not.toBe(
      document.querySelector('[data-testid="outer-save"]')
    );
  },
};
