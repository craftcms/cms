import {computed, ref} from 'vue';

export interface UiLayer {
  id: string;
  type: 'slideout' | 'modal' | 'dialog';
  position?: 'start' | 'end';
  panel: HTMLElement | null;
  backdrop: HTMLElement | null;
}

// Global stack of open UI layers
const layers = ref<UiLayer[]>([]);

export function useUiLayerManager() {
  const add = (layer: UiLayer) => {
    if (!layers.value.find((l) => l.id === layer.id)) {
      layers.value.push(layer);
      updatePositions();
    }
  };

  const remove = (id: string) => {
    const index = layers.value.findIndex((l) => l.id === id);
    if (index !== -1) {
      layers.value.splice(index, 1);
      updatePositions();
    }
  };

  const isTopmost = (id: string) => {
    const stack = layers.value;
    return stack.length > 0 && stack[stack.length - 1].id === id;
  };

  const updatePositions = () => {
    // Filter to only slideouts for stacking behavior
    const slideouts = layers.value.filter((l) => l.type === 'slideout');
    const total = slideouts.length;

    slideouts.forEach((layer, index) => {
      if (!layer.panel) return;

      // Calculate offset: newest panel (last in array) is at edge, older panels shift inward
      const offset = total > 1 ? (total - 1 - index) * 3 : 0;

      if (layer.position === 'end') {
        layer.panel.style.insetInlineEnd = `${offset}rem`;
      } else {
        layer.panel.style.insetInlineStart = `${offset}rem`;
      }

      // Update z-index so newer panels are on top
      layer.panel.style.zIndex = `${101 + index}`;
      if (layer.backdrop) {
        layer.backdrop.style.zIndex = `${100 + index}`;
      }
    });
  };

  const count = computed(() => layers.value.length);

  const hasOpenLayers = computed(() => layers.value.length > 0);

  return {
    add,
    remove,
    isTopmost,
    count,
    hasOpenLayers,
  };
}
