import {createApp, h, nextTick} from 'vue';
import {afterEach, describe, expect, it} from 'vite-plus/test';
import DateTimeControl from './DateTimeControl.vue';
import type {FormControlPayload} from './types';

describe('DateTimeControl', () => {
  let app: ReturnType<typeof createApp> | undefined;
  let container: HTMLElement | undefined;

  afterEach(() => {
    app?.unmount();
    container?.remove();
    app = undefined;
    container = undefined;
  });

  const control = () =>
    ({
      type: 'CraftCms\\Cms\\Form\\Controls\\DateTime',
      component: 'craft:date-time',
      props: {
        showDate: true,
        showTime: true,
        showTimeZone: true,
        locale: 'en-US',
        minuteIncrement: 15,
      },
      path: ['fields', 'date'],
      mode: 'editable',
      deltaGroup: ['fields', 'date'],
      forms: [],
    }) as unknown as FormControlPayload;

  function mount(value: unknown): void {
    container = document.createElement('div');
    document.body.append(container);
    app = createApp({
      setup: () => () =>
        h(DateTimeControl, {
          control: control(),
          value,
          editable: true,
          required: false,
        } as never),
    });
    app.mount(container);
  }

  /**
   * A control inside a block the server has just minted renders a beat before
   * its values reach the tree. Every part of a date is dereferenced on the way
   * to the input, so an absent value used to throw — and a throw here takes the
   * whole form down, which is what "Failed to render Form Control" is.
   */
  it('renders empty when its value is missing from the values tree', async () => {
    expect(() => mount(undefined)).not.toThrow();
    await nextTick();

    const input = container!.querySelector('craft-input-date-time') as {
      dateValue?: string;
      timeValue?: string;
    } | null;

    expect(input).not.toBeNull();
    expect(input!.dateValue).toBe('');
    expect(input!.timeValue).toBe('');
    // Nothing to clear, so no clear button.
    expect(container!.querySelector('.clear-btn')).toBeNull();
  });

  it('renders the value it was given', async () => {
    mount({date: '2026-08-07', time: '14:30', timezone: 'Europe/Brussels'});
    await nextTick();

    const input = container!.querySelector('craft-input-date-time') as {
      dateValue?: string;
      timeValue?: string;
    };

    expect(input.dateValue).toBe('2026-08-07');
    expect(input.timeValue).toBe('14:30');
    expect(container!.querySelector('.clear-btn')).not.toBeNull();
  });
});
