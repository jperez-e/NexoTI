<?php
$dir = __DIR__ . '/Views';
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'css') {
        $path = $file->getPathname();
        $content = file_get_contents($path);
        
        $search = ".layout { \r\n    display: flex; \r\n    flex-direction: column;";
        $pos = strpos($content, $search);
        
        if ($pos !== false) {
            $newContent = trim(substr($content, 0, $pos));
            file_put_contents($path, $newContent);
            echo "Fixed: $path\n";
        }
    }
}
echo "Done.\n";
