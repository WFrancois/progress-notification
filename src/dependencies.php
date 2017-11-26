<?php

$container = $app->getContainer();

$container['view'] = function ($c) {
    $view = new \Slim\Views\Twig(__DIR__ . '/../templates', [
        'cache' => false
    ]);

    // Instantiate and add Slim specific extension
    $basePath = rtrim(str_ireplace('index.php', '', $c['request']->getUri()->getBasePath()), '/');
    $view->addExtension(new Slim\Views\TwigExtension($c['router'], $basePath));

    $view->getEnvironment()->addGlobal('session', $_SESSION);
    $view->getEnvironment()->addGlobal('currentUrl', $c['request']->getUri()->getPath());

    return $view;
};

$config = \json_decode(file_get_contents(__DIR__ . '/config.json'), true);

new \ProgressNotification\Service\PDO($config['pdo']);