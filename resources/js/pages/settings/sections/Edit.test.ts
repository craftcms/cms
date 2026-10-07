import type {UiChange, UiPayload} from '@/modules/ui/types';
import {createApp, nextTick} from 'vue';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import Edit from './Edit.vue';

const state = vi.hoisted<{
  change?: (change: UiChange, values: UiPayload['values']) => void;
  setValue: ReturnType<typeof vi.fn>;
}>(() => ({
  change: undefined,
  setValue: vi.fn(),
}));

vi.mock('@/pages/Ui.vue', async () => {
  const {defineComponent, h} = await import('vue');

  return {
    default: defineComponent({
      props: ['ui'],
      emits: ['change'],
      setup: (_props, {emit, expose}) => {
        state.change = (change, values) => emit('change', change, values);
        expose({setValue: state.setValue});

