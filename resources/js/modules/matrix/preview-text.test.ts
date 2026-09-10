import {describe, expect, it} from 'vite-plus/test';
import {blockPreviewParts} from '@/modules/matrix/preview-text';

function fields(html: string): HTMLElement {
  const container = document.createElement('div');
  container.innerHTML = html;

  return container;
}

describe('blockPreviewParts', () => {
  it('summarises one entry per field, joining a field’s own inputs', () => {
    expect(
      blockPreviewParts(
        fields(`
          <craft-field>
            <input type="text" value="Hello">
            <input type="text" value="there">
          </craft-field>
          <craft-field><textarea>Body copy</textarea></craft-field>
        `)
      )
    ).toEqual(['Hello, there', 'Body copy']);
  });

  it('skips hidden inputs and empty fields', () => {
    expect(
      blockPreviewParts(
        fields(`
          <craft-field><input type="hidden" value="uid:1"></craft-field>
          <craft-field><input type="text" value=""></craft-field>
          <craft-field><input type="text" value="Kept"></craft-field>
        `)
      )
    ).toEqual(['Kept']);
  });

  it('reads a select by its selected option, not its value', () => {
    expect(
      blockPreviewParts(
        fields(`
          <craft-field>
            <select><option value="a">Alpha</option><option value="b" selected>Beta</option></select>
          </craft-field>
        `)
      )
    ).toEqual(['Beta']);
  });

  it('leaves the fields of a nested block to that block', () => {
    // A nested Matrix's own fields belong to its blocks' summaries, not to
    // the field that holds them.
    expect(
      blockPreviewParts(
        fields(`
          <craft-field>
            <input type="text" value="Outer">
            <div class="matrixblock">
              <craft-field><input type="text" value="Inner"></craft-field>
            </div>
          </craft-field>
        `)
      )
    ).toEqual(['Outer']);
  });

  it('ignores the unlit half of a switch', () => {
    expect(
      blockPreviewParts(
        fields(`
          <craft-field>
            <div class="lightswitch on">
              <span class="label off">Off</span><span class="label on">On</span>
            </div>
          </craft-field>
        `)
      )
    ).toEqual(['On']);
  });
});
