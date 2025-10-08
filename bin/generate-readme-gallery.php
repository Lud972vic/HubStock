#!/usr/bin/env php
<?php
// Génère un tableau HTML pour le README à partir des images dans public/readme

$root = dirname(__DIR__);
$dir = $root . '/public/readme';

if (!is_dir($dir)) {
    fwrite(STDERR, "Dossier introuvable: $dir\n");
    exit(1);
}

$files = array_values(array_filter(scandir($dir), function ($f) use ($dir) {
    if ($f === '.' || $f === '..') return false;
    $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
    $allowed = ['png','jpg','jpeg','gif','webp'];
    return in_array($ext, $allowed) && is_file($dir . '/' . $f);
}));

// Trier par nom croissant
sort($files, SORT_NATURAL | SORT_FLAG_CASE);

if (empty($files)) {
    echo "<!-- Aucun fichier image dans public/readme -->\n";
    exit(0);
}

// Construction du tableau (2 images par ligne)
echo "<table>\n";
$perRow = 2;
for ($i = 0; $i < count($files); $i += $perRow) {
    $remaining = count($files) - $i;
    echo "  <tr>\n";
    if ($remaining === 1) {
        $basename = $files[$i];
        $encoded = rawurlencode($basename);
        $src = "public/readme/" . $encoded;
        $alt = htmlspecialchars($basename, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        echo "    <td colspan=\"2\"><img src=\"$src\" width=\"1920\" alt=\"$alt\"></td>\n";
    } else {
        for ($j = 0; $j < $perRow; $j++) {
            $idx = $i + $j;
            $basename = $files[$idx];
            $encoded = rawurlencode($basename);
            $src = "public/readme/" . $encoded;
            $alt = htmlspecialchars($basename, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            echo "    <td><img src=\"$src\" width=\"1920\" alt=\"$alt\"></td>\n";
        }
    }
    echo "  </tr>\n";
}
echo "</table>\n";