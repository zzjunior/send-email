<?php
namespace App\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class HomeController
{
    public function index(Request $request, Response $response): Response
    {
        $view = \Slim\Views\Twig::fromRequest($request);
        return $view->render($response, 'home.twig');
    }
}
