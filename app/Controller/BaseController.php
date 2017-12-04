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

    /** @var  Container */
    protected $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->view = $container->view;
        $this->router = $container->router;
    }
}