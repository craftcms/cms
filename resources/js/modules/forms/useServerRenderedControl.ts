import {nextTick, onMounted, ref, watch, type WatchSource} from 'vue';
import {useEventListener} from '@vueuse/core';

type ServerRenderedControlOptions<Value> = {
  value: () => Value;
  dependencies: WatchSource[];
  render: () => Promise<string>;
  readValue: (
    host: HTMLElement
  ) => Value | undefined | Promise<Value | undefined>;
  update: (value: Value) => void;
};

export function useServerRenderedControl<Value>({
  value,
  dependencies,
  render,
  readValue,
  update,
}: ServerRenderedControlOptions<Value>) {
  const host = ref<HTMLElement>();
  const html = ref('');
  let serialized = JSON.stringify(value());
  let reading = false;

  useEventListener(host, ['input', 'change', 'click', 'drop'], () => {
    requestAnimationFrame(() => void read());
  });

  onMounted(refresh);
  watch(dependencies, refresh, {deep: true});
  watch(
    value,
    (current) => {
      if (JSON.stringify(current) !== serialized) {
        void refresh();
      }
    },
    {deep: true}
  );

  async function refresh(): Promise<void> {
    serialized = JSON.stringify(value());
    html.value = await render();
    await nextTick();
  }

  async function read(): Promise<void> {
    if (!host.value || reading) {
      return;
    }

    reading = true;
    try {
      const current = await readValue(host.value);
      if (current === undefined) {
        return;
      }

      const nextSerialized = JSON.stringify(current);
      if (nextSerialized === serialized) {
        return;
      }

      serialized = nextSerialized;
      update(current);
    } finally {
      reading = false;
    }
  }

  return {host, html};
}
