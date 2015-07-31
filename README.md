# Phar-Builder

Build Phar like a boss.

*This is a work in process*.

## Why?

Build phar files should be really simple.

## How?

You need to add a `build.yml` in your project. It needs two parameters (`name` and `include`). There also some extra parameter like `cli` (the script to execute from the console).

```yaml
name: foobar.phar
include: [src/, vendor/]
```

##Todo

1. Unit-tests
2. Docs
3. JSON reader as well
