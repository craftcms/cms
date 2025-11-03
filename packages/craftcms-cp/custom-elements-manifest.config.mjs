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
  ],
};
