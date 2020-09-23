<?php
/**
 * Setup application environment
 */
$dotenv = Dotenv\Dotenv::create(__DIR__, '.env');
$my_env = $dotenv->load();

defined('YII_DEBUG') or define('YII_DEBUG', getenv('YII_DEBUG') === 'true');
defined('YII_ENV') or define('YII_ENV', getenv('YII_ENV') ?: 'local');