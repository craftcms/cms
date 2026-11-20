/** A run of a message's text, and whether it was marked for emphasis. */
export interface EmphasisPart {
  text: string;
  strong: boolean;
}

/**
 * Matches the `*Name*` markers Craft puts around a field's name in a
 * validation message: Markdown emphasis on a single line, with no asterisk
 * inside. A lone asterisk is left as text.
 */
const EMPHASIS = /(\*+)([^*\n]+)\1/g;

/** Splits a message into plain and emphasized runs. */
export function emphasisParts(message: string): EmphasisPart[] {
  const parts: EmphasisPart[] = [];
  const pattern = new RegExp(EMPHASIS);
  let index = 0;
  let match: RegExpExecArray | null;

  while ((match = pattern.exec(message)) !== null) {
    if (match.index > index) {
      parts.push({text: message.slice(index, match.index), strong: false});
    }
    parts.push({text: match[2]!, strong: true});
    index = match.index + match[0].length;
  }

  if (index < message.length) {
    parts.push({text: message.slice(index), strong: false});
  }

  return parts;
}

/** The message's text with its emphasis markers removed. */
export function stripEmphasis(message: string): string {
  return message.replace(EMPHASIS, '$2');
}
