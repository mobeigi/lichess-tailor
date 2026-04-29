<?php require_once __DIR__ . '/includes/TextureLibrary.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Lichess Tailor</title>
  <meta name="description" content="Customise your Lichess board with custom styles using user-style browser extensions.">
  <meta name="robots" content="index, follow">
  <meta property="og:title" content="Lichess Tailor">
  <meta property="og:description" content="Customise your Lichess board with custom styles using user-style browser extensions.">
  <meta property="og:type" content="website">
  <meta property="og:image" content="/assets/images/logo512.png">
  <meta name="twitter:card" content="summary">
  <meta name="twitter:title" content="Lichess Tailor">
  <meta name="twitter:description" content="Customise your Lichess board with custom styles using user-style browser extensions.">
  <link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96">
  <link rel="icon" type="image/svg+xml" href="/favicon.svg">
  <link rel="shortcut icon" href="/favicon.ico">
  <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
  <meta name="apple-mobile-web-app-title" content="Lichess Tailor">
  <link rel="manifest" href="/site.webmanifest">
  <link rel="stylesheet" href="/assets/css/style.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@lichess-org/chessground@10.1.1/assets/chessground.base.min.css">
  
  <style id="preview-css"></style>
</head>
<body>
<div class="container">

  <header>
    <a href="/" class="header-title">
      <img src="/assets/images/logo512.png" alt="Lichess Tailor logo" class="header-logo">
      <h1>Lichess Tailor</h1>
    </a>
    <p>
      Customise your Lichess board with custom styles using user-style browser extensions.
      <span class="info-wrap">
        <button class="info-btn" id="info-btn" aria-label="How to use" data-tooltip="How to use">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/>
            <line x1="12" y1="16" x2="12" y2="12"/>
            <line x1="12" y1="8" x2="12.01" y2="8"/>
          </svg>
        </button>
        <div class="info-popover" id="info-popover" hidden>
          <button class="info-popover-close" id="info-popover-close" aria-label="Close">✕</button>
          <div class="info-popover-title">How to use</div>
          <ol>
            <li>Install a User Style extension (recommended is Stylus for <a href="https://chromewebstore.google.com/detail/stylus/clngdbkpkpeebahjckkjfobafhncgmne" target="_blank" rel="external">Chrome</a> or <a href="https://addons.mozilla.org/firefox/addon/styl-us/" target="_blank" rel="external">Firefox</a>).</li>
            <li>Customise your board colours, overlays and textures.</li>
            <li>Click <strong>Install</strong> on the Generated User Style card to apply on lichess.org.</li>
          </ol>
          <div class="info-popover-footer">See also: <a href="https://lichess.org/page/userstyles" target="_blank" rel="external">Lichess user styles guide</a></div>
        </div>
      </span>
    </p>
  </header>

  <div class="page-grid">

    <!-- Left: large preview + generated CSS -->
    <div class="left-col">

      <div class="card board-card">
        <div class="board-card-header">
          <div class="card-label" style="margin:0">Preview</div>
          <div class="turn-indicator" id="turn-indicator">
            <span class="turn-dot" id="turn-dot"></span>
            <span id="turn-text"></span>
          </div>
        </div>
        <div id="board-wrap"></div>
        <button id="reset-btn" class="reset-btn">↺ Reset</button>
      </div>

      <div class="card output-card">
        <div class="output-header">
          <div class="card-label" style="margin:0">Generated User Style</div>
          <div style="display:flex;gap:0.5rem">
            <button class="copy-btn" id="install-btn">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 3v13"/>
                <path d="M8 13l4 4 4-4"/>
                <path d="M3 19v1a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-1"/>
              </svg>
              <span class="btn-label">Install</span>
            </button>
            <button class="copy-btn" id="download-btn">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
                <path d="M12 11v6"/>
                <path d="M9 15l3 3 3-3"/>
              </svg>
              <span class="btn-label">Download</span>
            </button>
            <button class="copy-btn" id="share-btn">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
                <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
              </svg>
              <span class="btn-label">Share</span>
            </button>
            <button class="copy-btn" id="copy-btn">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="9" y="9" width="13" height="13" rx="2"/>
                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
              </svg>
              <span class="btn-label">Copy</span>
            </button>
          </div>
        </div>
        <textarea id="css-out" readonly spellcheck="false"></textarea>
      </div>

    </div>

    <!-- Right: configuration sidebar -->
    <div class="sidebar">

      <!-- Presets -->
      <div class="card">
        <div class="col-divider" style="margin-bottom:0.6rem">
          <span class="col-divider-label">Presets</span>
          <div class="col-divider-line"></div>
        </div>
        <div class="presets-grid" id="presets-grid"></div>
      </div>

      <!-- Colours -->
      <div class="card">
        <div class="col-divider" style="margin-bottom:0.6rem">
          <span class="col-divider-label">Colours</span>
          <div class="col-divider-line"></div>
        </div>
        <div class="ov-grid">

          <!-- Light squares -->
          <div class="ov-item">
            <div class="ov-head"><span class="ov-name">Light Squares</span></div>
            <div class="color-row">
              <div class="swatch" id="sq1-sw"><input type="color" id="sq1-color" value="#edd6b0"></div>
              <input type="text" class="hex-in" id="sq1-hex" value="#edd6b0" maxlength="7" spellcheck="false">
            </div>
            <div class="ov-alpha-row">
              <span class="alpha-lbl">α</span>
              <input type="range" class="alpha-slider" id="sq1-alpha" min="0" max="100" value="100">
              <span class="alpha-val" id="sq1-alpha-val">100%</span>
            </div>
          </div>

          <!-- Dark squares -->
          <div class="ov-item">
            <div class="ov-head"><span class="ov-name">Dark Squares</span></div>
            <div class="color-row">
              <div class="swatch" id="sq2-sw"><input type="color" id="sq2-color" value="#b88762"></div>
              <input type="text" class="hex-in" id="sq2-hex" value="#b88762" maxlength="7" spellcheck="false">
            </div>
            <div class="ov-alpha-row">
              <span class="alpha-lbl">α</span>
              <input type="range" class="alpha-slider" id="sq2-alpha" min="0" max="100" value="100">
              <span class="alpha-val" id="sq2-alpha-val">100%</span>
            </div>
          </div>

          <!-- Divider -->
          <div class="col-divider">
            <span class="col-divider-label">Overlays</span>
            <div class="col-divider-line"></div>
          </div>

          <!-- Last Move -->
          <div class="ov-item">
            <div class="ov-head"><span class="ov-name">Last Move</span></div>
            <div class="color-row">
              <div class="swatch" id="lm-sw"><input type="color" id="lm-color" value="#f6eb72"></div>
              <input type="text" class="hex-in" id="lm-hex" value="#f6eb72" maxlength="7" spellcheck="false">
            </div>
            <div class="ov-alpha-row">
              <span class="alpha-lbl">α</span>
              <input type="range" class="alpha-slider" id="lm-alpha" min="0" max="100" value="60">
              <span class="alpha-val" id="lm-alpha-val">60%</span>
            </div>
          </div>

          <!-- Selected -->
          <div class="ov-item">
            <div class="ov-head"><span class="ov-name">Selected</span></div>
            <div class="color-row">
              <div class="swatch" id="sel-sw"><input type="color" id="sel-color" value="#5aa7cc"></div>
              <input type="text" class="hex-in" id="sel-hex" value="#5aa7cc" maxlength="7" spellcheck="false">
            </div>
            <div class="ov-alpha-row">
              <span class="alpha-lbl">α</span>
              <input type="range" class="alpha-slider" id="sel-alpha" min="0" max="100" value="60">
              <span class="alpha-val" id="sel-alpha-val">60%</span>
            </div>
          </div>

          <!-- Move Destination -->
          <div class="ov-item">
            <div class="ov-head">
              <span class="ov-name">Move Destination</span>
              <select class="ov-mode" id="md-mode">
                <option value="dot">Dot</option>
                <option value="square">Square</option>
              </select>
            </div>
            <div class="color-row">
              <div class="swatch" id="md-sw"><input type="color" id="md-color" value="#573017"></div>
              <input type="text" class="hex-in" id="md-hex" value="#573017" maxlength="7" spellcheck="false">
            </div>
            <div class="ov-alpha-row">
              <span class="alpha-lbl">α</span>
              <input type="range" class="alpha-slider" id="md-alpha" min="0" max="100" value="60">
              <span class="alpha-val" id="md-alpha-val">60%</span>
            </div>
          </div>

          <!-- Captures (premove capture highlight) -->
          <div class="ov-item">
            <div class="ov-head"><span class="ov-name">Captures</span></div>
            <div class="color-row">
              <div class="swatch" id="oc-sw"><input type="color" id="oc-color" value="#ff7d7d"></div>
              <input type="text" class="hex-in" id="oc-hex" value="#ff7d7d" maxlength="7" spellcheck="false">
            </div>
            <div class="ov-alpha-row">
              <span class="alpha-lbl">α</span>
              <input type="range" class="alpha-slider" id="oc-alpha" min="0" max="100" value="60">
              <span class="alpha-val" id="oc-alpha-val">60%</span>
            </div>
          </div>

          <!-- Premove -->
          <div class="ov-item">
            <div class="ov-head"><span class="ov-name">Premove</span></div>
            <div class="color-row">
              <div class="swatch" id="pm-sw"><input type="color" id="pm-color" value="#bb37bc"></div>
              <input type="text" class="hex-in" id="pm-hex" value="#bb37bc" maxlength="7" spellcheck="false">
            </div>
            <div class="ov-alpha-row">
              <span class="alpha-lbl">α</span>
              <input type="range" class="alpha-slider" id="pm-alpha" min="0" max="100" value="60">
              <span class="alpha-val" id="pm-alpha-val">60%</span>
            </div>
          </div>

        </div>
      </div>

      <!-- Textures -->
      <div class="card">
        <div class="col-divider" style="margin-bottom:0.6rem">
          <span class="col-divider-label">Textures</span>
          <div class="col-divider-line"></div>
        </div>
        <div class="ov-grid">

          <!-- Light Square Texture -->
          <div class="ov-item">
            <div class="ov-head"><span class="ov-name">Light Squares</span></div>
            <div class="tx-row">
              <div class="tx-icon-wrap">
                <div class="tx-icon preset-thumb" id="tx1-icon" title="Choose texture">
                  <div class="tx-icon-inner" id="tx1-icon-inner"></div>
                </div>
                <div class="tx-icon-label" id="tx1-icon-label">None</div>
              </div>
              <div class="tx-controls">
                <div class="color-row">
                  <div class="swatch" id="tx1-sw"><input type="color" id="tx1-color" value="#000000"></div>
                  <input type="text" class="hex-in" id="tx1-hex" value="#000000" maxlength="7" spellcheck="false">
                </div>
                <div class="ov-alpha-row">
                  <span class="alpha-lbl">α</span>
                  <input type="range" class="alpha-slider" id="tx1-alpha" min="0" max="100" value="10">
                  <span class="alpha-val" id="tx1-alpha-val">10%</span>
                </div>
              </div>
            </div>
            <input type="file" id="tx1-file" accept=".svg,image/svg+xml" style="display:none">
          </div>

          <!-- Dark Square Texture -->
          <div class="ov-item">
            <div class="ov-head"><span class="ov-name">Dark Squares</span></div>
            <div class="tx-row">
              <div class="tx-icon-wrap">
                <div class="tx-icon preset-thumb" id="tx2-icon" title="Choose texture">
                  <div class="tx-icon-inner" id="tx2-icon-inner"></div>
                </div>
                <div class="tx-icon-label" id="tx2-icon-label">None</div>
              </div>
              <div class="tx-controls">
                <div class="color-row">
                  <div class="swatch" id="tx2-sw"><input type="color" id="tx2-color" value="#000000"></div>
                  <input type="text" class="hex-in" id="tx2-hex" value="#000000" maxlength="7" spellcheck="false">
                </div>
                <div class="ov-alpha-row">
                  <span class="alpha-lbl">α</span>
                  <input type="range" class="alpha-slider" id="tx2-alpha" min="0" max="100" value="10">
                  <span class="alpha-val" id="tx2-alpha-val">10%</span>
                </div>
              </div>
            </div>
            <input type="file" id="tx2-file" accept=".svg,image/svg+xml" style="display:none">
          </div>

        </div>
      </div>

      <!-- Texture library picker (overlays full sidebar when open) -->
      <div class="tx-picker" id="tx-picker" style="display:none">
        <div class="tx-picker-header">
          <button class="tx-picker-back" id="tx-picker-back" type="button">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            Back
          </button>
          <span class="tx-picker-title" id="tx-picker-title">Choose Texture</span>
        </div>
        <div class="tx-picker-body" id="tx-picker-body">
          <!-- Populated by JS -->
        </div>
      </div>

      <!-- Created by -->
      <div class="card created-card">
        <div>
          <span>Created by </span><a href="https://mobeigi.com" target="_blank" rel="external">Mo Beigi</a>
        </div>
        <a href="https://github.com/mobeigi/lichess-tailor" target="_blank" rel="external noreferrer">
          <img src="https://img.shields.io/github/stars/mobeigi/lichess-tailor?style=social" alt="GitHub Stars">
        </a>
      </div>

    </div><!-- /sidebar -->

  </div><!-- /page-grid -->
</div>

<script>
  window.LichessTailorEnv = {
    TEXTURE_LIBRARY: <?php echo json_encode(TextureLibrary::getGroupedTextures(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
    NO_TEXTURE_SVG: <?php echo json_encode(TextureLibrary::getNoTextureSvg(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>
  };
</script>
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js"></script>
<script type="module" src="/assets/js/app.min.js"></script>
</body>
</html>
