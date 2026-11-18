/**
 * Craft 5's `Craft.publishableSections`, for legacy and plugin scripts.
 *
 * Core sends the sections and entry types the user can create as
 * `Craft.creatableSections`. This rebuilds Craft 5's shape from them when the
 * global is read (`CpAsset::_publishableSections()` and `_entryTypes()`), so
 * the server sends nothing more. It follows the latest Inertia page, whose
 * visits can change the requested site and the sections.
 */
(() => {
  const Craft = (window.Craft = window.Craft || {});

  /** The latest Inertia page, which replaces the config the page loaded with. */
  let page = null;

  document.addEventListener('inertia:navigate', (event) => {
    page = event.detail.page;
  });

  // A value the server or a script set instead, such as from a
  // `CpDataResolving` listener, wins as it did in Craft 5.
  const existing = Object.getOwnPropertyDescriptor(
    Craft,
    'publishableSections'
  );
  let assigned = existing && 'value' in existing ? existing.value : undefined;
  let cache = {sections: null, siteId: null, value: undefined};

  /** Craft 5's sections, from core's sections and the requested site. */
  function publishableSections(sections, siteId) {
    return (
      sections
        // Craft 5 left out sections without entry types in any site
        .filter((section) => section.sites.length)
        .map((section) => {
          const entryTypes = new Map(
            section.entryTypes.map((entryType) => [entryType.id, entryType])
          );
          const entryTypesFor = (id) =>
            section.entryTypeIdsBySite[id].map((typeId) => {
              const {handle, name} = entryTypes.get(typeId);

              return {handle, id: typeId, name};
            });
          const entryTypesBySite = Object.fromEntries(
            section.sites.map((id) => [id, entryTypesFor(id)])
          );

          return {
            // The requested site's, or else the first site's
            entryTypes: entryTypesFor(
              section.sites.includes(siteId) ? siteId : section.sites[0]
            ),
            entryTypesBySite,
            handle: section.handle,
            id: section.id,
            name: section.name,
            sites: [...section.sites],
            type: section.type,
            uid: section.uid,
            canSave: section.canSave,
          };
        })
    );
  }

  Object.defineProperty(Craft, 'publishableSections', {
    configurable: true,
    enumerable: true,
    get() {
      if (assigned !== undefined) {
        return assigned;
      }

      const sections =
        page?.props?.craft?.general?.creatableSections ??
        Craft.creatableSections;
      const siteId = page?.props?.craft?.site?.id ?? Craft.siteId;

      if (!Array.isArray(sections)) {
        return undefined;
      }

      // Built once per change, so repeated reads return the same array
      if (cache.sections !== sections || cache.siteId !== siteId) {
        cache = {
          sections,
          siteId,
          value: publishableSections(sections, siteId),
        };
      }

      return cache.value;
    },
    set(value) {
      assigned = value;
    },
  });
})();
