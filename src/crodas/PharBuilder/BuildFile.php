<?php

namespace crodas\PharBuilder;

use RuntimeException;
use Symfony\Component\Yaml\Yaml;

class BuildFile
{
    protected $config;
    protected $yaml;

    public function __construct($file)
    {
        $this->config = new Configuration($file);
    }

    public function install($force = false)
    {
        $phar = $this->config->name;
        if (!is_file($phar) || $force) {
            $this->build();
        }
        $exe = str_replace(".phar", "", $phar);
        copy($phar, $exe);
        foreach (explode(":", $_SERVER["PATH"]) as $path) {
            if (is_writable($path) && strpos($path, "sbin") === false) {
                $target = $path . "/" . $exe;
                copy($exe, $target);
                chmod($target, 0755);
                unlink($exe);
                return realpath($target);
            }
        }
        throw new RuntimeException("Cannot install {$phar}, you may need to run as `sudo`");
    }

    public function build()
    {
        $builder = new PharBuilder($this->config->name);
        foreach ($this->config->files as $file) {
            $builder->addFile($file, getcwd() . '/'  . $file);
        }
        if ($this->config->main) {
            $builder->addFile($this->config->main, getcwd() . '/' . $this->config->main);
            $builder->mainScript($this->config->main);
        }
        $builder->build();
        return $this->config->name;
    }
}


