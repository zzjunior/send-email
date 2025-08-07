<?php
require __DIR__ . '/../vendor/autoload.php';

// Carrega as variáveis de ambiente
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

$app = AppFactory::create();

// Configura o Twig
$twig = Twig::create(__DIR__ . '/../views', ['cache' => false]);
$app->add(TwigMiddleware::create($app, $twig));

// Carrega as rotas
(require __DIR__ . '/../routes/web.php')($app);

$app->run();
