<?php

namespace crodas\PharBuilder;

use RuntimeException;

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
 *  @Cli("phar:install", "Install a phar file")
 *  @Arg("path", OPTIONAL)
 *  @Option("force")
 */
function install($input, $output)
{
    PharBuilder::checkCanCreatePhar();
    $force = $input->getOption('force');
    $path  = get_path($input->getArgument('path') ?: 'build.yml');
    $build = new BuildFile($path);
    $path  = $build->install($force);
    $output->writeLn("<info>Installed {$path}</info>");
}

/**
 *  @Cli("phar:build", "Build script")
 *  @Arg("path", OPTIONAL)
 */
function main($input, $output)
{
    PharBuilder::checkCanCreatePhar();
    $path  = get_path($input->getArgument('path') ?: 'build.yml');
    $build = new BuildFile($path);
    $output->writeLn("<info>Write phar using {$path}</info>");
    $output->writeLn("<info>Created " . $build->build() . "</info>");
}
