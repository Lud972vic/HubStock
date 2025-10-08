#!/usr/bin/env php
<?php
// Renomme les images de public/readme en noms sûrs (sans accents/espaces)

$root = dirname(__DIR__);
$dir = $root . '/public/readme';

if (!is_dir($dir)) {
    fwrite(STDERR, "Dossier introuvable: $dir\n");
    exit(1);
}

$allowed = ['png','jpg','jpeg','gif','webp'];

function sanitizeName(string $filename): string {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $name = pathinfo($filename, PATHINFO_FILENAME);

    // Tenter d’extraire date/heure: YYYY-MM-DD .. HH.MM.SS
    if (preg_match('/(\d{4})-(\d{2})-(\d{2}).*?(\d{2})\.(\d{2})\.(\d{2})/u', $filename, $m)) {
        return sprintf('capture-%s-%s-%s-%s-%s-%s.%s', $m[1], $m[2], $m[3], $m[4], $m[5], $m[6], $ext);
    }

    // Sinon, translittération et slugify simple
    $trans = iconv('UTF-8', 'ASCII//TRANSLIT', $name);
    if ($trans === false) { $trans = $name; }
    $trans = strtolower($trans);
    // Remplacer tout ce qui n’est pas alphanumérique par des tirets
    $slug = preg_replace('/[^a-z0-9]+/', '-', $trans);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');
    if ($slug === '') { $slug = 'image'; }
    return $slug . '.' . $ext;
}

$files = array_values(array_filter(scandir($dir), function ($f) use ($dir, $allowed) {
    if ($f === '.' || $f === '..') return false;
    $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
    return in_array($ext, $allowed) && is_file($dir . '/' . $f);
}));

if (empty($files)) {
    fwrite(STDOUT, "Aucune image à renommer dans $dir\n");
    exit(0);
}

$renamed = 0;
foreach ($files as $f) {
    $src = $dir . '/' . $f;
    $new = sanitizeName($f);
    $dst = $dir . '/' . $new;
    if ($src === $dst) {
        fwrite(STDOUT, "Déjà correct: $f\n");
        continue;
    }
    $i = 1;
    $base = pathinfo($dst, PATHINFO_FILENAME);
    $ext = pathinfo($dst, PATHINFO_EXTENSION);
    while (file_exists($dst)) {
        $dst = sprintf('%s/%s-%d.%s', $dir, $base, $i++, $ext);
    }
    if (!rename($src, $dst)) {
        fwrite(STDERR, "Échec renommage: $f -> " . basename($dst) . "\n");
        continue;
    }
    fwrite(STDOUT, "Renommé: $f -> " . basename($dst) . "\n");
    $renamed++;
}

fwrite(STDOUT, "Terminé. $renamed fichier(s) renommé(s).\n");