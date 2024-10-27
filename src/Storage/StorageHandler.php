<?php

declare(strict_types=1);

namespace tTorMt\SChat\Storage;

/**
 * Handles the storage and retrieval of user files. Storage order is year/month/day/count/fileName hash.
 * Example: 2025/11/28/0/f175a180123e0b686b1c9ca68660f226
 * Max files per dir is 1000
 */
class StorageHandler
{
    public const int MAX_FILES_PER_DIR = 1000;

    /**
     * Creates or returns an active directory path
     * @return string absolute path to a save directory
     * @throws DirectoryCouldNotBeCreatedException
     */
    public function getSavePath(): string
    {
        $year = date("Y");
        $month = date("m");
        $day = date("d");
        $path = __DIR__."/../../storage/$year/$month/$day/";
        if (!is_dir($path.'0')) {
            $path .= '0';
            if (!mkdir($path, 0770, true)) {
                throw new DirectoryCouldNotBeCreatedException();
            }
            return $path;
        }
        $dirList = glob($path.'*', GLOB_ONLYDIR);
        $latestDirNum = array_reduce($dirList, function ($carry, $item) {
            $dirNum = (int)basename($item);
            return max($dirNum, $carry);
        }, 0);
        if (count(scandir($path.$latestDirNum)) >= self::MAX_FILES_PER_DIR) {
            $latestDirNum++;
            $path .= $latestDirNum;
            if (!mkdir($path, 0770, true)) {
                throw new DirectoryCouldNotBeCreatedException();
            }
            return $path;
        }
        return $path.$latestDirNum;
    }

    /**
     * Removes a directory and it's contents recursively
     * @param string $path
     * @return void
     */
    public function removeDir(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        $nameList = array_diff(scandir($path), ['.', '..']);
        foreach ($nameList as $name) {
            $filePath = "$path/$name";
            if (is_dir($filePath)) {
                $this->removeDir($filePath);
            } else {
                unlink($filePath);
            }
        }
        rmdir($path);
    }

    /**
     * Checks file type, resizes and stores an image file
     * @param string $path
     * @return string relative to the storage file path
     * @throws DirectoryCouldNotBeCreatedException
     * @throws ImageStoreException|WrongImageTypeException
     */
    public function storeImage(string $path): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $type = finfo_file($finfo, $path);
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!in_array($type, $allowedTypes)) {
            throw new WrongImageTypeException();
        }

        $savePath = ($this->getSavePath()).'/'.md5(uniqid());
        if (!copy($path, $savePath)) {
            throw new ImageStoreException();
        }
        $storagePath = realpath(__DIR__.'/../../storage/');
        $savePath = realpath($savePath);
        return str_replace($storagePath.'/', '', $savePath);
    }

    /**
     * Stores different types of files.
     * @param string $path
     * @param string $fileName
     * @return string relative to the storage file path (hash.FILENAME)
     * @throws DirectoryCouldNotBeCreatedException
     * @throws FileStoreException
     */
    public function storeFile(string $path, string $fileName): string
    {
        $savePath = ($this->getSavePath()).'/'.md5(uniqid()).'$'.$fileName;
        if (!copy($path, $savePath)) {
            throw new FileStoreException();
        }
        $storagePath = realpath(__DIR__.'/../../storage/');
        $savePath = realpath($savePath);
        return str_replace($storagePath.'/', '', $savePath);
    }
}
