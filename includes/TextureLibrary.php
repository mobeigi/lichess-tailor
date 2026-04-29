<?php
declare(strict_types=1);

class TextureLibrary
{
    private static string $libDir = __DIR__ . '/../assets/images/textures/library/';
    private static string $noTexFile = __DIR__ . '/../assets/images/textures/no-texture.svg';

    /**
     * Returns grouped textures for the frontend library picker.
     * Structure: { groupKey: { label: string, textures: { name: svgString } } }
     */
    public static function getGroupedTextures(): array
    {
        $groupMeta = [
            'basic-patterns'   => 'Basic Patterns',
            'complex-patterns' => 'Complex Patterns',
            'players'          => 'Players',
        ];
        
        $library = [];
        foreach ($groupMeta as $groupKey => $groupLabel) {
            $groupDir = self::$libDir . $groupKey . '/';
            if (!is_dir($groupDir)) continue;
            
            $textures = [];
            $files = glob($groupDir . '*.svg');
            if ($files) {
                sort($files);
                foreach ($files as $file) {
                    $name = basename($file, '.svg');
                    $textures[$name] = file_get_contents($file);
                }
            }
            if (!empty($textures)) {
                $library[$groupKey] = ['label' => $groupLabel, 'textures' => $textures];
            }
        }
        
        return $library;
    }

    /**
     * Returns a flat map of all textures.
     * Structure: { name: svgString }
     */
    public static function getFlatTextures(): array
    {
        $texturePaths = [];
        $groupDirs = glob(self::$libDir . '*', GLOB_ONLYDIR);
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
        return $texturePaths;
    }

    /**
     * Returns the raw SVG for the "None" texture, if readable.
     */
    public static function getNoTextureSvg(): string
    {
        return is_readable(self::$noTexFile) ? file_get_contents(self::$noTexFile) : '';
    }
}
