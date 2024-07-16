<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (file_exists(dirname(__DIR__).'/config/bootstrap.php')) {
    require dirname(__DIR__).'/config/bootstrap.php';
} elseif (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}


// Ensure the environment is set to 'test'
$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = 'test';
$_SERVER['DATABASE_TEST_URL'] = 'sqlite:///' . dirname(__DIR__) . '/var/test.db';

// // Ensure the database schema is created
 //passthru('php bin/console doctrine:database:create --env=test --if-not-exists');
 //passthru('php bin/console doctrine:schema:update --force --env=test');