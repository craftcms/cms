import {createApp, h} from 'vue';
import {afterEach, describe, expect, it} from 'vite-plus/test';
import AskAiDiff from './AskAiDiff.vue';

describe('AskAiDiff', () => {
  let app: ReturnType<typeof createApp>;
  let container: HTMLElement;

  function mount(props: {
    current: string;
    proposed: string;
    quotes?: string[];
  }): void {
    container = document.createElement('div');
    document.body.append(container);
    app = createApp(() => h(AskAiDiff, props));
    app.mount(container);
  }

  afterEach(() => {
    app.unmount();
    container.remove();
  });

  it('shows text for an empty field without marking it inserted', () => {
    mount({current: '', proposed: 'A kitten was shivering.'});

    expect(container.querySelector('ins')).toBeNull();
    expect(container.textContent).toBe('A kitten was shivering.');
  });

  it('marks deleted and inserted text', () => {
    mount({
      current: 'A kitten was tucked beneath a basket.',
      proposed: 'A kitten was shivering beneath a basket.',
    });

    expect(container.querySelector('del')!.textContent).toBe('Deleted: tucked');
    expect(container.querySelector('ins')!.textContent).toBe(
      'Inserted: shivering'
    );
  });

  it('keeps highlighted passages visible when collapsing around them', () => {
    const filler = 'Filler words pad out this paragraph nicely. '.repeat(10);
    mount({
      current: `Old start. ${filler}A highlighted passage. ${filler}`,
      proposed: `New start. ${filler}A highlighted passage. ${filler}`,
      quotes: ['A highlighted passage.'],
    });
    const text = container.querySelector('p')!.textContent!;

    expect(container.querySelector('mark')!.textContent).toBe(
      'A highlighted passage.'
    );
    expect(text.length).toBeLessThan(filler.length * 2);
  });

  it('highlights quoted passages inside deleted text', () => {
    mount({
      current: 'The kitten was tucked beneath a basket, shivering.',
      proposed: 'The kitten was shivering beneath a basket.',
      quotes: ['tucked'],
    });

    expect(container.querySelector('del mark')!.textContent).toBe('tucked');
  });
});
