<p align="center">
<img src="https://i.imgur.com/tmZe114.png" height="110px" width="auto"/>
<br/>
<h3 align="center">Lichess Tailor</h3>
<p align="center">Customise your Lichess board with custom user styles</p>
<h2></h2>
<br />

<p align="center">
<a href="../../releases"><img src="https://img.shields.io/github/release/mobeigi/lichess-tailor.svg?style=flat-square" /></a>
<a href="../../actions"><img src="https://img.shields.io/github/actions/workflow/status/mobeigi/lichess-tailor/ci.yml?style=flat-square" /></a>
<a href="../../issues"><img src="https://img.shields.io/github/issues/mobeigi/lichess-tailor.svg?style=flat-square" /></a>
<a href="../../pulls"><img src="https://img.shields.io/github/issues-pr/mobeigi/lichess-tailor.svg?style=flat-square" /></a>
<a href="LICENSE.md"><img src="https://img.shields.io/github/license/mobeigi/lichess-tailor.svg?style=flat-square" /></a>
</p>

## Description

Lichess Tailor is a web tool for generating custom user styles for [lichess.org](https://lichess.org). Customise board colours, overlay highlights, and more with a live interactive preview. Export your style directly to Stylus, download it, or share it with others via a URL.

## Requirements

**Self-hosting:**
- Docker
- nginx
- PHP 8.0+

**Using generated styles:**
- A user-style browser extension such as [Stylus](https://github.com/openstyles/stylus) ([Chrome](https://chromewebstore.google.com/detail/stylus/clngdbkpkpeebahjckkjfobafhncgmne) / [Firefox](https://addons.mozilla.org/firefox/addon/styl-us/))

## Instructions

1. Clone the repository
   ```bash
   git clone https://github.com/mobeigi/lichess-tailor.git
   ```
2. Start the Docker containers
   ```bash
   docker compose up -d
   ```
3. Visit the site, choose a preset or customise colours and overlays.

## Textures

Lichess Tailor supports applying SVG textures to light and dark squares independently. Only `.svg` files are accepted; raster image formats (PNG, JPG, etc.) are not supported.

### Sourcing textures

SVG textures can be found across many free and paid resources online. [Flaticon](https://www.flaticon.com) is a recommended starting point with a large library of SVG icons and patterns available for download.

### Converting images to SVG

If you have a raster image (PNG, JPG, etc.) that you want to use as a texture, it can be converted to SVG using an online tool such as [PicSVG](https://picsvg.com). Note that conversions from raster images are approximate; simple high-contrast images tend to convert best.

### Optimising SVGs

SVG files sourced from the web often contain unnecessary metadata, editor comments, and redundant attributes that inflate their size. It is highly recommended to optimise your SVG before uploading to keep performance snappy and share/install URLs manageable. Good tools for this include:

- [SVGOMG](https://svgomg.net): a feature-rich optimiser with fine-grained control
- [SVG Viewer](https://www.svgviewer.dev): lets you view, edit, and optimise SVGs in the browser

## Contributions

Contributions are always welcome!  
Just make a [pull request](../../pulls).

## Licence

### Lichess Tailor

Lichess Tailor is licensed under the [GNU General Public License v3.0 (GPL-3.0)](LICENSE.md).

### Lichess Chessground

This project utilizes [Lichess Chessground](https://github.com/lichess-org/chessground) for rendering the chessboard. Chessground is licensed under the [MIT License](https://opensource.org/licenses/MIT).
