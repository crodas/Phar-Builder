<?php

namespace crodas\PharBuilder;

use RuntimeException;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Finder\Finder;

class Configuration
{
    protected $name;
    protected $main;
    protected $autoload = true;
    protected $files;

    public function __construct($file)
    {
        if (!is_file($file)) {
            throw new RuntimeException("Cannot find file {$file}");
        }
        $content = file_get_contents($file);
        preg_match("@\.([a-z0-9]{1,6})$@", $file, $ext);
        switch (strtolower($ext[1])) {
        case 'yaml':
        case 'yml':
            $object = Yaml::parse($content);
            break;
        case 'json':
            $object = json_decode($content, true);
            break;
        default:
            throw new RuntimeException("Dont know how to pase files `{$ext[1]}`");
        }

        foreach ((Array)$object as $key => $value) {
            $method = "set{$key}";
            if (!is_callable(array($this, $method))) {
                throw new RuntimeException("Unexpected property {$key}");
            }
            $this->$method($value);
        }
    }
    
    protected function _setString($name, $value)
    {
        if (!is_string($value)) {
            throw new RuntimeException($name . " should be a string");
        }

        $this->$name = $value;
    }

    protected function setMain($value)
    {
        return $this->_setString('main', $value);
    }

    protected function setName($value)
    {
        return $this->_setString('name', $value);
    }

    public function __get($name)
    {
        if ($name == 'files') {
            return call_user_func_array('array_merge', array_map(function($finder) {
                return iterator_to_array($finder);
            }, $this->files));
        }
        return $this->$name;
    }

    protected function setFiles($values)
    {
        if (!is_array($values)) {
            $values = [$values];
        }
        foreach ($values as  $value) {
            $finder = new Finder;
            if (is_string($value)) {
                $finder->files()->in($value);
            } else {
                $finder->files()->in(key($value));
                foreach (current($value) as $key => $value) {
                    $finder->$key($value);
                }
            }
            $this->files[] = $finder;
        }
    }
}
