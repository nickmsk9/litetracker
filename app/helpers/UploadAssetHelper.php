<?php

if (!function_exists('lt_upload_asset_ensure_directory')) {
    function lt_upload_asset_ensure_directory($path)
    {
        if (is_dir($path)) {
            return true;
        }

        $created = mkdir($path, 0777, true);
        return ($created || is_dir($path));
    }
}

if (!function_exists('lt_upload_asset_image_extension')) {
    function lt_upload_asset_image_extension($filename, $normalizeJpeg = true)
    {
        $extension = strtolower((string) pathinfo((string) $filename, PATHINFO_EXTENSION));
        $allowed = array('jpg', 'jpeg', 'png', 'gif');

        if (!in_array($extension, $allowed, true)) {
            return '';
        }

        return ($normalizeJpeg && $extension === 'jpeg' ? 'jpg' : $extension);
    }
}

if (!function_exists('lt_upload_asset_validate_image')) {
    function lt_upload_asset_validate_image($file, $label, $maxSize, $checkUploadError = false)
    {
        global $language;

        $name = (string) ($file['name'] ?? '');
        $tmp = (string) ($file['tmp_name'] ?? '');
        $size = (int) ($file['size'] ?? 0);
        $error = (int) ($file['error'] ?? UPLOAD_ERR_OK);

        if (($checkUploadError && $error !== UPLOAD_ERR_OK) || $name === '' || $tmp === '' || !is_uploaded_file($tmp)) {
            err($language['default_1'], $label.' не был загружен.', 1);
        }

        $extension = lt_upload_asset_image_extension($name, true);
        if ($extension === '') {
            err($language['default_1'], $label.' должен быть в формате JPG, PNG или GIF.', 1);
        }

        if ($size <= 0 || $size > (int) $maxSize) {
            err($language['default_1'], $label.' превышает допустимый размер '.mksize((int) $maxSize).'.', 1);
        }

        $imageInfo = getimagesize($tmp);
        if (!$imageInfo || empty($imageInfo[2]) || !in_array((int) $imageInfo[2], array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG), true)) {
            err($language['default_1'], $label.' не похож на изображение.', 1);
        }

        return $extension;
    }
}

if (!function_exists('lt_upload_asset_move_uploaded_image')) {
    function lt_upload_asset_move_uploaded_image($file, $directory, $targetName, $label)
    {
        global $language;

        if (!lt_upload_asset_ensure_directory($directory)) {
            err($language['default_1'], 'Не удалось подготовить каталог для загрузки файлов.', 1);
        }

        if (!move_uploaded_file((string) ($file['tmp_name'] ?? ''), $directory.$targetName)) {
            err($language['default_1'], 'Не удалось сохранить '.$label.'.', 1);
        }

        return $targetName;
    }
}
