<?php
ini_set('memory_limit', '1G');
set_time_limit(0);

use Symfony\Component\Console\Application;
use Symfony\Component\Finder\Finder;

require __DIR__.'/../vendor/autoload.php';
require __DIR__.'/../config/settings.php';

$application = new Application('Main Console commands to do variouse jobs', '1.0.0');

// Initialize Finder
$finder = new Finder();
$finder->files()->in(ROOT_DIR . '/src/Command')->name('*Command.php');

foreach ($finder as $file) {
    $relativePath = $file->getRelativePath();
    $className = $file->getBasename('.php');

    $namespace = 'App\\Command';
    if ($relativePath) {
        $namespace .= '\\' . str_replace('/', '\\', $relativePath);
    }

    $fullClassName = $namespace . '\\' . $className;

    // Check if the class exists and is a subclass of Symfony Command
    if (class_exists($fullClassName) && is_subclass_of($fullClassName, Symfony\Component\Console\Command\Command::class)) {
        $application->add(new $fullClassName());
    }
}

// Run the application
$application->run();
