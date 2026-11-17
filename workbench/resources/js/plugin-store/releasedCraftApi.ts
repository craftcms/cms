import {apiClient} from '@craftcms/ui/utilities/api/apiClient';

/** A released Craft version the API matches plugins against. */
const releasedVersion = '5.9.0';

// This install reports an unreleased Craft version, which the API finds no
// compatible plugins for. Reporting a released one lets the store list and sell
// plugins as it would to a real install, so the cart can be filled.
apiClient.interceptors.request.use((config) => {
  const system = config.headers['X-Craft-System'];

  return system
    ? {
        ...config,
        headers: {
          ...config.headers,
          'X-Craft-System': system.replace(
            /craft:[^;,]+/,
            `craft:${releasedVersion}`
          ),
        },
      }
    : config;
});
