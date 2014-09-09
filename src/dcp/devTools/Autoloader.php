<?php


namespace dcp\DevTools;

require_once "AutoloaderException.php";

class Autoloader
{
    private $baseDir;

    /**
     * Autoloader constructor.
     *
     * @param string $baseDir Mustache library base directory (default: dirname(__FILE__).'/..')
     */
    public function __construct($baseDir = null)
    {
        if ($baseDir === null) {
            $baseDir = dirname(__FILE__) . DIRECTORY_SEPARATOR;
        }

        $vendorBaseDir = dirname(__FILE__)
            . DIRECTORY_SEPARATOR . '..'
            . DIRECTORY_SEPARATOR . '..'
            . DIRECTORY_SEPARATOR . '..'
            . DIRECTORY_SEPARATOR. 'vendor';

        // realpath doesn't always work, for example, with stream URIs
        $realDir = realpath($baseDir);
        if (is_dir($realDir)) {
            $this->baseDir = $realDir;
        } else {
            $this->baseDir = $baseDir;
        }

        $realVendorDir = realpath($vendorBaseDir);
        if (is_dir($realVendorDir)) {
            $this->vendorBaseDir = $realVendorDir;
        } else {
            $this->vendorBaseDir = $vendorBaseDir;
        }
    }

    /**
     * Register a new instance as an SPL autoloader.
     *
     * @param string $baseDir Mustache library base directory (default: dirname(__FILE__).'/..')
     *
     * @return \dcp\DevTools\Autoloader Registered Autoloader instance
     */
    public static function register($baseDir = null)
    {
        $loader = new self($baseDir);
        spl_autoload_register(array($loader, 'autoload'));

        return $loader;
    }

    /**
     * Autoload Mustache classes.
     *
     * @param string $class
     * @throws AutoloaderException
     */
    public function autoload($class)
    {
        $file = false;
        if ($class[0] === '\\') {
            $class = substr($class, 1);
        }

        if (strpos($class, 'Mustache') === 0) {
            $currentBaseDir = $this->vendorBaseDir . DIRECTORY_SEPARATOR . "mustache" . DIRECTORY_SEPARATOR . "src";
            $file = sprintf('%s' . DIRECTORY_SEPARATOR . '%s.php', $currentBaseDir,
                str_replace('_', DIRECTORY_SEPARATOR, $class));
        }

        if (strpos($class, 'Ulrichsg\\Getopt\\') === 0) {
            $currentBaseDir = $this->vendorBaseDir . DIRECTORY_SEPARATOR . "getopt-php" . DIRECTORY_SEPARATOR . "src";
            $class = str_replace('Ulrichsg\\Getopt\\', 'Ulrichsg'.DIRECTORY_SEPARATOR.'Getopt'.DIRECTORY_SEPARATOR, $class);
            $file = sprintf('%s' . DIRECTORY_SEPARATOR . '%s.php', $currentBaseDir,
                str_replace('\\', DIRECTORY_SEPARATOR, $class));
        }

        if (strpos($class, 'BuildTools') === 0) {
            $currentBaseDir = $this->vendorBaseDir . DIRECTORY_SEPARATOR . "buildTools" . DIRECTORY_SEPARATOR . "src";
            $file = sprintf('%s' . DIRECTORY_SEPARATOR . '%s.php', $currentBaseDir,
                str_replace('_', DIRECTORY_SEPARATOR, $class));
        }

        if (strpos($class, 'dcp\\DevTools\\') === 0) {
            $class = str_replace('dcp\\DevTools\\', '', $class);
            $file = sprintf('%s' . DIRECTORY_SEPARATOR . '%s.php', $this->baseDir,
                str_replace('\\', DIRECTORY_SEPARATOR, $class));
        }

        if ($file === false || !is_file($file)) {
            var_export("Unable to find " . $class);
            throw new AutoloaderException("Unable to find $class at $file");
        }

        require $file;


    }
}
