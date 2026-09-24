import {getTsProgram, typeParserPlugin} from '@wc-toolkit/type-parser';

/**
 * The type parser warns once for every type it declines to expand: DOM
 * interfaces reached through a property (`Element`, `HTMLCanvasElement`), the
 * component classes themselves, and anything past its depth or property
 * limits. Expanding those was never the point — only the union aliases behind
 * `size`, `variant`, and `appearance` reach the Storybook controls — so the
 * bail-out notices are dropped here.
 *
 * The filter is deliberately narrow. Real parser warnings, such as a bad
 * `tsconfig.json`, use a different prefix and still print. The parser calls
 * `console.warn(colorFormat, message)`, so the message is the second argument.
 */
const SKIPPED_TYPE_NOTICE = '[type-parser] - Skipped parsing type';
const consoleWarn = console.warn;
console.warn = (...args) => {
  if (args.some((arg) => typeof arg === 'string' && arg.includes(SKIPPED_TYPE_NOTICE))) {
    return;
  }
  consoleWarn(...args);
};

/**
 * A leaked TypeScript AST node (e.g. a `SourceFile`, which holds a circular
 * `parent` pointer) is a TS node, not a manifest value. The CEM manifest schema
 * is plain JSON, so any TS-node-shaped value in it is always a leak — safe to drop.
 */
const isTsNode = (value) =>
  !!value &&
  typeof value === 'object' &&
  typeof value.kind === 'number' &&
  typeof value.pos === 'number' &&
  typeof value.end === 'number';

/**
 * Recursively remove leaked TS nodes from the manifest so the analyzer's final
 * `JSON.stringify` doesn't choke on their circular `parent` references. Node-shaped
 * values are deleted *before* recursing into them, so we never walk a circular
 * node; a `WeakSet` guards against any other cycles.
 */
const stripLeakedNodes = (value, seen) => {
  if (!value || typeof value !== 'object' || seen.has(value)) {
    return;
  }
  seen.add(value);

  if (Array.isArray(value)) {
    for (let i = 0; i < value.length; i++) {
      if (isTsNode(value[i])) {
        value[i] = undefined;
      } else {
        stripLeakedNodes(value[i], seen);
      }
    }
    return;
  }

  for (const key of Object.keys(value)) {
    if (isTsNode(value[key])) {
      delete value[key];
    } else {
      stripLeakedNodes(value[key], seen);
    }
  }
};

export default {
  litelement: true,
  globs: ['src/components/**/*.ts'],
  exclude: ['**/*.stories.ts', '**/*.styles.ts', '**/*.test.ts'],
  outdir: 'dist',
  // The type parser needs a real TypeScript program so it can resolve type
  // aliases (`SizeValue`) back to their union members ('small' | 'medium' |
  // 'large'). Without this the manifest only records the alias name, and
  // Storybook renders a text box instead of a select.
  overrideModuleCreation: ({ts, globs}) => {
    const program = getTsProgram(ts, globs, 'tsconfig.json');
    return program
      .getSourceFiles()
      .filter((sf) => globs.find((glob) => sf.fileName.includes(glob)));
  },
  plugins: [
    // Expand type aliases into `parsedTypes`, which `.storybook/preview.ts`
    // reads via `setStorybookHelpersConfig({typeRef: 'parsedTypes'})`.
    typeParserPlugin({propertyName: 'parsedTypes'}),

    // Add a plugin to prevent inheritance tree analysis errors
    {
      name: 'skip-external-inheritance',
      packageLinkPhase({customElementsManifest}) {
        // Clear out problematic inheritance chains from external libraries
        customElementsManifest?.modules?.forEach((module) => {
          module?.declarations?.forEach((declaration) => {
            if (declaration.customElement && declaration.superclass) {
              // Only keep inheritance from local classes
              if (
                declaration.superclass.package ||
                declaration.superclass.module?.startsWith('node_modules') ||
                declaration.superclass.module?.startsWith('@lion') ||
                declaration.superclass.module?.startsWith('@awesome.me')
              ) {
                // Keep the superclass info but don't let CEM traverse it
                declaration.superclass = {
                  name: declaration.superclass.name,
                };
              }
            }
          });
        });
      },
    },
    // The manifest is the package's public API description: Storybook's
    // property tables and editors' IntelliSense are generated from it. The
    // analyzer records every class member it finds, so without this the tables
    // list internals — `craft-permission-tree` advertised a `#treeId`, and 45
    // elements between them exposed 408 members no consumer can call.
    //
    // Dropped: TypeScript `private`/`protected`, JS `#private` fields, and the
    // `_`/`__` prefixes Lion and this package use for the same thing. Anything
    // meant to be public and prefixed should be renamed rather than exempted.
    {
      name: 'drop-internal-members',
      packageLinkPhase({customElementsManifest}) {
        const isInternal = (member) =>
          member.privacy === 'private' ||
          member.privacy === 'protected' ||
          member.name?.startsWith('#') ||
          member.name?.startsWith('_');

        customElementsManifest?.modules?.forEach((module) => {
          module?.declarations?.forEach((declaration) => {
            if (declaration.members) {
              declaration.members = declaration.members.filter(
                (member) => !isInternal(member)
              );
            }
          });
        });
      },
    },

    // The analyzer also infers events from `dispatchEvent()` calls, reading the
    // first argument literally — so a helper that dispatches
    // `new CustomEvent(type, …)` is recorded as an event named `type`. That is
    // an artifact of the call site, not API, and it reaches the docs tables.
    //
    // Once a class declares its events with `@fires`, that list is taken as the
    // whole story and inferred extras are dropped. A class with no `@fires` is
    // left alone, so nothing is hidden from the guard that requires every
    // remaining event to be described.
    {
      name: 'declared-events-win',
      packageLinkPhase({customElementsManifest}) {
        customElementsManifest?.modules?.forEach((module) => {
          module?.declarations?.forEach((declaration) => {
            const events = declaration.events;

            if (!events?.some((event) => event.description)) {
              return;
            }

            declaration.events = events.filter((event) => event.description);
          });
        });
      },
    },

    // Strip any leaked TS AST nodes so the analyzer can serialize the manifest.
    // analyzer@0.11.0 (under Node 24) leaks `SourceFile` nodes into the manifest,
    // and their circular `parent` pointers crash the final `JSON.stringify`. Runs
    // last so it cleans whatever earlier phases/plugins leave behind.
    {
      name: 'strip-leaked-ts-nodes',
      packageLinkPhase({customElementsManifest}) {
        stripLeakedNodes(customElementsManifest, new WeakSet());
      },
    },
  ],
};
