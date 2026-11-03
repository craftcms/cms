/**
 * Three demo "plugins" that add to the AI assistant's context, to see how its
 * Add menu holds several plugins' sources and chips. Uses only what a
 * third-party plugin gets: the global `Cp` object.
 *
 * Their context types aren't registered on the server, so the assistant
 * leaves their items out of what the model sees.
 */
const sources = [
  {
    id: 'commerce:order',
    key: 'commerce:order:1042',
    label: 'Order #1042',
    icon: 'cart-shopping',
    data: {orderId: 1042},
  },
  {
    id: 'seo:audit',
    key: 'seo:audit:home',
    label: 'SEO audit: Homepage',
    icon: 'magnifying-glass',
    data: {uri: '__home__'},
  },
  {
    id: 'analytics:traffic',
    key: 'analytics:traffic:30d',
    label: 'Traffic, last 30 days',
    icon: 'chart-line',
    data: {days: 30},
  },
];

window.Cp.booting((cp) => {
  sources.forEach(({id, key, label, icon, data}, index) => {
    cp.$assistant.registerContextSource({
      id,
      label: () => label,
      icon,
      order: index + 1,
      available: () => cp.$assistant.getContext(key) === undefined,
      add: () => cp.$assistant.addContext({type: id, key, label, icon, data}),
    });
  });
});
