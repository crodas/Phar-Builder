<?php

namespace crodas\PharBuilder;

use Phar;
use Symfony\Component\Process\ProcessBuilder;
use crodas\FileUtil\File;
use Autoloader;
use FileSystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use DirectoryIterator;

class PharBuilder
{
    protected $phar;
    protected $files = array();
    protected $filter;
    protected $stub;
    protected $autoload;
    protected $dir;

    public static function checkCanCreatePhar()
    {
        if (!ini_get('phar.readonly')) {
            return;
        }
        $php  = $_SERVER['_'];
        if (preg_match("@/php[0-9\\.]*$@", $php)) {
            $argv = array_merge([$php, '-d', 'phar.readonly=off'], $GLOBALS['argv']);
        } else {
            $argv = array_merge(['/usr/bin/env', 'php', '-d', 'phar.readonly=off'], $GLOBALS['argv']);
        }
        $builder = new ProcessBuilder($argv);
        $builder->setTimeout(300);
        $builder->getProcess()->run(function ($type, $buffer) {
            echo $buffer;       
        });
        exit; /* Die! */
    }

    public function __construct($file)
    {
        self::checkCanCreatePhar();
        if (is_file($file)) {
            unlink($file);
        }
        $this->phar = new Phar($file);
        $this->tmp  = sys_get_temp_dir() . '/phar-' . uniqid(true);
        $this->autoload = "autoload-" . uniqid(true) . ".php";
    }

    public function addFilter(Callable $fnc)
    {
        $this->filter = $fnc;
        return $this;
    }

    public function addDir($directory, $base = '', $filter = null)
    {
        $filter = $filter ?: $this->filter;
        $path  = realpath($directory);
        $iter  = new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS);
        foreach (new RecursiveIteratorIterator($iter) as $file) {
            if (!$file->isfile() || ($filter && !$filter($file))) {
                continue;
            }
            $relative = $base . substr($file->getPathName(), strlen($path));
            $this->addFile($relative, $file);
        }
    }

    public function mainScript($path)
    {
        if (empty($this->files[$path])) {
            throw new \RuntimeException("Cannot set $path as main script, it is not in the phar file");
        }
        $stub = file_get_contents(__DIR__ . '/Stub.php');
        $stub = str_replace('__STUB__', var_export($path, true), $stub);
        $stub = str_replace('__AUTOLOAD__', var_export($this->autoload, true), $stub);
        $this->stub = 'index-' . uniqid(true) . '.php';
        $this->addFile($this->stub, $stub);
    }

    public function addFiles(Array $files)
    {
        foreach ($files as $file) {
            $this->addFile($file, getcwd() . '/'  . $file);
        }
    }

    public function addFile($name, $path = NULL)
    {
        if ($path == NULL) {
            $path = $name;
        }
        $name     = trim($name, "\\/");
        $realPath = $this->tmp . '/' . $name;
        $this->files[$name] = true;
        if (file_exists($path)) {
            File::write($realPath, file_get_contents($path));
        } else {
            File::write($realPath, $path);
        }
    }

    public function build()
    {
        if ($this->stub) {
            $autoload = new Autoloader\Generator($this->tmp);
            $autoload->relativePaths()->IncludePSR0Autoloader(false);
            $autoload->generate($this->tmp . "/" . $this->autoload, getcwd() . "/.phar-build-tmp");
            $this->phar->setStub(
                "#!/usr/bin/env php\n"
                . $this->phar->createDefaultStub($this->stub)
            );
        }
        $this->phar->startBuffering();
        $this->phar->buildFromDirectory($this->tmp);
        $this->phar->setSignatureAlgorithm(phar::SHA1);
        $this->phar->stopBuffering();
    }
}
