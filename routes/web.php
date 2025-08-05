<?php
use Slim\App;

return function (App $app) {
    $app->get('/', \App\Controllers\HomeController::class . ':index');
    $app->post('/send-emails', \App\Controllers\EmailController::class . ':send');
};
