# Condition Builder

Condition builder is a fork of the [vue query builder](https://github.com/dabernathy89/vue-query-builder)

Embedding the builder can be done using JS with:

```html
<div class="pane">
  <div id="element-query-builder"></div>
</div>
```

```javascript
new Craft.VueConditionBuilder({
    container: '#element-query-builder',
    rules: [],
    query: {},
    maxDepth: 3,
    defaultOperator: 'all'
});
```

All `Craft.VueConditionBuilder` object settings are props for the `ConditionBuilder` vue component.


Settings (Props):

