# Container Interop for Symfony Console

[![Travis](https://travis-ci.org/Articus/ContainerInteropSymfonyConsole.svg?branch=master)](https://travis-ci.org/Articus/ContainerInteropSymfonyConsole)
[![Coveralls](https://coveralls.io/repos/github/Articus/ContainerInteropSymfonyConsole/badge.svg?branch=master)](https://coveralls.io/github/Articus/ContainerInteropSymfonyConsole?branch=master)
[![Codacy](https://api.codacy.com/project/badge/Grade/0606a252112b4bb7846252345343f608)](https://www.codacy.com/app/articusw/ContainerInteropSymfonyConsole?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=Articus/ContainerInteropSymfonyConsole&amp;utm_campaign=Badge_Grade)

This package provides a small factory that will allow to create [Symfony Console Application](https://symfony.com/doc/current/components/console.html) via PSR-11 compatible container. The code is dead simple, I just tired of copying it between projects :)

## Quick start for Zend Expressive application

Let's imagine that you have an existing Zend Expressive application and it requires some console utility.
First of all you need to add `articus/container-interop-symfony-console` package to your [composer.json](https://getcomposer.org/doc/04-schema.md#require).
Next step is configuring your console application. Here is a small example (it is in YAML just for readability):
```YAML
dependencies:
    Symfony\Component\Console\Application: ContainerInteropSymfonyConsole\Factory
    #Service for your console command, should extend \Symfony\Component\Console\Command\Command
    App\MyCommand: App\MyCommandFactory

Symfony\Component\Console\Application:
    name: My App
    version: 1.0.0
    commands:
        - App\MyCommand
```
Configuration should be available via `config` service of your container. Check `src/ContainerInteropSymfonyConsole/Options.php` for full list of available options.

Finally you need to create PHP-script that will be your console application entrypoint. For example `bin/console.php` file with the following content:

```PHP
#!/usr/bin/php
<?php

chdir(dirname(__DIR__));

/** @var \Psr\Container\ContainerInterface $container */
$container = require_once __DIR__ . '/../config/container.php';

/** @var \Symfony\Component\Console\Application $app */
$app = $container->get(\Symfony\Component\Console\Application::class);
$app->run();
```
Now if you execute `php bin/console.php list` your console command should be listed and it should be possible to launch it.