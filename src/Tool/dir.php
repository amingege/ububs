<?php
/**
 * 递归获取目录下的文件
 * @param $dir 目录
 * @param string $filter 正则
 * @param bool $deep 是否递归
 * @param array $result 结果集引用
 * @return array
 */
function dir_tree($dir, $filter = '', $deep = true, $result = [])
{
    // static $result;
    $files = new \DirectoryIterator($dir);
    foreach ($files as $file) {
        if ($file->isDot()) {
            continue;
        }
        $filename = $file->getFilename();
        if ($file->isDir() && $deep) {
            $result = dir_tree($dir . DS . $filename, $filter, $deep, $result);
        } else {
            if ($filter !== '' && !\preg_match($filter, $filename)) {
                continue;
            }
            $result[$dir][] = $filename;
        }
    }
    return $result;
}

/**
 * 递归创建目录
 * @param $dir 目录
 * @param int $mode 权限
 * @return bool
 */
function dir_make($dir, $mode = 0755)
{
    if (is_dir($dir) || mkdir($dir, $mode, true)) {
        return true;
    }
    if (!dir_make(dirname($dir), $mode)) {
        return false;
    }
    return mkdir($dir, $mode);
}

/**
 * 递归删除目录
 * @param $dir 目录
 * @param $filter 正则
 * @return bool
 */
function dir_del($dir, $filter = '')
{
    $files = new \DirectoryIterator($dir);
    foreach ($files as $file) {
        if ($file->isDot()) {
            continue;
        }
        $filename = $file->getFilename();
        if (!empty($filter) && !\preg_match($filter, $filename)) {
            continue;
        }
        if ($file->isDir()) {
            dir_del($dir . DS . $filename);
        } else {
            \unlink($dir . DS . $filename);
        }
    }
    return \rmdir($dir);
}

/**
 * 检查一个目录是否可写，包括目录下的文件
 * @param  string  $dir  目录路劲
 * @param  boolean $deep 是否递归
 * @return bool
 */
function dir_check_write_permission($dir, $deep = false)
{
    $dir    = str_replace('\\', '/', $dir);
    $result = true;
    if (!is_dir($dir)) {
        $result = false;
        return $result;
    }
    $file_hd = @fopen($dir . '/test.txt', 'w');
    if (!$file_hd) {
        @fclose($file_hd);
        @unlink($dir . '/test.txt');
        $result = false;
        return $result;
    }
    $dir_hd = opendir($dir);
    while (false !== ($file = readdir($dir_hd))) {
        if ($file != "." && $file != "..") {
            if (is_file($dir . '/' . $file)) {
                //文件不可写，直接返回
                if (!is_writable($dir . '/' . $file)) {
                    $result = false;
                    return $result;
                }
            } else {
                if (!$file_hd2 = @fopen($dir . '/' . $file . '/test.txt', 'w')) {
                    @fclose($file_hd2);
                    @unlink($dir . '/' . $file . '/test.txt');
                    $result = false;
                    return $result;
                }
                //递归
                if ($deep) {
                    $result = check_dir_iswritable($dir . '/' . $file);
                }
            }
        }
    }
    return $result;
}
