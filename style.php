<?php
declare(strict_types=1);
ini_set('display_errors', '0');

header('Content-Type: text/css; charset=utf-8');
header('Cache-Control: public, max-age=86400');

function validateHex(mixed $val, string $default): string
{
    return (is_string($val) && preg_match('/^[0-9a-fA-F]{6}$/', $val))
        ? strtolower($val)
        : $default;
}

function validateAlpha(mixed $val, int $default): int
{
    return is_numeric($val) ? max(0, min(100, (int) $val)) : $default;
}

function toRgba(string $hex, int $alpha): string
{
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    $a = number_format($alpha / 100, 2, '.', '');
    return "rgba($r, $g, $b, $a)";
}

// Texture registry — complete SVG strings (viewBox="0 0 64 64") using #000000.
// The <svg> wrapper is stripped at render time and the colour substituted.
// To add a new texture: add an entry here and an <option> in style.user.css params.
const TEXTURE_PATHS = [
    'horizontal' =>
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">' .
        '<g transform="rotate(90 32 32) translate(0 0)">' .
        '<path d="M4 3a1 1 0 0 0-1 1v56a1 1 0 0 0 2 0V4a1 1 0 0 0-1-1zM12 3a1 1 0 0 0-1 1v56a1 1 0 0 0 2 0V4a1 1 0 0 0-1-1zM20 3a1 1 0 0 0-1 1v56a1 1 0 0 0 2 0V4a1 1 0 0 0-1-1zM28 3a1 1 0 0 0-1 1v56a1 1 0 0 0 2 0V4a1 1 0 0 0-1-1zM36 3a1 1 0 0 0-1 1v56a1 1 0 0 0 2 0V4a1 1 0 0 0-1-1zM44 3a1 1 0 0 0-1 1v56a1 1 0 0 0 2 0V4a1 1 0 0 0-1-1zM52 3a1 1 0 0 0-1 1v56a1 1 0 0 0 2 0V4a1 1 0 0 0-1-1zM60 3a1 1 0 0 0-1 1v56a1 1 0 0 0 2 0V4a1 1 0 0 0-1-1z" fill="#000000"/>' .
        '</g></svg>',
    'vertical' =>
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">' .
        '<g>' .
        '<path d="M4 3a1 1 0 0 0-1 1v56a1 1 0 0 0 2 0V4a1 1 0 0 0-1-1zM12 3a1 1 0 0 0-1 1v56a1 1 0 0 0 2 0V4a1 1 0 0 0-1-1zM20 3a1 1 0 0 0-1 1v56a1 1 0 0 0 2 0V4a1 1 0 0 0-1-1zM28 3a1 1 0 0 0-1 1v56a1 1 0 0 0 2 0V4a1 1 0 0 0-1-1zM36 3a1 1 0 0 0-1 1v56a1 1 0 0 0 2 0V4a1 1 0 0 0-1-1zM44 3a1 1 0 0 0-1 1v56a1 1 0 0 0 2 0V4a1 1 0 0 0-1-1zM52 3a1 1 0 0 0-1 1v56a1 1 0 0 0 2 0V4a1 1 0 0 0-1-1zM60 3a1 1 0 0 0-1 1v56a1 1 0 0 0 2 0V4a1 1 0 0 0-1-1z" fill="#000000"/>' .
        '</g></svg>',
    'diagonal' =>
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">' .
        '<g>' .
        '<path d="M4 15.51a1 1 0 0 0 .71-.29L15.22 4.71a1 1 0 1 0-1.42-1.42L3.29 13.8a1 1 0 0 0 0 1.42 1 1 0 0 0 .71.29zM4 26.89a1 1 0 0 0 .71-.29L26.6 4.71a1 1 0 1 0-1.42-1.42L3.29 25.18a1 1 0 0 0 0 1.42 1 1 0 0 0 .71.29zM4 38.25a1 1 0 0 0 .71-.25L38 4.71a1 1 0 1 0-1.42-1.42L3.29 36.54a1 1 0 0 0 0 1.42 1 1 0 0 0 .71.29zM4 49.63a1 1 0 0 0 .71-.29L49.34 4.71a1 1 0 1 0-1.42-1.42L3.29 47.92a1 1 0 0 0 0 1.42 1 1 0 0 0 .71.29zM60.71 3.29a1 1 0 0 0-1.42 0l-56 56a1 1 0 0 0 0 1.42 1 1 0 0 0 1.42 0l56-56a1 1 0 0 0 0-1.42zM59.29 14.66 14.66 59.29a1 1 0 0 0 0 1.42 1 1 0 0 0 1.42 0l44.63-44.63a1 1 0 0 0-1.42-1.42zM59.29 26 26 59.29a1 1 0 0 0 0 1.42 1 1 0 0 0 1.42 0l33.29-33.25A1 1 0 0 0 59.29 26zM59.29 37.4 37.4 59.29a1 1 0 0 0 0 1.42 1 1 0 0 0 1.42 0l21.89-21.89a1 1 0 0 0-1.42-1.42zM59.29 48.78 48.78 59.29a1 1 0 0 0 0 1.42 1 1 0 0 0 1.42 0L60.71 50.2a1 1 0 0 0-1.42-1.42z" fill="#000000"/>' .
        '</g></svg>',
    'waves' =>
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">' .
        '<g fill="none" stroke="#000000" stroke-width="2">' .
        '<path d="M4 5c1.73 0 2.59 1.06 3.85 2.8s2.64 3.62 5.48 3.62S17.57 9.5 18.8 7.8 20.92 5 22.66 5s2.6 1.06 3.86 2.8 2.63 3.62 5.47 3.62 4.24-1.92 5.48-3.62S39.59 5 41.32 5s2.6 1.06 3.87 2.8 2.63 3.62 5.47 3.62 4.25-1.92 5.48-3.62S58.26 5 60 5"/>' .
        '<path d="M4 17.39c1.73 0 2.59 1.07 3.85 2.8s2.64 3.63 5.48 3.63 4.24-1.93 5.47-3.63 2.12-2.8 3.86-2.8 2.6 1.07 3.86 2.8 2.63 3.63 5.47 3.63 4.24-1.93 5.48-3.63 2.12-2.8 3.85-2.8 2.6 1.07 3.87 2.8 2.63 3.63 5.47 3.63 4.25-1.93 5.48-3.63 2.12-2.8 3.86-2.8"/>' .
        '<path d="M4 29.79c1.73 0 2.59 1.06 3.85 2.8s2.64 3.62 5.48 3.62 4.24-1.92 5.47-3.62 2.12-2.8 3.86-2.8 2.6 1.06 3.86 2.8 2.63 3.62 5.47 3.62 4.24-1.92 5.48-3.62 2.12-2.8 3.85-2.8 2.6 1.06 3.87 2.8 2.63 3.62 5.47 3.62 4.25-1.92 5.48-3.62 2.12-2.8 3.86-2.8"/>' .
        '<path d="M4 42.18c1.73 0 2.59 1.07 3.85 2.8s2.64 3.63 5.48 3.63 4.24-1.93 5.47-3.63 2.12-2.8 3.86-2.8 2.6 1.07 3.86 2.8 2.63 3.63 5.47 3.63 4.24-1.93 5.48-3.63 2.12-2.8 3.85-2.8 2.6 1.07 3.87 2.8 2.63 3.63 5.47 3.63 4.25-1.93 5.48-3.63 2.12-2.8 3.86-2.8"/>' .
        '<path d="M4 54.58c1.73 0 2.59 1.06 3.85 2.8s2.64 3.62 5.48 3.62 4.24-1.92 5.47-3.62 2.12-2.8 3.86-2.8 2.6 1.06 3.86 2.8 2.63 3.62 5.47 3.62 4.24-1.92 5.48-3.62 2.12-2.8 3.85-2.8 2.6 1.06 3.87 2.8 2.63 3.62 5.47 3.62 4.25-1.92 5.48-3.62 2.12-2.8 3.86-2.8"/>' .
        '</g></svg>',
];

function makeTexturePattern(string $patId, string $txId, int $alphaPercent, string $colour, string $customSvg = ''): string
{
    // Resolve SVG source: named registry or custom upload
    if ($txId === 'custom') {
        if ($customSvg === '' || stripos($customSvg, '<svg') === false) return '';
        $svgSrc = $customSvg;
    } elseif (isset(TEXTURE_PATHS[$txId])) {
        $svgSrc = TEXTURE_PATHS[$txId];
    } else {
        return '';
    }

    $opacity = number_format($alphaPercent / 100, 2, '.', '');

    // Determine viewBox width for scaling (fallback to 64 if not found).
    // viewBox="minX minY width height" — width is the 3rd token (index 2).
    $viewBoxSize = 64;
    if (preg_match('/viewBox=["\']([^"\']*)["\']/', $svgSrc, $vb)) {
        $parts = preg_split('/[\s,]+/', trim($vb[1]));
        if (count($parts) === 4 && (float)$parts[2] > 0) {
            $viewBoxSize = (float)$parts[2];
        }
    }
    $scale = number_format(1 / $viewBoxSize, 10, '.', '');

    // Strip the outer <svg> wrapper, then remove all inline fill/stroke colour
    // attributes from child elements (preserving fill="none" which is structural).
    // The wrapper <g> sets fill and stroke to the chosen colour so every element
    // inherits it, making the colour picker work for any SVG.
    $raw   = preg_replace('/<svg[^>]*>/', '', $svgSrc);
    $raw   = preg_replace('/<\/svg>\s*$/', '', $raw);
    $raw   = preg_replace('/\s+fill="(?!none")[^"]*"/', '', $raw);
    $raw   = preg_replace("/\\s+fill='(?!none')[^']*'/", '', $raw);
    $raw   = preg_replace('/\s+stroke="(?!none")[^"]*"/', '', $raw);
    $inner = preg_replace("/\\s+stroke='(?!none')[^']*'/", '', $raw);

    return implode("\n", [
        "<pattern id=\"{$patId}\" x=\"0\" y=\"0\" width=\"1\" height=\"1\" patternUnits=\"userSpaceOnUse\">",
        "  <g fill=\"#{$colour}\" stroke=\"#{$colour}\" opacity=\"{$opacity}\" transform=\"scale({$scale})\">",
        "    {$inner}",
        "  </g>",
        "</pattern>",
    ]);
}

// Parse and validate inputs — defaults match the Walnut preset
$sq1    = validateHex  ($_GET['sq1']    ?? 'edd6b0', 'edd6b0');
$sq1a   = validateAlpha($_GET['sq1a']   ?? 100,       100);
$sq2    = validateHex  ($_GET['sq2']    ?? 'b88762', 'b88762');
$sq2a   = validateAlpha($_GET['sq2a']   ?? 100,       100);
$lm     = validateHex  ($_GET['lm']     ?? 'f6eb72', 'f6eb72');
$lma    = validateAlpha($_GET['lma']    ?? 60,        60);
$sel    = validateHex  ($_GET['sel']    ?? '5aa7cc', '5aa7cc');
$sela   = validateAlpha($_GET['sela']   ?? 60,        60);
$md     = validateHex  ($_GET['md']     ?? '573017', '573017');
$mda    = validateAlpha($_GET['mda']    ?? 60,        60);
$mdmode = in_array($_GET['mdmode'] ?? '', ['dot', 'square'], true)
    ? $_GET['mdmode']
    : 'dot';
$oc     = validateHex  ($_GET['oc']     ?? 'ff7d7d', 'ff7d7d');
$oca    = validateAlpha($_GET['oca']    ?? 60,        60);
$pm     = validateHex  ($_GET['pm']     ?? 'bb37bc', 'bb37bc');
$pma    = validateAlpha($_GET['pma']    ?? 60,        60);

$validTextures = array_merge(['none', 'custom'], array_keys(TEXTURE_PATHS));
$tx1    = in_array($_GET['tx1'] ?? '', $validTextures, true) ? $_GET['tx1'] : 'none';
$tx1a   = validateAlpha($_GET['tx1a']   ?? 10,         10);
$tx1c   = validateHex  ($_GET['tx1c']   ?? '000000', '000000');
$tx2    = in_array($_GET['tx2'] ?? '', $validTextures, true) ? $_GET['tx2'] : 'none';
$tx2a   = validateAlpha($_GET['tx2a']   ?? 10,         10);
$tx2c   = validateHex  ($_GET['tx2c']   ?? '000000', '000000');

// Decode custom SVG uploads (base64-encoded, UTF-8 content)
function decodeCustomSvg(mixed $val): string
{
    if (!is_string($val) || $val === '') return '';
    $decoded = base64_decode($val, true);
    if ($decoded === false) return '';
    // Basic sanity check — must contain an <svg element
    return (stripos($decoded, '<svg') !== false) ? $decoded : '';
}
$tx1svg = decodeCustomSvg($_GET['tx1svg'] ?? '');
$tx2svg = decodeCustomSvg($_GET['tx2svg'] ?? '');

// Convert to rgba strings
$sq1rgba = toRgba($sq1, $sq1a);
$sq2rgba = toRgba($sq2, $sq2a);
$lmRgba  = toRgba($lm,  $lma);
$selRgba = toRgba($sel,  $sela);
$mdRgba  = toRgba($md,   $mda);
$ocRgba  = toRgba($oc,   $oca);
$pmRgba  = toRgba($pm,   $pma);

// Build texture patterns
$pat1 = makeTexturePattern('tp1', $tx1, $tx1a, $tx1c, $tx1svg);
$pat2 = makeTexturePattern('tp2', $tx2, $tx2a, $tx2c, $tx2svg);
$patParts = array_filter([$pat1, $pat2], fn($p) => $p !== '');
$defs = !empty($patParts)
    ? "<defs>\n" . implode("\n", $patParts) . "\n</defs>"
    : '';

// Build square fill lines.
// When a texture is active wrap both rects in a <g id="e/f"> so the <use>
// elements clone the whole group (colour + texture).
$sq1Fill = $pat1 !== ''
    ? "        <g id=\"e\">\n          <rect width=\"1\" height=\"1\" fill=\"{$sq1rgba}\"/>\n          <rect width=\"1\" height=\"1\" fill=\"url(#tp1)\"/>\n        </g>"
    : "        <rect width=\"1\" height=\"1\" id=\"e\" fill=\"{$sq1rgba}\"/>";
$sq2Fill = $pat2 !== ''
    ? "        <g id=\"f\">\n          <rect y=\"1\" width=\"1\" height=\"1\" fill=\"{$sq2rgba}\"/>\n          <rect y=\"1\" width=\"1\" height=\"1\" fill=\"url(#tp2)\"/>\n        </g>"
    : "        <rect y=\"1\" width=\"1\" height=\"1\" id=\"f\" fill=\"{$sq2rgba}\"/>";

// Build 8×8 checkerboard SVG board pattern
$svgParts = [
    '<?xml version="1.0" encoding="UTF-8" standalone="no"?>',
    '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="http://www.w3.org/1999/xlink"',
    '     viewBox="0 0 8 8" shape-rendering="crispEdges">',
];
if ($defs !== '') $svgParts[] = $defs;
$svgParts = array_merge($svgParts, [
    '<g id="a">',
    '  <g id="b">',
    '    <g id="c">',
    '      <g id="d">',
    $sq1Fill,
    '        <use x="1" y="1" href="#e" x:href="#e"/>',
    $sq2Fill,
    '        <use x="1" y="-1" href="#f" x:href="#f"/>',
    '      </g>',
    '      <use x="2" href="#d" x:href="#d"/>',
    '    </g>',
    '    <use x="4" href="#c" x:href="#c"/>',
    '  </g>',
    '  <use y="2" href="#b" x:href="#b"/>',
    '</g>',
    '<use y="4" href="#a" x:href="#a"/>',
    '</svg>',
]);

$svg = implode("\n", $svgParts);
$b64 = base64_encode($svg);

// Move destination style
$mdCss = $mdmode === 'dot'
    ? "background: radial-gradient({$mdRgba} 19%, rgba(0, 0, 0, 0) 20%) !important;"
    : "background: unset !important;\n      background-color: {$mdRgba} !important;";

echo <<<CSS
/* ==UserStyle==
@name           Lichess Tailor style for lichess.org
@namespace      github.com/mobeigi/lichess-tailor
@version        1.0.0
@description    Custom board colours and overlay highlights for lichess.org, generated by Lichess Tailor.
@author         Mo Beigi
==/UserStyle== */

@-moz-document domain("lichess.org") {
    cg-board::before {
      background-color: {$sq1rgba} !important;
      background-image: url('data:image/svg+xml;base64,{$b64}') !important;
      filter: unset !important;
    }

    cg-wrap coords:nth-child(odd) coord:nth-child(odd),
    cg-wrap.orientation-black coords:nth-child(odd) coord:nth-child(even),
    cg-wrap coords.files:nth-child(even) coord:nth-child(odd),
    cg-wrap.orientation-black coords.files:nth-child(even) coord:nth-child(even) {
      color: {$sq1rgba} !important;
    }

    cg-wrap coords:nth-child(odd) coord:nth-child(even),
    cg-wrap.orientation-black coords:nth-child(odd) coord:nth-child(odd),
    cg-wrap coords.files:nth-child(even) coord:nth-child(even),
    cg-wrap.orientation-black coords.files:nth-child(even) coord:nth-child(odd) {
      color: {$sq2rgba} !important;
    }

    cg-board square.last-move {
      background-color: {$lmRgba} !important;
    }

    cg-board square.selected {
      background-color: {$selRgba} !important;
    }

    cg-board square.move-dest, cg-board square.premove-dest {
      {$mdCss}
    }

    cg-board square.oc {
      background: unset !important;
      background-color: {$ocRgba} !important;
    }

    cg-board square.current-premove, cg-board square.last-premove {
      background-color: {$pmRgba} !important;
    }
}
CSS;
