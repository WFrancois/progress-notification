<?php

namespace ProgressNotification\Controller;


use Slim\Container;
use Slim\Router;
use Slim\Views\Twig;

class BaseController
{
    /** @var Twig */
    protected $view;

    /** @var Router */
    protected $router;

    public function __construct(Container $container)
    {
        $this->view = $container->view;
        $this->router = $container->router;
    }
}