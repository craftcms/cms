// The `punycode` userland package ships no types. Declare the small surface we
// use (IDN <-> ASCII conversion) for the Link input's URL validation.
declare module 'punycode/' {
  export function toASCII(input: string): string;
  export function toUnicode(input: string): string;
  const punycode: {
    toASCII: (input: string) => string;
    toUnicode: (input: string) => string;
  };
  export default punycode;
}
