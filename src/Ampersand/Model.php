<?php

/*
 * This file is part of the Ampersand backend framework.
 *
 */

namespace Ampersand;

use Psr\Log\LoggerInterface;
use Exception;
use Ampersand\IO\JSONReader;

/**
 *
 * @author Michiel Stornebrink (https://github.com/Michiel-s)
 *
 */
class Model
{
    const HASH_ALGORITHM = 'md5';

    /**
     * Logger
     *
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * Directory where Ampersand model is generated in
     *
     * @var string
     */
    protected $folder;

    /**
     * Filepath for saving checksums of generated Ampersand model
     *
     * @var string
     */
    protected $checksumFile;

    /**
     * List of files that contain the generated Ampersand model
     *
     * @var array
     */
    protected $modelFiles = [];

    /**
     * Constructor
     *
     * @param string $folder directory where Ampersand model is generated in
     */
    public function __construct(string $folder, LoggerInterface $logger)
    {
        $this->folder = realpath($folder);
        $this->logger = $logger;

        $this->checksumFile = "{$this->folder}/checksums.txt";
        
        // Ampersand model files
        $this->modelFiles = [
            'concepts' => $this->folder . '/concepts.json',
            'conjuncts' => $this->folder . '/conjuncts.json',
            'interfaces' => $this->folder . '/interfaces.json',
            'populations' => $this->folder . '/populations.json',
            'relations' => $this->folder . '/relations.json',
            'roles' => $this->folder . '/roles.json',
            'rules' => $this->folder . '/rules.json',
            'settings' => $this->folder . '/settings.json',
            'views' => $this->folder . '/views.json'
        ];
        
        // Write checksum file if not yet exists
        if (!file_exists($this->checksumFile)) {
            $this->writeChecksumFile();
        }
    }

    /**
     * Write new checksum file of generated model
     *
     * @return void
     */
    public function writeChecksumFile()
    {
        /* Earlier implementation.
        $this->logger->debug("Writing checksum file for generated Ampersand model files");

        $checksums = [];
        foreach ($this->modelFiles as $path) {
            $filename = pathinfo($path, PATHINFO_BASENAME);
            $checksums[$filename] = hash_file(self::HASH_ALGORITHM, $path);
        }

        file_put_contents($this->checksumFile, serialize($checksums));
        */

        // Now: use the hash value from generated output (created by Haskell codebase)
        file_put_contents($this->checksumFile, $this->getSetting('modelHash'));
    }

    /**
     * Verify checksums of generated model. Return true when valid, false otherwise.
     *
     * @return bool
     */
    public function verifyChecksum(): bool
    {
        $this->logger->debug("Verifying checksum for Ampersand model files");

        return (file_get_contents($this->checksumFile) === $this->getSetting('modelHash'));

        /* Earlier implementation.
        $valid = true; // assume all checksums match

        // Get stored checksums
        $checkSums = unserialize(file_get_contents($this->checksumFile));

        // Compare checksum with actual file
        foreach ($this->modelFiles as $path) {
            $filename = pathinfo($path, PATHINFO_BASENAME);
            if ($checkSums[$filename] !== hash_file(self::HASH_ALGORITHM, $path)) {
                $this->logger->warning("Invalid checksum of file '{$filename}'");
                $valid = false;
            }
        }

        return $valid;
        */
    }

    public function getFolder(): string
    {
        return $this->folder;
    }

    public function getFilePath(string $filename): string
    {
        if (!array_key_exists($filename, $this->modelFiles)) {
            throw new Exception("File '{$filename}' is not part of the specified Ampersand model files", 500);
        }

        return $this->modelFiles[$filename];
    }

    protected function loadFile(string $filename): JSONReader
    {
        $reader = new JSONReader();
        $reader->loadFile($this->getFilePath($filename));
        return $reader;
    }

    protected function getFile(string $filename): JSONReader
    {
        static $loadedFiles = [];

        if (!array_key_exists($filename, $loadedFiles)) {
            $loadedFiles[$filename] = $this->loadFile($filename);
        }

        return $loadedFiles[$filename];
    }

    protected function getSetting(string $setting)
    {
        $settings = $this->getFile('settings')->getContent();
        
        if (!property_exists($settings, $setting)) {
            throw new Exception("Undefined setting '{$setting}'", 500);
        }

        return $settings->$setting;
    }
}
