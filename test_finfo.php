<?php
$f = fopen("test.pdf", "w");
fwrite($f, "dummy");
fclose($f);

$finfo = new finfo(FILEINFO_MIME_TYPE);
echo "test.pdf (dummy text): " . $finfo->file("test.pdf") . "\n";

$f = fopen("empty.pdf", "w");
fclose($f);
echo "empty.pdf (0 bytes): " . $finfo->file("empty.pdf") . "\n";

unlink("test.pdf");
unlink("empty.pdf");
