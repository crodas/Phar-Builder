# Phar-Builder

Build Phar like a boss.

*This is a work in process*.

## How to install

You need to [Composer](https://getcomposer.org/) and PHP with phar enabled.

```bash
git clone https://github.com/crodas/Phar-Builder.git
cd Phar-Builder
composer install
php cli.php install
```

## Why?

Build phar files should be really simple.

## How?

You need to add a `spec.yml` in your project. It needs two parameters (`name` and `include`). There also some extra parameter like `cli` (the script to execute from the console).

```yaml
name: phar-builder.phar
files:  
    - src: { name: "*.php" }
    - vendor: { exclude: ["Tests", "tests"] }
main: cli.php
```

##Todo

1. Unit-tests
2. Docs
3. JSON reader as well
