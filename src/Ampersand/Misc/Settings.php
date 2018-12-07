<?php

/*
 * This file is part of the Ampersand backend framework.
 *
 */

namespace Ampersand\Misc;

use Exception;
use Ampersand\Misc\Extension;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Serializer\Encoder\YamlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\JsonDecode;

/**
 *
 * @author Michiel Stornebrink (https://github.com/Michiel-s)
 *
 */
class Settings
{
    /**
     * Array of all settings
     * Setting keys (e.g. global.debugmode) are case insensitive
     *
     * @var array
     */
    protected $settings = [];

    /**
     * List of configured extensions
     *
     * @var \Ampersand\Misc\Extension[]
     */
    protected $extensions = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->loadSettingsYamlFile(dirname(__FILE__) . '/defaultSettings.yaml');
    }

    /**
     * Load settings file
     *
     * @param string $filePath
     * @param bool $overwriteAllowed specifies if already set settings may be overwritten
     * @return \Ampersand\Misc\Settings $this
     */
    public function loadSettingsJsonFile(string $filePath, bool $overwriteAllowed = true): Settings
    {
        $fileSystem = new Filesystem;
        if (!$fileSystem->exists($filePath)) {
            throw new Exception("Cannot load settings file. Specified path does not exist: '{$filePath}'", 500);
        }

        $decoder = new JsonDecode(false);
        $settings = $decoder->decode(file_get_contents($filePath), JsonEncoder::FORMAT);

        if (!is_array($settings)) {
            throw new Exception("Settings var not provided as an array", 500);
        }

        foreach ($settings as $setting => $value) {
            $this->set($setting, $value, $overwriteAllowed);
        }
        
        return $this;
    }

    /**
     * Load settings file (yaml format)
     *
     * @param string $filePath
     * @param bool $overwriteAllowed specifies if already set settings may be overwritten
     * @return \Ampersand\Misc\Settings $this
     */
    public function loadSettingsYamlFile(string $filePath, bool $overwriteAllowed = true): Settings
    {
        $fileSystem = new Filesystem;
        if (!$fileSystem->exists($filePath)) {
            throw new Exception("Cannot load settings file. Specified path does not exist: '{$filePath}'", 500);
        }

        $encoder = new YamlEncoder();
        $file = $encoder->decode(file_get_contents($filePath), YamlEncoder::FORMAT);

        foreach ((array)$file['settings'] as $setting => $value) {
            $this->set($setting, $value, $overwriteAllowed);
        }

        foreach ((array)$file['extensions'] as $extName => $data) {
            $bootstrapFile = $data['bootstrap'] ?? null;
            $configFile = $data['config'] ?? null;
            $this->extensions[] = new Extension($extName, $bootstrapFile, $configFile);

            if (!is_null($configFile)) {
                $this->loadSettingsYamlFile($configFile, false); // extensions settings are not allowed to overwrite existing settings
            }
        }

        return $this;
    }

    /**
     * Get a specific setting
     *
     * @param string $setting
     * @param mixed $defaultIfNotSet
     * @return mixed
     */
    public function get(string $setting, $defaultIfNotSet = null)
    {
        $setting = strtolower($setting); // use lowercase

        if (!array_key_exists($setting, $this->settings) && is_null($defaultIfNotSet)) {
            throw new Exception("Setting '{$setting}' is not specified", 500);
        }

        return $this->settings[$setting] ?? $defaultIfNotSet;
    }

    /**
     * Set a specific setting to a (new) value
     *
     * @param string $setting
     * @param mixed $value
     * @param boolean $overwriteAllowed specifies if already set setting may be overwritten
     * @return void
     */
    public function set(string $setting, $value = null, $overwriteAllowed = true)
    {
        $setting = strtolower($setting); // use lowercase
        
        if (array_key_exists($setting, $this->settings) && !$overwriteAllowed) {
            throw new Exception("Setting '{$setting}' is set already; overwrite is not allowed", 500);
        }

        $this->settings[$setting] = $value;
    }

    /**
     * Get list of configured extensions
     *
     * @return \Ampersand\Misc\Extension[]
     */
    public function getExtensions()
    {
        return $this->extensions;
    }
}
