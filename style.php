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

// Texture registry — loaded from /images/textures/library/<group>/*.svg
// To add a texture: drop the SVG in the right subfolder — no code changes needed.
$libDir = __DIR__ . '/images/textures/library/';
$texturePaths = [];
$groupDirs = glob($libDir . '*', GLOB_ONLYDIR);
if ($groupDirs) {
    sort($groupDirs);
    foreach ($groupDirs as $groupDir) {
        $files = glob($groupDir . '/*.svg');
        if ($files) {
            sort($files);
            foreach ($files as $file) {
                $name = basename($file, '.svg');
                $texturePaths[$name] = file_get_contents($file);
            }
        }
    }
}

function makeTexturePattern(string $patId, string $txId, int $alphaPercent, string $colour, string $customSvg, array $texturePaths): string
{
    // Resolve SVG source: named registry or custom upload
    if ($txId === 'custom') {
        if ($customSvg === '' || stripos($customSvg, '<svg') === false) return '';
        $svgSrc = $customSvg;
    } elseif (isset($texturePaths[$txId])) {
        $svgSrc = $texturePaths[$txId];
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

    // Strip the outer <svg> wrapper, then replace all explicit black colour
    // values (#000000, #000, black) with the chosen colour. The wrapper <g>
    // also sets fill and stroke directly so paths with no explicit colour
    // (which inherit SVG's default black) are covered too.
    // fill="none" and stroke="none" are left untouched as they are structural.
    $raw   = preg_replace('/<svg[^>]*>/', '', $svgSrc);
    $raw   = preg_replace('/<\/svg>\s*$/', '', $raw);
    $raw   = str_replace('#000000', "#{$colour}", $raw);
    $raw   = preg_replace('/#000(?![0-9a-fA-F])/', "#{$colour}", $raw);
    $inner = preg_replace('/(["\'])black\1/', "$1#{$colour}$1", $raw);

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

$validTextures = array_merge(['none', 'custom'], array_keys($texturePaths));
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
$pat1 = makeTexturePattern('tp1', $tx1, $tx1a, $tx1c, $tx1svg, $texturePaths);
$pat2 = makeTexturePattern('tp2', $tx2, $tx2a, $tx2c, $tx2svg, $texturePaths);
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
