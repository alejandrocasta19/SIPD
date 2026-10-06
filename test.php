<?php
$zip = new ZipArchive;
if ($zip->open('documentos/1.1 GA-FT-045 Formato de apertura Proceso Disciplinarios.docx') === true) {
    file_put_contents('docx_text.txt', strip_tags($zip->getFromName('word/document.xml')));
} else {
    echo "Failed to open zip\n";
}
