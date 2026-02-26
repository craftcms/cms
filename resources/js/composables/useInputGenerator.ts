import {ref, watch, type WatchStopHandle} from 'vue';
import {asciiString, toEnvVar, toHandle} from '@craftcms/cp';

/**
 * Converts a string to a URI-friendly slug format.
 * Port of legacy UriFormatGenerator.generateTargetValue()
 */
export function toUriFormat(value: string): string {
  // Remove HTML tags
  let str = value.replace(/<(.*?)>/g, '');

  // Make it lowercase
  str = str.toLowerCase();

  // Convert extended ASCII characters to basic ASCII
  str = asciiString(str);

  // Must start with a letter and end with a letter/number
  str = str.replace(/^[^a-z]+/, '');
  str = str.replace(/[^a-z0-9]+$/, '');

  // Get the "words"
  const words = str.split(/[^a-z0-9]+/).filter(Boolean);

  return words.join('-');
}

type TransformName = 'handle' | 'uri' | 'envvar';

const builtInTransforms: Record<TransformName, (val: string) => string> = {
  handle: (val) => toHandle(val),
  uri: toUriFormat,
  envvar: (val) => toEnvVar(val),
};

interface UseInputGeneratorOptions {
  transform: TransformName | ((val: string) => string);
}

/**
 * Vue composable that auto-generates a target value from a source value
 * using a transform function. Replaces legacy BaseInputGenerator and its
 * subclasses (HandleGenerator, UriFormatGenerator, EnvVarGenerator, DynamicGenerator).
 *
 * Auto-generation stops when the target is manually changed (via markDirty).
 */
export function useInputGenerator(
  source: () => string,
  onUpdate: (value: string) => void,
  options: UseInputGeneratorOptions
) {
  const transform =
    typeof options.transform === 'function'
      ? options.transform
      : builtInTransforms[options.transform];

  const isDirty = ref(false);
  let watcher: WatchStopHandle | null = null;

  function generate() {
    onUpdate(transform(source()));
  }

  function start() {
    if (watcher) return;

    watcher = watch(source, () => {
      if (!isDirty.value) {
        generate();
      }
    });
  }

  function stop() {
    watcher?.();
    watcher = null;
  }

  function markDirty() {
    isDirty.value = true;
  }

  function markClean() {
    isDirty.value = false;
  }

  start();

  return {isDirty, stop, start, markDirty, markClean};
}
