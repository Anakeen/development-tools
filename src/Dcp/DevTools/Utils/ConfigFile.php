<?php

namespace Dcp\Devtools\Utils;

class ConfigFile
{
    const DEFAULT_FILE_NAME='build.json';

    const GET_MERGE_DEFAULTS = 1 << 0;

    const SET_MERGE_VALUE = 1 << 0;

    protected $config;
    protected $sourcePath = null;
    protected $fileName;


    public function __construct($sourcePath, $fileName=null)
    {
        if (is_null($fileName)) {
            $this->fileName = self::DEFAULT_FILE_NAME;
        } else {
            $this->fileName = $fileName;
        }

        if(!is_dir($sourcePath) && !(is_link($sourcePath) && is_dir(readlink($sourcePath)))) {
            throw new \Exception(
                sprintf(
                    "%s is not a directory.",
                    $sourcePath
                )
            );
        }

        $this->sourcePath = $sourcePath;

        if(!file_exists($this->getConfigFilePath())) {
            throw new \Exception(
                sprintf(
                    "%s does not exists.",
                    $this->getConfigFilePath()
                )
            );
        }

        $this->loadConfig();
    }

    public function get($property, $default=null, $options=0) {
        if(isset($this->config[$property])) {
            $val = $this->config[$property];
            if(is_array($val) && ($options & self::GET_MERGE_DEFAULTS)) {
                $val = array_merge($val, $default);
            }
            return $val;
        }
        return $default;
    }

    public function set($property, $value) {
        $oldValue = $this->get($property, []);
        $this->config[$property] = $value;
        return $oldValue;
    }

    public function getConfig() {
        return array_merge($this->config);
    }

    public function getConfigFilePath() {
        return $this->sourcePath . DIRECTORY_SEPARATOR . $this->fileName;
    }

    public function getModulePath() {
        return $this->sourcePath;
    }

    public function __get($property) {
        return $this->get($property);
    }

    public function __set($property, $value) {
        $this->set($property, $value);
    }

    public function saveConfig() {
        $jsonConfig = json_encode($this->config);
        if(JSON_ERROR_NONE !== json_last_error()) {
            throw new \Exception(
                sprintf(
                    "An error occured while saving %s: %d (%s).",
                    $this->getConfigFilePath(),
                    json_last_error(),
                    json_last_error_msg()
                )
            );
        }
        if (false === file_put_contents($this->getConfigFilePath(), $jsonConfig)) {
            throw new \Exception(
                sprintf(
                    "An error occured while saving %s: could not write file.",
                    $this->getConfigFilePath()
                )
            );
        }
    }

    protected function loadConfig()
    {
        $config = json_decode(
            file_get_contents($this->getConfigFilePath()),
            true
        );
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \Exception(
                sprintf(
                    "An error occured while reading %s: %d (%s)",
                    $this->getConfigFilePath(),
                    json_last_error(),
                    json_last_error_msg()
                )
            );
        }
        $this->config = $config;
    }
}