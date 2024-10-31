<?php

namespace pdflio\lib\Filesystem;

use FilesystemIterator;

class Filesystem
{
    public function read($path)
    {
        if (! $this->exists($path)) {
            throw new FilesystemException("File not found: {$path}");
        } elseif (! $this->isFile($path)) {
            throw new FilesystemException("Not a file: {$path}");
        } elseif (! $this->isReadable($path)) {
            throw new FilesystemException("Cannot read file: {$path}");
        }

        return file_get_contents($this->normalize($path));
    }

    public function readLineByLine($path, callable $callback)
    {
        if (! $this->exists($path)) {
            throw new FilesystemException("File not found: {$path}");
        } elseif (! $this->isFile($path)) {
            throw new FilesystemException("Not a file: {$path}");
        } elseif (! $this->isReadable($path)) {
            throw new FilesystemException("Cannot read file: {$path}");
        }

        $pointer = fopen($path, 'r');

        while (! feof($pointer)) {
            $callback(fgets($pointer));
        }

        fclose($pointer);
    }

    public function write($path, $data, $overwrite = false, $append = false)
    {
        $path = $this->normalize($path);

        if ($this->isDir($path)) {
            throw new FilesystemException("Cannot write file, path is a directory: {$path}");
        } elseif ($this->isFile($path) && $overwrite == false && $append == false) {
            throw new FilesystemException("File already exists: {$path}");
        }

        $flags = LOCK_EX;
        if ($overwrite == false && $append == true) {
            $flags = FILE_APPEND | LOCK_EX;
        }

        file_put_contents($path, $data, $flags);

        $this->ensureCorrectAccessMode($path);
    }

    public function append($path, $data)
    {
        $this->write($path, $data, false, true);
    }

    public function mkDir($path, $with_index = true)
    {
        $path = $this->normalize($path);

        $old_umask = umask(0);
        $result = @mkdir($path, DIR_WRITE_MODE, true);
        umask($old_umask);

        if (! $result) {
            return false;
        }

        if ($with_index) {
            $this->addIndexHtml($path);
        }

        $this->ensureCorrectAccessMode($path);

        return true;
    }

    public function delete($path)
    {
        if ($this->isDir($path)) {
            return $this->deleteDir($path);
        }

        return $this->deleteFile($path);
    }

    public function deleteFile($path)
    {
        if (! $this->isFile($path)) {
            throw new FilesystemException("File does not exist {$path}");
        }

        return @unlink($this->normalize($path));
    }

    public function deleteDir($path, $leave_empty = false)
    {
        $path = rtrim($path, '/');

        if (! $this->isDir($path)) {
            throw new FilesystemException("Directory does not exist {$path}.");
        }

        if (! $leave_empty && $this->attemptFastDelete($path)) {
            return true;
        }

        $contents = new FilesystemIterator($this->normalize($path));

        foreach ($contents as $item) {
            if ($item->isDir()) {
                $this->deleteDir($item->getPathname());
            } else {
                $this->deleteFile($item->getPathName());
            }
        }

        if (! $leave_empty) {
            @rmdir($this->normalize($path));
        }

        return true;
    }

    public function getDirectoryContents($path, $recursive = false)
    {
        if (! $this->exists($path)) {
            throw new FilesystemException('Cannot get contents of path, the path is invalid: ' . $path);
        }

        if (! $this->isDir($path)) {
            throw new FilesystemException('Cannot get contents of path, the path is not a directory: ' . $path);
        }

        $contents = new FilesystemIterator($this->normalize($path));
        $contents_array = [];

        foreach ($contents as $item) {
            if ($item->isDir() && $recursive) {
                $contents_array += $this->getDirectoryContents($item->getPathname(), $recursive);
            } else {
                $contents_array[] = $item->getPathName();
            }
        }

        return $contents_array;
    }

    public function emptyDir($path, $add_index = true)
    {
        $this->deleteDir($path, true);
        $this->addIndexHtml($path);
    }

    protected function attemptFastDelete($path)
    {
        $path = $this->normalize($path);

        $delete_name = sha1($path . '_delete_' . mt_rand());
        $delete_path = PATH_CACHE . $delete_name;
        $this->rename($path, $delete_path);

        if ($this->exists($delete_path) && is_dir($delete_path)) {
            $delete_path = @escapeshellarg($delete_path);

            if (DIRECTORY_SEPARATOR == '/') {
                @exec("rm -rf {$delete_path}");
            } else {
                @exec("rd /s /q {$delete_path}");
            }

            return  ! $this->exists($delete_path);
        }

        return false;
    }

    public function rename($source, $dest)
    {
        if (! $this->exists($source)) {
            throw new FilesystemException("Cannot rename non-existent path: {$source}");
        } elseif ($this->exists($dest)) {
            throw new FilesystemException("Cannot rename, destination already exists: {$dest}");
        }

        @rename(
            $this->normalize($source),
            $this->normalize($dest)
        );

        $this->ensureCorrectAccessMode($dest);
    }

    public function copy($source, $dest)
    {
        if (! $this->exists($source)) {
            throw new FilesystemException("Cannot copy non-existent path: {$source}");
        }

        if ($this->isDir($source)) {
            $this->recursiveCopy($source, $dest);
        } else {
            copy(
                $this->normalize($source),
                $this->normalize($dest)
            );
        }

        $this->ensureCorrectAccessMode($dest);
    }

    protected function recursiveCopy($source, $dest)
    {
        $dir = opendir($source);
        @mkdir($dest);

        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if ($this->isDir($source . '/' . $file)) {
                    $this->recursiveCopy($source . '/' . $file, $dest . '/' . $file);
                } else {
                    copy($source . '/' . $file, $dest . '/' . $file);
                }
            }
        }

        closedir($dir);
    }

    public function dirname($path)
    {
        return dirname($this->normalize($path));
    }

    public function basename($path)
    {
        return basename($this->normalize($path));
    }

    public function filename($path)
    {
        return pathinfo($this->normalize($path), PATHINFO_FILENAME);
    }

    public function extension($path)
    {
        return pathinfo($this->normalize($path), PATHINFO_EXTENSION);
    }

    public function exists($path)
    {
        if ($path = $this->normalize($path)) {
            return file_exists($path);
        }

        return false;
    }

    public function mtime($path)
    {
        if (! $this->exists($path)) {
            throw new FilesystemException("File does not exist: {$path}");
        }

        return filemtime($this->normalize($path));
    }

    public function touch($path, $time = null)
    {
        if (! $this->exists($path)) {
            throw new FilesystemException("Touching non-existent files is not supported: {$path}");
        }

        if (isset($time)) {
            touch($this->normalize($path), $time);
        } else {
            touch($this->normalize($path));
        }
    }

    public function isDir($path)
    {
        return is_dir($this->normalize($path));
    }

    public function isFile($path)
    {
        return is_file($this->normalize($path));
    }

    public function isReadable($path)
    {
        return is_readable($this->normalize($path));
    }

    public function chmod($path, $mode)
    {
        return @chmod($this->normalize($path), $mode);
    }

    public function isWritable($path)
    {
        if (DIRECTORY_SEPARATOR == '/') {
            return is_writable($this->normalize($path));
        }

        if ($this->isDir($path)) {
            $path = rtrim($this->normalize($path), '/') . '/' . md5(mt_rand(1, 100) . mt_rand(1, 100));

            if (($fp = @fopen($path, FOPEN_WRITE_CREATE)) === false) {
                return false;
            }

            fclose($fp);
            @chmod($path, DIR_WRITE_MODE);
            @unlink($path);

            return true;
        } elseif (($fp = @fopen($path, FOPEN_WRITE_CREATE)) === false) {
            return false;
        }

        fclose($fp);

        return true;
    }

    public function hashFile($algo, $filename)
    {
        if (! $this->exists($filename)) {
            throw new FilesystemException("File does not exist: {$filename}");
        }

        return hash_file($algo, $filename);
    }

    public function getFreeDiskSpace($path = '/')
    {
        return @disk_free_space($path);
    }

    public function include_file($filename)
    {
        include_once($filename);
    }

    public function getUniqueFilename($path)
    {
        $path = $this->normalize($path);

        if (! $this->exists($path)) {
            return $path;
        }

        $i = 0;
        $extension = $this->extension($path);
        $filename = $this->dirname($path) . '/' . $this->filename($path);

        $files = glob($filename . '_*' . $extension);

        if (! empty($files)) {
            if (version_compare(PHP_VERSION, '5.4.0') < 0) {
                rsort($files);
            } else {
                rsort($files, SORT_NATURAL);
            }

            foreach ($files as $file) {
                $number = str_replace(array($filename, $extension), '', $file);
                if (substr_count($number, '_') == 1 && strpos($number, '_') === 0) {
                    $number = str_replace('_', '', $number);
                    if (is_numeric($number)) {
                        $i = (int) $number;

                        break;
                    }
                }
            }
        }

        do {
            $i++;
            $path = $filename . '_' . $i . '.' . $extension;
        } while (in_array($path, $files));

        return $path;
    }

    public function findAndReplace($file, $search, $replace)
    {
        if ($this->exists($file)) {
            return;
        }

        if ($this->isDir($file)) {
            foreach ($this->getDirectoryContents($file) as $file) {
                $this->findAndReplace($file, $search, $replace);
            }

            return;
        }

        $contents = $this->read($file);

        if (strpos($search, '/') === 0) {
            $contents = preg_replace($search, $replace, $contents);
        } else {
            $contents = str_replace($search, $replace, $contents);
        }

        $this->write($file, $contents, true);
    }

    protected function addIndexHtml($dir)
    {
        $dir = rtrim($dir, '/');

        if (! $this->isDir($dir)) {
            throw new FilesystemException("Cannot add index file to non-existant directory: {$dir}");
        }

        if (! $this->isFile($dir . '/index.html')) {
            $this->write($dir . '/index.html', 'Directory access is forbidden.');
        }
    }

    protected function ensureCorrectAccessMode($path)
    {
        if ($this->isDir($path)) {
            $this->chmod($path, 0755);
        } else {
            $this->chmod($path, 0644);
        }
    }

    protected function normalize($path)
    {
        return $path;
    }
}