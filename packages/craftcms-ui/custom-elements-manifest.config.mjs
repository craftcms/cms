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
  plugins: [
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
