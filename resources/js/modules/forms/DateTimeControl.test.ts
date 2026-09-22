import {createApp, h, nextTick} from 'vue';
import {afterEach, describe, expect, it} from 'vite-plus/test';
import DateTimeControl from './DateTimeControl.vue';
import type {FormControlPayload} from './types';
import {controlValueAt} from './runtime';

describe('controlValueAt', () => {
  const control = {path: ['fields', 'date']};

  it('reads the value at the control’s path', () => {
    const values = {fields: {date: {date: '2026-08-07'}}};

    expect(controlValueAt(values, control)).toEqual({date: '2026-08-07'});
  });

  /**
   * The payload describing a control can arrive an emit ahead of the values
   * filling it — a Matrix block the server has just minted. Without a stand-in,
   * a control whose value is a shape reaches into nothing and throws, and a
   * throw during render takes the whole field down.
   */
  it('stands in the control’s empty value when the path is missing', () => {
    expect(controlValueAt({}, {...control, emptyValue: {}})).toEqual({});
    expect(
      controlValueAt({}, {...control, emptyValue: {entries: {}, sortOrder: []}})
    ).toEqual({entries: {}, sortOrder: []});
  });

  it('leaves a stored null alone', () => {
    // Null is a value a control chose — a content block with no content — not
    // an absent one.
    expect(
      controlValueAt({fields: {date: null}}, {...control, emptyValue: {}})
    ).toBeNull();
  });

  it('reads undefined for a control that declares no empty value', () => {
    // A scalar control coerces undefined on its own, so it ships nothing.
    expect(controlValueAt({}, control)).toBeUndefined();
  });
});

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

  it('renders empty when it is handed its empty value', async () => {
    // `controlValueAt()` stands that in at the render boundary, so the control
    // never sees undefined.
    mount({});
    await nextTick();

    const input = container!.querySelector('craft-input-date-time') as {
      dateValue?: string;
      timeValue?: string;
    } | null;

    expect(input).not.toBeNull();
    expect(input!.dateValue).toBe('');
    expect(input!.timeValue).toBe('');
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
