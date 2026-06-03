export default {
  litelement: true,
  globs: ['src/components/**/*.ts'],
  exclude: ['**/*.stories.ts', '**/*.styles.ts', '**/*.test.ts'],
  outdir: 'dist',
  plugins: [
    // Capture custom JSDoc tags used to drive PHP component generation.
    // Unknown tags are dropped by the analyzer otherwise, so we read them off
    // the AST here and attach them to the manifest:
    //   @phpComponent          (class)    opt this element into PHP generation
    //   @phpType {Type}        (property) override the generated PHP type
    {
      name: 'php-codegen-tags',
      analyzePhase({ts, node, moduleDoc}) {
        if (!ts.isClassDeclaration(node) || !node.name) {
          return;
        }

        const decl = moduleDoc.declarations?.find(
          (d) => d.name === node.name.getText()
        );
        if (!decl) {
          return;
        }

        const findTag = (n, name) =>
          (ts.getJSDocTags(n) || []).find(
            (t) => t.tagName?.getText() === name
          );

        if (findTag(node, 'phpComponent')) {
          decl.phpComponent = true;
        }

        for (const member of node.members || []) {
          const tag = findTag(member, 'phpType');
          if (!tag) {
            continue;
          }

          const phpType = String(tag.comment ?? '')
            .trim()
            .replace(/^\{|\}$/g, '')
            .trim();
          if (!phpType) {
            continue;
          }

          const fieldName = member.name?.getText();
          const field = decl.members?.find(
            (m) => m.kind === 'field' && m.name === fieldName
          );
          if (field) {
            field.phpType = phpType;
          }
          const attr = decl.attributes?.find((a) => a.fieldName === fieldName);
          if (attr) {
            attr.phpType = phpType;
          }
        }
      },
    },
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
