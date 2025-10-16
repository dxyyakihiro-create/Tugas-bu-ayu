<?php
 $target_dir = "uploads";

if (!file_exists($target_dir)) {
    if (mkdir($target_dir, 0777, true)) {
        echo "Folder '$target_dir' berhasil dibuat<br>";
    } else {
        echo "Gagal membuat folder '$target_dir'<br>";
    }
} else {
    echo "Folder '$target_dir' sudah ada<br>";
}


if (is_writable($target_dir)) {
    echo "Folder writable: Yes<br>";
} else {
    echo "Folder writable: No<br>";
    if (chmod($target_dir, 0777)) {
        echo "Permission diubah ke 777<br>";
    } else {
        echo "Gagal mengubah permission<br>";
    }
}

echo "<br>Struktur folder:<br>";
 $files = scandir('.');
foreach ($files as $file) {
    if (is_dir($file) && $file != '.' && $file != '..') {
        echo "- $file/<br>";
    }
}
?>