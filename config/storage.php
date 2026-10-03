
<?php

define(
    'DOCUMENT_STORAGE_PATH',
    dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage'
);

if (!is_dir(DOCUMENT_STORAGE_PATH)) {
    if (!mkdir(DOCUMENT_STORAGE_PATH, 0750, true) && !is_dir(DOCUMENT_STORAGE_PATH)) {
        throw new RuntimeException('Unable to create document storage directory.');
    }
}