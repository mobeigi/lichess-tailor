import { Chessground } from 'https://cdn.jsdelivr.net/npm/@lichess-org/chessground@10.1.1/+esm';
import { Chess } from 'https://cdn.jsdelivr.net/npm/chess.js@1.1.0/+esm';

// ── Texture library ──────────────────────────────────────────────────────────
// Loaded server-side from /assets/images/textures/library/<group>/<name>.svg
// Structure: { groupKey: { label: string, textures: { name: svgString } } }
// To add a texture: drop the SVG in the right subfolder — no code changes needed.
let TEXTURE_LIBRARY = {};

// Flat lookup: textureName -> svgString (for makeTexturePattern)
let TEXTURES = {};
let NO_TEXTURE_SVG = '';

// Async initialization
async function loadTextures() {
  try {
    const res = await fetch('/api/textures.php');
    const data = await res.json();
    TEXTURE_LIBRARY = data.TEXTURE_LIBRARY;
    NO_TEXTURE_SVG = data.NO_TEXTURE_SVG;
    
    // Build flat lookup
    for (const group of Object.values(TEXTURE_LIBRARY)) {
      for (const [name, svg] of Object.entries(group.textures)) {
        TEXTURES[name] = svg;
      }
    }
    
    // Initialise icon boxes only after textures load
    syncTxIcon('tx1');
    syncTxIcon('tx2');
    update();
  } catch (err) {
    console.error('Failed to load textures from API:', err);
  }
}



// ── Custom SVG store ─────────────────────────────────────────────────────────
// Holds the raw SVG string for each channel when the user uploads a file.
// Keyed by prefix ('tx1' or 'tx2'). Null means no custom SVG loaded.
const customSVG = { tx1: null, tx2: null };

// Strip non-essential attributes from an SVG string to reduce its size before
// encoding into the URL. Keeps only the structure and path data.
function sanitiseSVG(raw) {
  return raw
    .replace(/\s+version="[^"]*"/g, '')
    .replace(/\s+xmlns:xlink="[^"]*"/g, '')
    .replace(/\s+x="0"\s+y="0"/g, '')
    .replace(/\s+x="0"/g, '')
    .replace(/\s+y="0"/g, '')
    .replace(/\s+width="[^"]*"/g, '')
    .replace(/\s+height="[^"]*"/g, '')
    .replace(/\s+style="[^"]*"/g, '')
    .replace(/\s+xml:space="[^"]*"/g, '')
    .replace(/\s+enable-background="[^"]*"/g, '')
    .replace(/\s+data-name="[^"]*"/g, '')
    .replace(/\s+data-original="[^"]*"/g, '')
    .replace(/\s+opacity="1"/g, '')
    .replace(/\s+class="[^"]*"/g, '')
    .replace(/>\s+</g, '><')
    .trim();
}

// Build the texture <pattern> block for one square type.
// patId: 'tp1' or 'tp2', sqSize: SVG units per square (1 in our 8×8 viewBox).
// The texture SVG paths are scaled from 64×64 to sqSize×sqSize via a transform.
// colour: a CSS hex colour string (e.g. '#000000') applied to stroke and fill.
function makeTexturePattern(patId, txId, alphaPercent, sqSize, colour, prefix) {
  // Resolve the SVG source: named registry or custom upload
  let svgSrc = null;
  if (txId === 'custom') {
    svgSrc = customSVG[prefix] ?? null;
  } else {
    svgSrc = TEXTURES[txId] ?? null;
  }
  if (!svgSrc) return '';

  const opacity = parseFloat((alphaPercent / 100).toFixed(2));
  // Determine full viewBox for scaling/positioning (fallback to 0 0 64 64 if not found).
  // viewBox="minX minY width height"
  let minX = 0, minY = 0, viewBoxWidth = 64, viewBoxHeight = 64;
  const vbMatch = svgSrc.match(/viewBox=["']([^"']*)["']/);
  if (vbMatch) {
    const parts = vbMatch[1].trim().split(/[\s,]+/).map(parseFloat);
    if (parts.length === 4 && parts[2] > 0 && parts[3] > 0) {
      [minX, minY, viewBoxWidth, viewBoxHeight] = parts;
    }
  }
  const scaleX = sqSize / viewBoxWidth;
  const scaleY = sqSize / viewBoxHeight;

  // Strip the outer <svg> wrapper, then replace all explicit black colour
  // values (#000000, #000, black) with the chosen colour. The wrapper <g>
  // also sets fill and stroke directly so paths with no explicit colour
  // (which inherit SVG's default black) are covered too.
  // fill="none" and stroke="none" are left untouched as they are structural.
  const inner = svgSrc
    .replace(/^<svg[^>]*>/, '')
    .replace(/<\/svg>\s*$/, '')
    .replace(/#000000/g, colour)
    .replace(/#000(?![0-9a-fA-F])/g, colour)
    .replace(/(["'])black\1/g, `$1${colour}$1`);
  return [
    `<pattern id="${patId}" x="0" y="0" width="${sqSize}" height="${sqSize}"`,
    `         patternUnits="userSpaceOnUse">`,
    `  <g fill="${colour}" stroke="${colour}" opacity="${opacity}" transform="translate(${-minX * scaleX}, ${-minY * scaleY}) scale(${scaleX}, ${scaleY})">`,
    inner,
    `  </g>`,
    `</pattern>`,
  ].join('\n');
}

function makeSVG(c1, c2, tx1Id = 'none', tx1Alpha = 10, tx1Colour = '#000000', tx2Id = 'none', tx2Alpha = 10, tx2Colour = '#000000') {
  const sqSize = 1; // each square is 1 unit in the 8×8 viewBox
  const pat1 = makeTexturePattern('tp1', tx1Id, tx1Alpha, sqSize, tx1Colour, 'tx1');
  const pat2 = makeTexturePattern('tp2', tx2Id, tx2Alpha, sqSize, tx2Colour, 'tx2');
  const hasTex1 = pat1 !== '';
  const hasTex2 = pat2 !== '';

  // The fill for each square: solid colour rect + optional texture rect on top.
  // When a texture is active we wrap both rects in a <g id="e/f"> so that
  // the existing <use> elements clone the whole group (colour + texture).
  const sq1Fill = hasTex1
    ? [`        <g id="e">`,
       `          <rect width="1" height="1" fill="${c1}"/>`,
       `          <rect width="1" height="1" fill="url(#tp1)"/>`,
       `        </g>`].join('\n')
    : `        <rect width="1" height="1" id="e" fill="${c1}"/>`;
  const sq2Fill = hasTex2
    ? [`        <g id="f">`,
       `          <rect y="1" width="1" height="1" fill="${c2}"/>`,
       `          <rect y="1" width="1" height="1" fill="url(#tp2)"/>`,
       `        </g>`].join('\n')
    : `        <rect y="1" width="1" height="1" id="f" fill="${c2}"/>`;

  const defs = (pat1 || pat2)
    ? `<defs>\n${[pat1, pat2].filter(Boolean).join('\n')}\n</defs>`
    : '';

  return [
    '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="http://www.w3.org/1999/xlink"',
    '     viewBox="0 0 8 8" shape-rendering="crispEdges">',
    defs,
    '<g id="a">',
    '  <g id="b">',
    '    <g id="c">',
    '      <g id="d">',
    sq1Fill,
    '        <use x="1" y="1" href="#e" x:href="#e"/>',
    sq2Fill,
    '        <use x="1" y="-1" href="#f" x:href="#f"/>',
    '      </g>',
    '      <use x="2" href="#d" x:href="#d"/>',
    '    </g>',
    '    <use x="4" href="#c" x:href="#c"/>',
    '  </g>',
    '  <use y="2" href="#b" x:href="#b"/>',
    '</g>',
    '<use y="4" href="#a" x:href="#a"/>',
    '</svg>'
  ].filter(s => s !== '').join('\n');
}

function toRgba(hex, alphaPercent) {
  const r = parseInt(hex.slice(1, 3), 16);
  const g = parseInt(hex.slice(3, 5), 16);
  const b = parseInt(hex.slice(5, 7), 16);
  const a = parseFloat((alphaPercent / 100).toFixed(2));
  return `rgba(${r}, ${g}, ${b}, ${a})`;
}

function ovRgba(prefix) {
  return toRgba(
    document.getElementById(`${prefix}-color`).value,
    parseInt(document.getElementById(`${prefix}-alpha`).value)
  );
}

function generateCSS(c1rgba, c2rgba) {
  const tx1Type   = txState.tx1;
  const tx1Alpha  = parseInt(document.getElementById('tx1-alpha').value);
  const tx1Colour = document.getElementById('tx1-color').value;
  const tx2Type   = txState.tx2;
  const tx2Alpha  = parseInt(document.getElementById('tx2-alpha').value);
  const tx2Colour = document.getElementById('tx2-color').value;

  const b64 = btoa(makeSVG(c1rgba, c2rgba, tx1Type, tx1Alpha, tx1Colour, tx2Type, tx2Alpha, tx2Colour));
  const lmRgba  = ovRgba('lm');
  const selRgba = ovRgba('sel');
  const mdRgba  = ovRgba('md');
  const ocRgba  = ovRgba('oc');
  const pmRgba  = ovRgba('pm');
  const mdMode  = document.getElementById('md-mode').value;

  const mdCss = mdMode === 'dot'
    ? `background: radial-gradient(${mdRgba} 19%, rgba(0, 0, 0, 0) 20%) !important;`
    : `background: unset !important;\n      background-color: ${mdRgba} !important;`;

  return `/* ==UserStyle==
@name           Lichess Tailor style for lichess.org
@namespace      github.com/mobeigi/lichess-tailor
@version        1.0.0
@description    Custom board colours and overlay highlights for lichess.org, generated by Lichess Tailor.
@author         Mo Beigi
==/UserStyle== */

@-moz-document domain("lichess.org") {
    cg-board::before {
      background-color: ${c1rgba} !important;
      background-image: url('data:image/svg+xml;base64,${b64}') !important;
      filter: unset !important;
    }

    cg-wrap coords:nth-child(odd) coord:nth-child(odd),
    cg-wrap.orientation-black coords:nth-child(odd) coord:nth-child(even),
    cg-wrap coords.files:nth-child(even) coord:nth-child(odd),
    cg-wrap.orientation-black coords.files:nth-child(even) coord:nth-child(even) {
      color: ${c1rgba} !important;
    }

    cg-wrap coords:nth-child(odd) coord:nth-child(even),
    cg-wrap.orientation-black coords:nth-child(odd) coord:nth-child(odd),
    cg-wrap coords.files:nth-child(even) coord:nth-child(even),
    cg-wrap.orientation-black coords.files:nth-child(even) coord:nth-child(odd) {
      color: ${c2rgba} !important;
    }

    cg-board square.last-move {
      background-color: ${lmRgba} !important;
    }

    cg-board square.selected {
      background-color: ${selRgba} !important;
    }

    cg-board square.move-dest, cg-board square.premove-dest {
      ${mdCss}
    }

    cg-board square.oc {
      background: unset !important;
      background-color: ${ocRgba} !important;
    }

    cg-board square.current-premove, cg-board square.last-premove {
      background-color: ${pmRgba} !important;
    }
}`;
}

function update() {
  const c1rgba = ovRgba('sq1');
  const c2rgba = ovRgba('sq2');
  const fullCSS = generateCSS(c1rgba, c2rgba);
  const inner = fullCSS.replace(/^[\s\S]*?@-moz-document[^{]+\{([\s\S]*)\}$/, '$1').trim();
  document.getElementById('preview-css').textContent = inner;
  document.getElementById('css-out').value = fullCSS;
}

function setSliderGradient(slider, hex) {
  const r = parseInt(hex.slice(1, 3), 16);
  const g = parseInt(hex.slice(3, 5), 16);
  const b = parseInt(hex.slice(5, 7), 16);
  slider.style.background =
    `linear-gradient(to right, rgba(${r},${g},${b},0.15) 0%, rgb(${r},${g},${b}) 100%)`;
}

function wireOverlay(prefix) {
  const picker = document.getElementById(`${prefix}-color`);
  const hex    = document.getElementById(`${prefix}-hex`);
  const swatch = document.getElementById(`${prefix}-sw`);
  const slider = document.getElementById(`${prefix}-alpha`);
  const valEl  = document.getElementById(`${prefix}-alpha-val`);

  swatch.style.background = picker.value;
  setSliderGradient(slider, picker.value);

  picker.addEventListener('input', () => {
    hex.value = picker.value;
    hex.classList.remove('bad');
    swatch.style.background = picker.value;
    setSliderGradient(slider, picker.value);
    update();
    syncPresetHighlight();
  });
  hex.addEventListener('input', () => {
    const v = hex.value.trim();
    if (/^#[0-9A-Fa-f]{6}$/.test(v)) {
      picker.value = v;
      hex.classList.remove('bad');
      swatch.style.background = v;
      setSliderGradient(slider, v);
      update();
      syncPresetHighlight();
    } else {
      hex.classList.add('bad');
    }
  });
  slider.addEventListener('input', () => {
    valEl.textContent = slider.value + '%';
    update();
    syncPresetHighlight();
  });
}

const PRESETS = {
  duo: [
    {
      name: 'Walnut',
      sq1: '#edd6b0', sq1A: 100, sq2: '#b88762', sq2A: 100,
      lm: '#f6eb72', lmA: 60,
      sel: '#5aa7cc', selA: 60,
      md: '#573017', mdA: 60, mdMode: 'dot',
      oc: '#ff7d7d', ocA: 60,
      pm: '#bb37bc', pmA: 60,
    },
    {
      name: 'Classic',
      sq1: '#f5f5f0', sq1A: 100, sq2: '#5c5c5c', sq2A: 100,
      lm: '#f6eb72', lmA: 60,
      sel: '#5aa7cc', selA: 60,
      md: '#888888', mdA: 60, mdMode: 'dot',
      oc: '#ff7d7d', ocA: 60,
      pm: '#bb37bc', pmA: 60,
    },
    {
      name: 'Forest',
      sq1: '#eeeed2', sq1A: 100, sq2: '#769656', sq2A: 100,
      lm: '#cdd16a', lmA: 70,
      sel: '#5bc0de', selA: 60,
      md: '#3a5a28', mdA: 60, mdMode: 'dot',
      oc: '#ff7d7d', ocA: 60,
      pm: '#bb37bc', pmA: 60,
    },
    {
      name: 'Crimson',
      sq1: '#fce8e8', sq1A: 100, sq2: '#c0392b', sq2A: 100,
      lm: '#f6eb72', lmA: 60,
      sel: '#5aa7cc', selA: 60,
      md: '#7a1515', mdA: 60, mdMode: 'dot',
      oc: '#ff9944', ocA: 60,
      pm: '#bb37bc', pmA: 60,
    },
    {
      name: 'Ocean',
      sq1: '#ddeeff', sq1A: 100, sq2: '#2563a8', sq2A: 100,
      lm: '#f6eb72', lmA: 60,
      sel: '#48c9b0', selA: 60,
      md: '#0d2f5e', mdA: 60, mdMode: 'dot',
      oc: '#ff7d7d', ocA: 60,
      pm: '#bb37bc', pmA: 60,
    },
    {
      name: 'Midnight',
      sq1: '#e8e0f7', sq1A: 100, sq2: '#6d28d9', sq2A: 100,
      lm: '#f6eb72', lmA: 60,
      sel: '#5aa7cc', selA: 60,
      md: '#3b1074', mdA: 60, mdMode: 'dot',
      oc: '#ff7d7d', ocA: 60,
      pm: '#f59e0b', pmA: 60,
    },
    {
      name: 'Amber',
      sq1: '#fff8e7', sq1A: 100, sq2: '#d97706', sq2A: 100,
      lm: '#f6eb72', lmA: 60,
      sel: '#5aa7cc', selA: 60,
      md: '#7c3a00', mdA: 60, mdMode: 'dot',
      oc: '#ff7d7d', ocA: 60,
      pm: '#bb37bc', pmA: 60,
    },
  ]
};

function applyPreset(p) {
  function setOverlay(prefix, hex, alpha) {
    const picker = document.getElementById(`${prefix}-color`);
    const hexIn  = document.getElementById(`${prefix}-hex`);
    const sw     = document.getElementById(`${prefix}-sw`);
    const slider = document.getElementById(`${prefix}-alpha`);
    const valEl  = document.getElementById(`${prefix}-alpha-val`);
    picker.value = hex;
    hexIn.value  = hex;
    hexIn.classList.remove('bad');
    sw.style.background = hex;
    setSliderGradient(slider, hex);
    slider.value = alpha;
    valEl.textContent = alpha + '%';
  }
  setOverlay('sq1', p.sq1, p.sq1A);
  setOverlay('sq2', p.sq2, p.sq2A);
  setOverlay('lm',  p.lm,  p.lmA);
  setOverlay('sel', p.sel, p.selA);
  setOverlay('md',  p.md,  p.mdA);
  setOverlay('oc',  p.oc,  p.ocA);
  setOverlay('pm',  p.pm,  p.pmA);
  if (p.mdMode) document.getElementById('md-mode').value = p.mdMode;
  // Textures — only apply if present in the preset/params object
  ['tx1', 'tx2'].forEach(prefix => {
    const typeKey  = prefix;          // e.g. 'tx1'
    const alphaKey = prefix + 'A';    // e.g. 'tx1A'
    const colKey   = prefix + 'C';    // e.g. 'tx1C'
    const svgKey   = prefix + 'SVG';  // e.g. 'tx1SVG' — custom SVG content
    if (p[svgKey] !== undefined) {
      customSVG[prefix] = p[svgKey];
    }
    if (p[typeKey] !== undefined) {
      txState[prefix] = p[typeKey];
      // Sync icon if already rendered (may not be on first load — _syncTxIcons handles that)
      if (document.getElementById(`${prefix}-icon`)) syncTxIcon(prefix);
    }
    if (p[colKey] !== undefined) {
      const picker = document.getElementById(`${prefix}-color`);
      const hexIn  = document.getElementById(`${prefix}-hex`);
      const swatch = document.getElementById(`${prefix}-sw`);
      const slider = document.getElementById(`${prefix}-alpha`);
      picker.value = p[colKey];
      hexIn.value  = p[colKey];
      hexIn.classList.remove('bad');
      swatch.style.background = p[colKey];
      setSliderGradient(slider, p[colKey]);
    }
    if (p[alphaKey] !== undefined) {
      const slider = document.getElementById(`${prefix}-alpha`);
      const valEl  = document.getElementById(`${prefix}-alpha-val`);
      slider.value = p[alphaKey];
      valEl.textContent = p[alphaKey] + '%';
    }
  });
  update();
}

function syncPresetHighlight() {
  const style = 'duo';
  const container = document.getElementById('presets-grid');
  if (!container) return;
  const list = PRESETS[style] || [];
  const cur = {
    sq1:  document.getElementById('sq1-color').value,
    sq1A: parseInt(document.getElementById('sq1-alpha').value),
    sq2:  document.getElementById('sq2-color').value,
    sq2A: parseInt(document.getElementById('sq2-alpha').value),
    lm:   document.getElementById('lm-color').value,
    lmA:  parseInt(document.getElementById('lm-alpha').value),
    sel:  document.getElementById('sel-color').value,
    selA: parseInt(document.getElementById('sel-alpha').value),
    md:   document.getElementById('md-color').value,
    mdA:  parseInt(document.getElementById('md-alpha').value),
    mdMode: document.getElementById('md-mode').value,
    oc:   document.getElementById('oc-color').value,
    ocA:  parseInt(document.getElementById('oc-alpha').value),
    pm:   document.getElementById('pm-color').value,
    pmA:  parseInt(document.getElementById('pm-alpha').value),
  };
  container.querySelectorAll('.preset-item').forEach((item, i) => {
    const p = list[i];
    const match = p &&
      cur.sq1 === p.sq1 && cur.sq1A === p.sq1A &&
      cur.sq2 === p.sq2 && cur.sq2A === p.sq2A &&
      cur.lm === p.lm && cur.lmA === p.lmA &&
      cur.sel === p.sel && cur.selA === p.selA &&
      cur.md === p.md && cur.mdA === p.mdA && cur.mdMode === p.mdMode &&
      cur.oc === p.oc && cur.ocA === p.ocA &&
      cur.pm === p.pm && cur.pmA === p.pmA;
    item.classList.toggle('active', !!match);
  });
}

function renderPresets(style) {
  const container = document.getElementById('presets-grid');
  container.innerHTML = '';
  (PRESETS[style] || []).forEach(p => {
    const item = document.createElement('div');
    item.className = 'preset-item';

    const thumb = document.createElement('div');
    thumb.className = 'preset-thumb';
    thumb.innerHTML =
      `<svg viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg" width="100%" height="100%">` +
      `<polygon points="0,0 0,48 48,0" fill="${p.sq1}"/>` +
      `<polygon points="0,48 48,48 48,0" fill="${p.sq2}"/>` +
      `</svg>`;

    const name = document.createElement('div');
    name.className = 'preset-name';
    name.textContent = p.name;

    item.appendChild(thumb);
    item.appendChild(name);

    item.addEventListener('click', () => {
      applyPreset(p);
      syncPresetHighlight();
    });

    container.appendChild(item);
  });
  syncPresetHighlight();
}

wireOverlay('sq1');
wireOverlay('sq2');
wireOverlay('lm');
wireOverlay('sel');
wireOverlay('md');
wireOverlay('oc');
wireOverlay('pm');

document.getElementById('md-mode').addEventListener('change', () => { update(); syncPresetHighlight(); });

// ── Texture state ─────────────────────────────────────────────────────────────
// Keyed by 'tx1' / 'tx2'. txType is a texture name (from library), 'custom', or 'none'.
const txState = {
  tx1: 'none',
  tx2: 'none',
};

// Returns a friendly display name for a texture id
function txDisplayName(id) {
  if (id === 'none') return 'None';
  if (id === 'custom') return 'Custom';
  // Convert kebab-case to Title Case
  return id.replace(/-/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
}

// The no-texture SVG for the None option — served as-is, colours preserved


// Build a small preview SVG for the 40×40 icon/thumbnail.
// None uses no-texture.svg as-is. All others use off-black (#1a1a1a) on off-white background.
function makeThumbnailSVG(txId) {
  if (txId === 'none' || txId === null) return NO_TEXTURE_SVG || '';
  const svgSrc = txId === 'custom' ? customSVG.tx1 || customSVG.tx2 : TEXTURES[txId];
  if (!svgSrc) return '';
  return makeThumbnailSVGFromSrc(svgSrc);
}

// Update the icon box in the sidebar for a given prefix
function syncTxIcon(prefix) {
  const id     = txState[prefix];
  const icon   = document.getElementById(`${prefix}-icon`);
  const inner  = document.getElementById(`${prefix}-icon-inner`);
  const label  = document.getElementById(`${prefix}-icon-label`);
  const svgStr = id === 'none'
    ? (NO_TEXTURE_SVG || '')
    : id === 'custom'
      ? (customSVG[prefix] ? makeThumbnailSVGFromSrc(customSVG[prefix]) : '')
      : makeThumbnailSVG(id);
  const displayName = txDisplayName(id);
  inner.innerHTML = svgStr;
  label.textContent = displayName;
  icon.title = displayName;
  icon.classList.toggle('active', id !== 'none');
}

function makeThumbnailSVGFromSrc(svgSrc) {
  if (!svgSrc) return '';
  let vbAttr = 'viewBox="0 0 64 64"';
  const vbMatch = svgSrc.match(/viewBox=["']([^"']*)["']/);
  if (vbMatch) vbAttr = `viewBox="${vbMatch[1]}"`;
  const inner = svgSrc.replace(/^<svg[^>]*>/, '').replace(/<\/svg>\s*$/, '');
  return `<svg xmlns="http://www.w3.org/2000/svg" ${vbAttr} width="100%" height="100%"><g fill="#1a1a1a" stroke="#1a1a1a">${inner}</g></svg>`;
}

// ── Texture picker (library sidebar overlay) ──────────────────────────────────
let pickerActivePrefix = null; // which channel is being edited: 'tx1' or 'tx2'

function openTexturePicker(prefix) {
  pickerActivePrefix = prefix;
  const picker = document.getElementById('tx-picker');
  const title  = document.getElementById('tx-picker-title');
  title.textContent = prefix === 'tx1' ? 'Choose Texture for Light Squares' : 'Choose Texture for Dark Squares';
  renderLibraryPicker(prefix);
  picker.style.display = 'flex';
}

function closeTexturePicker() {
  document.getElementById('tx-picker').style.display = 'none';
  pickerActivePrefix = null;
}

function renderLibraryPicker(prefix) {
  const body = document.getElementById('tx-picker-body');
  body.innerHTML = '';
  const currentId = txState[prefix];

  // None option — first group
  const noneGroup = document.createElement('div');
  noneGroup.className = 'tx-lib-group';
  const noneItem = makeTxLibItem('none', currentId, prefix);
  const noneGrid = document.createElement('div');
  noneGrid.className = 'tx-lib-grid';
  noneGrid.appendChild(noneItem);
  noneGroup.appendChild(noneGrid);
  body.appendChild(noneGroup);

  // Library groups
  for (const [groupKey, group] of Object.entries(TEXTURE_LIBRARY)) {
    const groupEl = document.createElement('div');
    groupEl.className = 'tx-lib-group';

    const labelEl = document.createElement('div');
    labelEl.className = 'tx-lib-group-label';
    labelEl.textContent = group.label;
    groupEl.appendChild(labelEl);

    const grid = document.createElement('div');
    grid.className = 'tx-lib-grid';

    for (const [name] of Object.entries(group.textures)) {
      grid.appendChild(makeTxLibItem(name, currentId, prefix));
    }
    groupEl.appendChild(grid);
    body.appendChild(groupEl);
  }

  // Upload custom group
  const uploadGroup = document.createElement('div');
  uploadGroup.className = 'tx-lib-group';
  const uploadLabel = document.createElement('div');
  uploadLabel.className = 'tx-lib-group-label';
  uploadLabel.textContent = 'Custom';
  uploadGroup.appendChild(uploadLabel);
  const uploadGrid = document.createElement('div');
  uploadGrid.className = 'tx-lib-grid';

  // If custom SVG is loaded, show it as an option
  if (customSVG[prefix]) {
    uploadGrid.appendChild(makeTxLibItem('custom', currentId, prefix));
  }

  // Upload card
  const uploadItem = document.createElement('div');
  uploadItem.className = 'tx-lib-item tx-lib-upload';
  uploadItem.title = 'Upload SVG file';
  const uploadThumb = document.createElement('div');
  uploadThumb.className = 'tx-lib-thumb';
  uploadThumb.innerHTML = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>`;
  const uploadName = document.createElement('div');
  uploadName.className = 'tx-lib-name';
  uploadName.textContent = 'Upload';
  uploadItem.appendChild(uploadThumb);
  uploadItem.appendChild(uploadName);
  uploadItem.addEventListener('click', () => triggerUpload(prefix));
  uploadGrid.appendChild(uploadItem);
  uploadGroup.appendChild(uploadGrid);
  body.appendChild(uploadGroup);
}

function makeTxLibItem(txId, currentId, prefix) {
  const displayName = txDisplayName(txId);
  const item = document.createElement('div');
  item.className = 'tx-lib-item' + (txId === currentId ? ' active' : '');
  item.title = displayName;

  const thumb = document.createElement('div');
  thumb.className = 'tx-lib-thumb';

  if (txId === 'none') {
    thumb.innerHTML = NO_TEXTURE_SVG || '';
  } else {
    const svgSrc = txId === 'custom' ? customSVG[prefix] : TEXTURES[txId];
    if (svgSrc) {
      thumb.innerHTML = makeThumbnailSVGFromSrc(svgSrc);
    }
  }

  const name = document.createElement('div');
  name.className = 'tx-lib-name';
  name.textContent = displayName;

  item.appendChild(thumb);
  item.appendChild(name);

  item.addEventListener('click', () => {
    // Apply immediately and update preview — stay in picker
    txState[prefix] = txId;
    syncTxIcon(prefix);
    update();
    // Update active highlight within picker
    document.querySelectorAll('#tx-picker-body .tx-lib-item:not(.tx-lib-upload)').forEach(el => {
      el.classList.remove('active');
    });
    item.classList.add('active');
  });

  return item;
}

// ── Upload handling ────────────────────────────────────────────────────────────
function triggerUpload(prefix) {
  const fileInput = document.getElementById(`${prefix}-file`);
  fileInput.value = '';
  fileInput.click();
}

['tx1', 'tx2'].forEach(prefix => {
  const fileInput = document.getElementById(`${prefix}-file`);
  fileInput.addEventListener('change', () => {
    const file = fileInput.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
      const raw = e.target.result;
      if (!raw || !/<svg[\s>]/i.test(raw)) {
        customSVG[prefix] = null;
        update();
        return;
      }
      customSVG[prefix] = sanitiseSVG(raw);
      txState[prefix] = 'custom';
      syncTxIcon(prefix);
      update();
      // Refresh picker if open for this prefix
      if (pickerActivePrefix === prefix) {
        renderLibraryPicker(prefix);
      }
    };
    reader.onerror = () => { customSVG[prefix] = null; update(); };
    reader.readAsText(file);
  });

  // Click on icon box opens the picker
  document.getElementById(`${prefix}-icon`).addEventListener('click', () => {
    openTexturePicker(prefix);
  });

  // Wire colour + alpha controls
  const picker = document.getElementById(`${prefix}-color`);
  const hexIn  = document.getElementById(`${prefix}-hex`);
  const swatch = document.getElementById(`${prefix}-sw`);
  const slider = document.getElementById(`${prefix}-alpha`);
  const valEl  = document.getElementById(`${prefix}-alpha-val`);

  swatch.style.background = picker.value;
  setSliderGradient(slider, picker.value);

  picker.addEventListener('input', () => {
    hexIn.value = picker.value;
    hexIn.classList.remove('bad');
    swatch.style.background = picker.value;
    setSliderGradient(slider, picker.value);
    update();
  });
  hexIn.addEventListener('input', () => {
    const v = hexIn.value.trim();
    if (/^#[0-9A-Fa-f]{6}$/.test(v)) {
      picker.value = v;
      hexIn.classList.remove('bad');
      swatch.style.background = v;
      setSliderGradient(slider, v);
      update();
    } else {
      hexIn.classList.add('bad');
    }
  });
  slider.addEventListener('input', () => {
    valEl.textContent = slider.value + '%';
    update();
  });
});

document.getElementById('tx-picker-back').addEventListener('click', closeTexturePicker);



// Exposed for loadFromParams
window._syncTxIcons = () => { syncTxIcon('tx1'); syncTxIcon('tx2'); };

(async () => {
  await loadTextures(); // Wait for textures to load BEFORE rendering UI and parsing params
  renderPresets('duo');
  loadFromParams();
  if (window._syncTxIcons) window._syncTxIcons();
})();

document.getElementById('install-btn').addEventListener('click', () => {
  window.open(`/style.user.css?${buildStyleParams()}`, '_blank');
});

function buildStyleParams() {
  const params = {
    sq1:    document.getElementById('sq1-color').value.slice(1),
    sq1a:   document.getElementById('sq1-alpha').value,
    sq2:    document.getElementById('sq2-color').value.slice(1),
    sq2a:   document.getElementById('sq2-alpha').value,
    lm:     document.getElementById('lm-color').value.slice(1),
    lma:    document.getElementById('lm-alpha').value,
    sel:    document.getElementById('sel-color').value.slice(1),
    sela:   document.getElementById('sel-alpha').value,
    md:     document.getElementById('md-color').value.slice(1),
    mda:    document.getElementById('md-alpha').value,
    mdmode: document.getElementById('md-mode').value,
    oc:     document.getElementById('oc-color').value.slice(1),
    oca:    document.getElementById('oc-alpha').value,
    pm:     document.getElementById('pm-color').value.slice(1),
    pma:    document.getElementById('pm-alpha').value,
    tx1:    txState.tx1,
    tx1a:   document.getElementById('tx1-alpha').value,
    tx1c:   document.getElementById('tx1-color').value.slice(1),
    tx2:    txState.tx2,
    tx2a:   document.getElementById('tx2-alpha').value,
    tx2c:   document.getElementById('tx2-color').value.slice(1),
  };
  // If custom is selected but no SVG was uploaded, treat it as none
  if (params.tx1 === 'custom' && !customSVG.tx1) params.tx1 = 'none';
  if (params.tx2 === 'custom' && !customSVG.tx2) params.tx2 = 'none';
  // Embed custom SVG as base64 when active so Install and Share are self-contained
  if (params.tx1 === 'custom') {
    params.tx1svg = btoa(unescape(encodeURIComponent(customSVG.tx1)));
  }
  if (params.tx2 === 'custom') {
    params.tx2svg = btoa(unescape(encodeURIComponent(customSVG.tx2)));
  }
  return new URLSearchParams(params);
}

function loadFromParams() {
  const p = new URLSearchParams(window.location.search);
  if (!p.has('sq1')) return;
  const hex = (key, def) => {
    const v = p.get(key) || '';
    return '#' + (/^[0-9a-fA-F]{6}$/.test(v) ? v : def);
  };
  const alpha = (key, def) => {
    const v = parseInt(p.get(key));
    return isNaN(v) ? def : Math.max(0, Math.min(100, v));
  };
  const VALID_TEXTURES = ['none', 'custom', ...Object.keys(TEXTURES)];
  const texture = (key, def) => {
    const v = p.get(key) || '';
    return VALID_TEXTURES.includes(v) ? v : def;
  };
  // Restore custom SVGs from base64 params before applyPreset runs
  ['tx1', 'tx2'].forEach(prefix => {
    const b64 = p.get(`${prefix}svg`);
    if (b64) {
      try {
        const decoded = decodeURIComponent(escape(atob(b64)));
        if (/<svg[\s>]/i.test(decoded)) {
          customSVG[prefix] = decoded;
        }
      } catch (e) { /* malformed base64 — ignore */ }
    }
  });
  applyPreset({
    sq1: hex('sq1', 'edd6b0'), sq1A: alpha('sq1a', 100),
    sq2: hex('sq2', 'b88762'), sq2A: alpha('sq2a', 100),
    lm:  hex('lm',  'f6eb72'), lmA:  alpha('lma',  60),
    sel: hex('sel', '5aa7cc'), selA: alpha('sela', 60),
    md:  hex('md',  '573017'), mdA:  alpha('mda',  60),
    mdMode: ['dot', 'square'].includes(p.get('mdmode')) ? p.get('mdmode') : 'dot',
    oc:  hex('oc',  'ff7d7d'), ocA:  alpha('oca',  60),
    pm:  hex('pm',  'bb37bc'), pmA:  alpha('pma',  60),
    tx1: texture('tx1', 'none'), tx1A: alpha('tx1a', 10), tx1C: hex('tx1c', '000000'),
    tx2: texture('tx2', 'none'), tx2A: alpha('tx2a', 10), tx2C: hex('tx2c', '000000'),
  });
  syncPresetHighlight();
  if (window._syncTxIcons) window._syncTxIcons();
  history.replaceState(null, '', location.pathname);
}

document.getElementById('download-btn').addEventListener('click', () => {
  const css = document.getElementById('css-out').value;
  const blob = new Blob([css], { type: 'text/css' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = 'lichess-tailor.user.css';
  a.click();
  setTimeout(() => URL.revokeObjectURL(url), 10000);
});

const SHARE_SVG = `<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>`;

document.getElementById('share-btn').addEventListener('click', async () => {
  const btn = document.getElementById('share-btn');
  const url = `${location.origin}${location.pathname}?${buildStyleParams()}`;
  try {
    await navigator.clipboard.writeText(url);
  } catch {
    const ta = document.createElement('textarea');
    ta.value = url;
    document.body.appendChild(ta);
    ta.select();
    document.execCommand('copy');
    document.body.removeChild(ta);
  }
  btn.innerHTML = '✓ <span class="btn-label">Link copied!</span>';
  btn.classList.add('ok');
  setTimeout(() => {
    btn.innerHTML = SHARE_SVG + ' <span class="btn-label">Share</span>';
    btn.classList.remove('ok');
  }, 2000);
});

const COPY_SVG = `<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>`;

document.getElementById('copy-btn').addEventListener('click', async () => {
  const btn  = document.getElementById('copy-btn');
  const text = document.getElementById('css-out').value;
  try {
    await navigator.clipboard.writeText(text);
  } catch {
    const ta = document.getElementById('css-out');
    ta.select();
    document.execCommand('copy');
  }
  btn.innerHTML = '✓ <span class="btn-label">Copied!</span>';
  btn.classList.add('ok');
  setTimeout(() => {
    btn.innerHTML = COPY_SVG + ' <span class="btn-label">Copy</span>';
    btn.classList.remove('ok');
  }, 2000);
});

const infoBtn     = document.getElementById('info-btn');
const infoPopover = document.getElementById('info-popover');

function positionInfoPopover() {
  const rect    = infoBtn.getBoundingClientRect();
  const popW    = 290;
  const margin  = 8;
  const rawLeft = rect.left + rect.width / 2;
  const clamped = Math.max(popW / 2 + margin, Math.min(window.innerWidth - popW / 2 - margin, rawLeft));
  // Shift the arrow so it always points back at the button
  const arrowLeft = Math.max(14, Math.min(popW - 14, rawLeft - clamped + popW / 2));
  infoPopover.style.top  = (rect.bottom + 10) + 'px';
  infoPopover.style.left = clamped + 'px';
  infoPopover.style.setProperty('--arrow-left', arrowLeft + 'px');
}

infoBtn.addEventListener('click', e => {
  e.stopPropagation();
  if (infoPopover.hidden) {
    positionInfoPopover();
    infoPopover.hidden = false;
  } else {
    infoPopover.hidden = true;
  }
});
document.getElementById('info-popover-close').addEventListener('click', e => {
  e.stopPropagation();
  infoPopover.hidden = true;
});
infoPopover.addEventListener('click', e => e.stopPropagation());
document.addEventListener('click', () => { infoPopover.hidden = true; });
window.addEventListener('scroll', () => {
  if (!infoPopover.hidden) positionInfoPopover();
}, { passive: true });
window.addEventListener('resize', () => {
  if (!infoPopover.hidden) positionInfoPopover();
}, { passive: true });

update();

const chess = new Chess();
let blackMoveTimer = null;
let countdownInterval = null;
let cg;

function setTurnIndicator(color, countdownMs) {
  const dot  = document.getElementById('turn-dot');
  const text = document.getElementById('turn-text');
  clearInterval(countdownInterval);
  if (color === 'white') {
    dot.className = 'turn-dot white';
    text.textContent = 'White to move';
  } else if (color === 'black') {
    dot.className = 'turn-dot black';
    const end = Date.now() + countdownMs;
    const tick = () => {
      const secs = Math.max(0, (end - Date.now()) / 1000).toFixed(1);
      text.textContent = `Black automoves in ${secs}s`;
    };
    tick();
    countdownInterval = setInterval(tick, 100);
  } else if (color === 'white_wins') {
    dot.className = 'turn-dot white';
    text.textContent = 'White wins by checkmate (1-0)';
  }
}

let confettiInterval = null;

function launchConfetti() {
  const duration = 5 * 1000;
  const animationEnd = Date.now() + duration;
  const defaults = { startVelocity: 30, spread: 360, ticks: 60, zIndex: 0 };
  const randomInRange = (min, max) => Math.random() * (max - min) + min;

  confettiInterval = setInterval(() => {
    const timeLeft = animationEnd - Date.now();
    if (timeLeft <= 0) {
      clearInterval(confettiInterval);
      return;
    }
    const particleCount = 50 * (timeLeft / duration);
    confetti({ ...defaults, particleCount, origin: { x: randomInRange(0.1, 0.3), y: Math.random() - 0.2 } });
    confetti({ ...defaults, particleCount, origin: { x: randomInRange(0.7, 0.9), y: Math.random() - 0.2 } });
  }, 250);
}

function toDests() {
  const dests = new Map();
  chess.moves({ verbose: true }).forEach(m => {
    if (!dests.has(m.from)) dests.set(m.from, []);
    dests.get(m.from).push(m.to);
  });
  return dests;
}

// Always rebuild the full movable config so events.after is never dropped
// by a partial cg.set() merge and dests are always fresh.
function whiteMovable() {
  return {
    color: 'white',
    free: false,
    dests: toDests(),
    events: { after: onWhiteMove }
  };
}

function onWhiteMove(orig, dest) {
  const piece = chess.get(orig);
  const isPromo = piece?.type === 'p' && (dest[1] === '8' || dest[1] === '1');
  try {
    chess.move({ from: orig, to: dest, ...(isPromo ? { promotion: 'q' } : {}) });
  } catch {
    // Premove was illegal after black's response — snap back to real position
    cg.set({ fen: chess.fen(), turnColor: 'white', movable: whiteMovable() });
    setTurnIndicator('white');
    return;
  }
  if (isPromo) {
    cg.setPieces(new Map([[dest, { role: 'queen', color: 'white', promoted: true }]]));
  }
  // Flip turn to black — chessground blocks normal moves but allows white to queue a premove
  cg.set({ turnColor: 'black' });
  scheduleBlack();
}

function scheduleBlack() {
  clearTimeout(blackMoveTimer);
  if (chess.isGameOver()) {
    if (chess.isCheckmate()) {
      setTurnIndicator('white_wins');
      launchConfetti();
    }
    return;
  }
  setTurnIndicator('black', 5000);
  blackMoveTimer = setTimeout(() => {
    const moves = chess.moves({ verbose: true });
    if (!moves.length) return;
    const m = moves[Math.floor(Math.random() * moves.length)];
    chess.move(m);
    // Animate black's move — cg.move() preserves premovable.current unlike cg.set({ fen })
    cg.move(m.from, m.to);
    // En passant: captured pawn sits beside dest, not on it — cg.move() won't remove it
    if (m.flags.includes('e')) {
      cg.setPieces(new Map([[m.to[0] + m.from[1], undefined]]));
    }
    // Re-enable white — if a premove is queued it fires onWhiteMove which calls scheduleBlack
    setTurnIndicator('white');
    cg.set({
      turnColor: 'white',
      movable: chess.isGameOver() ? { color: 'none' } : whiteMovable()
    });
    // Execute the queued premove if it's still legal
    cg.playPremove();
  }, 5000);
}

cg = Chessground(document.getElementById('board-wrap'), {
  fen: chess.fen(),
  turnColor: 'white',
  movable: whiteMovable(),
  premovable: {
    enabled: true,
    showDests: true,
  },
  highlight: { lastMove: true, check: true }
});
setTurnIndicator('white');

document.getElementById('reset-btn').addEventListener('click', () => {
  clearTimeout(blackMoveTimer);
  clearInterval(countdownInterval);
  clearInterval(confettiInterval);
  confetti.reset();
  chess.reset();
  cg.set({
    fen: chess.fen(),
    turnColor: 'white',
    lastMove: undefined,
    movable: whiteMovable()
  });
  setTurnIndicator('white');
});