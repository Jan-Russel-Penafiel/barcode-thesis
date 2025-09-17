<?php
define('BASE_DIR', realpath(__DIR__));
function load_data() {
    $file = BASE_DIR . '/data.json';
    if (!file_exists($file)) {
        $default = ['users' => [], 'barcodes' => [], 'attendance' => []];
        file_put_contents($file, json_encode($default, JSON_PRETTY_PRINT));
        return $default;
    }
    $content = file_get_contents($file);
    $data = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON decode error in $file: " . json_last_error_msg());
        return ['users' => [], 'barcodes' => [], 'attendance' => []];
    }
    return $data;
}
function save_data($data) {
    $file = BASE_DIR . '/data.json';
    if (file_exists($file) && !is_writable($file)) {
        error_log("File $file is not writable");
        return false;
    }
    if (!file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX)) {
        error_log("Failed to write to $file");
        return false;
    }
    return true;
}
?>