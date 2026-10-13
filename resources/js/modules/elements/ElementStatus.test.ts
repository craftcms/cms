import {createApp, h, nextTick} from 'vue';
import {afterEach, describe, expect, it} from 'vite-plus/test';
import '@craftcms/ui/components/indicator/indicator';
import ElementStatus from './ElementStatus.vue';

let teardown: (() => void) | undefined;

type ElementStatusProps = InstanceType<typeof ElementStatus>['$props'];

type Indicator = HTMLElement & {fill: string; appearance?: string};

async function indicatorFor(props: ElementStatusProps): Promise<Indicator> {
  const container = document.createElement('div');
  document.body.append(container);

  const app = createApp({render: () => h(ElementStatus, props)});
  app.mount(container);

  teardown = () => {
    app.unmount();
    container.remove();
  };

  await nextTick();

  return container.querySelector('craft-indicator') as Indicator;
}

afterEach(() => {
  teardown?.();
  teardown = undefined;
});

describe('ElementStatus', () => {
  it.each([
    ['live', 'success'],
    ['expired', 'danger'],
    ['pending', 'warning'],
    ['info', 'info'],
  ])('fills a %s dot with the %s variant', async (value, fill) => {
    expect((await indicatorFor({value})).fill).toBe(fill);
  });

  it('fills a palette color status with that color', async () => {
    expect((await indicatorFor({value: 'orange'})).fill).toBe('orange');
  });

  it('prefers an explicit color over the value', async () => {
    const indicator = await indicatorFor({
      value: 'processing',
      color: {value: 'violet'},
    });

    expect(indicator.fill).toBe('violet');
  });

  it('draws a disabled status as an empty ring', async () => {
    const indicator = await indicatorFor({value: 'disabled'});

    expect(indicator.appearance).toBe('outline');
    expect(indicator.fill).toBe('var(--c-color-fill-loud)');
  });

  it('leaves an unrecognized status on the default fill', async () => {
    expect((await indicatorFor({value: 'archived'})).fill).toBe(
      'var(--c-color-fill-loud)'
    );
  });

  it('fills the “all” option with a gradient', async () => {
    expect((await indicatorFor({value: '', label: 'All'})).fill).toContain(
      'linear-gradient'
    );
  });
});
