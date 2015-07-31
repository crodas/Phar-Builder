<?php

namespace crodas\PharBuilder;

use RuntimeException;
use Symfony\Component\Yaml\Yaml;

class BuildFile
{
    protected $file;
    protected $yaml;

    public function __construct($file)
    {
        if (!is_file($file)) {
            throw new RuntimeException("$file is not a file");
        }
        $this->file = $file;
        $this->parse();
    }

    public function parse()
    {
        $yaml = Yaml::parse(file_get_contents($this->file));
        foreach (['name', 'include'] as $req) {
            if (empty($yaml[$req])) {
                throw new RuntimeException("$req property missing in {$this->file}");
            }
        }
        $this->settings = $yaml;
    }

    public function install($force = false)
    {
        $phar = $this->settings['name'];
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
        $builder = new PharBuilder($this->settings['name']);
        foreach ((array)$this->settings['include'] as $include) {
            $builder->addDir(getcwd() . '/' . $include, $include);
        }
        if (!empty($this->settings['cli'])) {
            $builder->addFile($this->settings['cli'], getcwd() . '/' . $this->settings['cli']);
            $builder->mainScript($this->settings['cli']);
        }
        $builder->build();
        return $this->settings['name'];
    }
}


