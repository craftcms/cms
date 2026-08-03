// The `d3` bundle ships no bundled types and `@types/d3` isn't installed. The
// chart module uses d3 loosely (everything is `any`), so declare the module as
// untyped rather than pulling in the full `@types/d3` graph.
declare module 'd3';
