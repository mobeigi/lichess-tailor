<?php
require_once __DIR__ . '/../includes/TextureLibrary.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=86400'); // Cache for 1 day

$response = [
    'TEXTURE_LIBRARY' => TextureLibrary::getGroupedTextures(),
    'NO_TEXTURE_SVG' => TextureLibrary::getNoTextureSvg()
];

echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);