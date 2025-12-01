import {formatNumber} from './format.js';

export function tokenizePattern(
  pattern: string
): Array<string | string[]> | false {
  let depth = 1;
  let start: number;
  let pos: number;
  // Get an array of the string characters (factoring in 3+ byte chars)
  const chars = [...pattern];
  if ((start = pos = chars.indexOf('{')) === -1) {
    return [pattern];
  }
  let tokens: Array<string | string[]> = [chars.slice(0, pos).join('')];
  while (true) {
    let open: number = chars.indexOf('{', pos + 1);
    let close: number = chars.indexOf('}', pos + 1);

    if (open === -1 && close === -1) {
      break;
    }

    if (open === -1) {
      open = chars.length;
    }

    if (close !== -1 && close > open) {
      depth++;
      pos = open;
    } else if (close !== -1) {
      depth--;
      pos = close;
    }
    if (depth === 0) {
      tokens.push(
        chars
          .slice(start + 1, pos)
          .join('')
          .split(',', 3)
      );
      start = pos + 1;
      tokens.push(
        chars.slice(start, open === -1 ? chars.length : open).join('')
      );
      start = open === -1 ? chars.length : open;
    }

    if (depth !== 0 && (open === -1 || close === -1)) {
      break;
    }
  }
  if (depth !== 0) {
    return false;
  }

  return tokens;
}

export function parseToken(
  token: string[],
  args: Record<string, any> = {}
): string | number | boolean | null | false {
  // parsing pattern based on ICU grammar:
  // http://icu-project.org/apiref/icu4c/classMessageFormat.html#details
  const param = token[0]?.trim();
  if (!param || typeof args[param] === 'undefined') {
    return `{${token.join(',')}}`;
  }
  const arg = args[param];
  const type = typeof token[1] !== 'undefined' ? token[1].trim() : 'none';
  switch (type) {
    case 'number':
      return (() => {
        let format = typeof token[2] !== 'undefined' ? token[2].trim() : null;
        if (format !== null && format !== 'integer') {
          throw `Message format 'number' is only supported for integer values.`;
        }
        let number = formatNumber(arg);
        let pos;
        if (format === null && (pos = `${arg}`.indexOf('.')) !== -1) {
          number += `.${arg.substring(pos + 1)}`;
        }
        return number;
      })();
    case 'none':
      return arg;
    case 'select':
      return (() => {
        /* http://icu-project.org/apiref/icu4c/classicu_1_1SelectFormat.html
                      selectStyle = (selector '{' message '}')+
                      */
        if (typeof token[2] === 'undefined') {
          return false;
        }
        let select = tokenizePattern(token[2]);
        if (select === false) {
          return false;
        }
        let c = select.length;
        let message: string | false = false;
        for (let i = 0; i + 1 < c; i++) {
          if (Array.isArray(select[i]) || !Array.isArray(select[i + 1])) {
            return false;
          }
          let selector = (select[i++] as string).trim();
          if ((message === false && selector === 'other') || selector == arg) {
            message = (select[i] as string[]).join(',');
          }
        }
        if (message === false) {
          return false;
        }
        return formatMessage(message, args);
      })();
    case 'plural':
      return (() => {
        /* http://icu-project.org/apiref/icu4c/classicu_1_1PluralFormat.html
                      pluralStyle = [offsetValue] (selector '{' message '}')+
                      offsetValue = "offset:" number
                      selector = explicitValue | keyword
                      explicitValue = '=' number  // adjacent, no white space in between
                      keyword = [^[[:Pattern_Syntax:][:Pattern_White_Space:]]]+
                      message: see MessageFormat
                      */
        if (typeof token[2] === 'undefined') {
          return false;
        }
        let plural = tokenizePattern(token[2]);
        if (plural === false) {
          return false;
        }
        const c = plural.length;
        let message: string | false = false;
        let offset = 0;
        for (let i = 0; i + 1 < c; i++) {
          if (
            typeof plural[i] === 'object' ||
            typeof plural[i + 1] !== 'object'
          ) {
            return false;
          }
          let selector = (plural[i++] as string).trim();
          let selectorChars = [...selector];

          if (i === 1 && selector.substring(0, 7) === 'offset:') {
            let pos = [...selector.replace(/[\n\r\t]/g, ' ')].indexOf(' ', 7);
            if (pos === -1) {
              throw new Error('Message pattern is invalid.');
            }
            offset = parseInt(selectorChars.slice(7, pos).join('').trim());
            selector = selectorChars
              .slice(pos + 1, pos + 1 + selectorChars.length)
              .join('')
              .trim();
          }
          if (
            (message === false && selector === 'other') ||
            (selector[0] === '=' &&
              parseInt(
                selectorChars.slice(1, 1 + selectorChars.length).join('')
              ) === arg) ||
            (selector === 'one' && arg - offset === 1)
          ) {
            const nextItem = plural[i];
            const pluralMessage = (
              typeof nextItem === 'string' ? [nextItem] : (nextItem as string[])
            )
              .map((p: string) => {
                return p.replace('#', String(arg - offset));
              })
              .join(',');
            message = pluralMessage;
          }
        }
        if (message === false) {
          return false;
        }
        return formatMessage(message, args);
      })();
    default:
      throw new Error(`Message format '${type}' is not supported.`);
  }
}

export function formatMessage(pattern: string, params: object): string {
  let tokens;

  if ((tokens = tokenizePattern(pattern)) === false) {
    throw new Error('Message pattern is invalid.');
  }

  for (let i = 0; i < tokens.length; i++) {
    let token = tokens[i];
    if (typeof token === 'object') {
      const result = parseToken(token, params);
      if (result === false) {
        throw new Error('Message pattern is invalid.');
      }
      tokens[i] = String(result);
    }
  }
  return tokens.join('');
}

export function t(
  category: string,
  message: string,
  params?: Record<any, any>,
  store?: Record<string, any>
): string {
  if (
    store &&
    typeof store[category] !== 'undefined' &&
    typeof store[category][message] !== 'undefined'
  ) {
    message = store[category][message];
  }

  if (params) {
    return formatMessage(message, params);
  }

  return message;
}
