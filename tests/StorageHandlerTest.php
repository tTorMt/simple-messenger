<?php

namespace tTorMt\SChat\Tests;

use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use tTorMt\SChat\Storage\DirectoryCouldNotBeCreatedException;
use tTorMt\SChat\Storage\ImageStoreException;
use tTorMt\SChat\Storage\StorageHandler;
use tTorMt\SChat\Storage\WrongImageTypeException;

class StorageHandlerTest extends TestCase
{
    private static string $path;

    public static function setUpBeforeClass(): void
    {
        $year = date("Y");
        $month = date("m");
        $day = date("d");
        $path = __DIR__."/../storage/$year/$month/$day";
        echo `rm -rf $path`;
        self::$path = $path;
    }

    public static function tearDownAfterClass(): void
    {
        $path = self::$path;
        echo `rm -rf $path`;
    }

    public function testCreateStorageHandler(): StorageHandler
    {
        $storageHandler = new StorageHandler();
        $this->assertInstanceOf(StorageHandler::class, $storageHandler);
        return $storageHandler;
    }

    /**
     * @throws DirectoryCouldNotBeCreatedException
     */
    #[Depends('testCreateStorageHandler')]
    public function testGetSavePath(StorageHandler $storageHandler): StorageHandler
    {
        $path = $storageHandler->getSavePath();
        $this->assertSame(realpath(self::$path.'/0'), realpath($path));
        $path = $storageHandler->getSavePath();
        $this->assertSame(realpath(self::$path.'/0'), realpath($path));
        for ($i = 1; $i < 1001; $i++) {
            touch($path.'file'.$i);
        }
        $path = $storageHandler->getSavePath();
        $this->assertSame(realpath(self::$path.'/1'), realpath($path));
        return $storageHandler;
    }

    /**
     * @throws WrongImageTypeException
     * @throws DirectoryCouldNotBeCreatedException
     * @throws ImageStoreException
     */
    #[Depends('testGetSavePath')]
    public function testStoreImage(StorageHandler $storageHandler): StorageHandler
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $storagePath = realpath(__DIR__.'/../storage');
        $imagePath = $storageHandler->storeImage(__DIR__.'/assets/img.gif');
        $imagePath = $storagePath.'/'.$imagePath;
        $this->assertTrue(file_exists($imagePath));
        $this->assertSame(file_get_contents(__DIR__.'/assets/img.gif'), file_get_contents($imagePath));
        $this->assertSame(finfo_file($finfo, $imagePath), 'image/gif');

        $imagePath = $storageHandler->storeImage(__DIR__.'/assets/img.jpg');
        $imagePath = $storagePath.'/'.$imagePath;
        $this->assertTrue(file_exists($imagePath));
        $this->assertSame(file_get_contents(__DIR__.'/assets/img.jpg'), file_get_contents($imagePath));
        $this->assertSame(finfo_file($finfo, $imagePath), 'image/jpeg');

        $imagePath = $storageHandler->storeImage(__DIR__.'/assets/img.png');
        $imagePath = $storagePath.'/'.$imagePath;
        $this->assertTrue(file_exists($imagePath));
        $this->assertSame(file_get_contents(__DIR__.'/assets/img.png'), file_get_contents($imagePath));
        $this->assertSame(finfo_file($finfo, $imagePath), 'image/png');

        return $storageHandler;
    }

    /**
     * @throws DirectoryCouldNotBeCreatedException
     * @throws ImageStoreException
     */
    #[Depends('testStoreImage')]
    public function testWrongImageType(StorageHandler $storageHandler): void
    {
        $this->expectException(WrongImageTypeException::class);
        $storageHandler->storeImage(__FILE__);
    }

    /**
     * @throws DirectoryCouldNotBeCreatedException
     */
    #[Depends('testStoreImage')]
    public function testRemoveDir(StorageHandler $storageHandler): void
    {
        $storageHandler->removeDir(__DIR__.'/assets/img.jpg');
        $this->assertTrue(file_exists(__DIR__.'/assets/img.jpg'));

        $activePath = $storageHandler->getSavePath();
        $this->assertTrue(file_exists($activePath));
        $storageHandler->removeDir($activePath);
        $this->assertFalse(file_exists($activePath));
    }
}
