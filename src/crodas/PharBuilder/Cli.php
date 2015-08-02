<?php

namespace crodas\PharBuilder;

use RuntimeException;
use Symfony\Component\Yaml\Yaml;

function get_path($path)
{
    foreach ([$path, getcwd() . '/' . $path] as $p) {
        if (file_exists($p)) {
            return $p;
        }
    }

    throw new RuntimeException("Cannot find file $path");
}

/**
 *  @Cli("composer", "Build a spec.yml file based on your composer.json")
 */
function from_composer($input, $output)
{
    $file = 'composer.json';
    if (!is_file($file)) {
        throw new RuntimeException("Cannot find composer.json");
    }
    $composer = json_decode(file_get_contents('composer.json'), true);
    foreach (array('autoload', 'name') as $name) {
        if (empty($composer[$name])) {
            throw new RuntimeException("Composer doesn't have $name property");
        }
    }

    $files = array();
    foreach ($composer['autoload'] as $desc) {
        foreach ($desc as $file) {
            $files[] = $file;
        }
    }
    $files[] = array('vendor' => array("exclude" => array("tests", "Tests")));

    $spec = array(
        'name' => substr(strstr($composer['name'], "/"), 1) . ".phar",
        'files' => $files,
    );

    echo Yaml::dump($spec, 3);
}

/**
 *  @Cli("install", "Install a phar file")
 *  @Arg("path", OPTIONAL)
 *  @Option("force")
 */
function install($input, $output)
{
    PharBuilder::checkCanCreatePhar();
    $force = $input->getOption('force');
    $path  = get_path($input->getArgument('path') ?: 'spec.yml');
    $build = new BuildFile($path);
    $path  = $build->install($force);
    $output->writeLn("<info>Installed {$path}</info>");
}

/**
 *  @Cli("build", "Build script")
 *  @Arg("path", OPTIONAL)
 */
function main($input, $output)
{
    PharBuilder::checkCanCreatePhar();
    $path  = get_path($input->getArgument('path') ?: 'spec.yml');
    $build = new BuildFile($path);
    $output->writeLn("<info>Write phar using {$path}</info>");
    $output->writeLn("<info>Created " . $build->build() . "</info>");
}
