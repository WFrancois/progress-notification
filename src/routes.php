<?php
// Routes

$app->get('/', \ProgressNotification\Controller\RegisterController::class . ':registerAction')->setName('homePage');

$app->post('/ajax/register', \ProgressNotification\Controller\RegisterController::class . ':ajaxRegister')->setName('registerAjax');

$app->post('/ajax/current-subscription', \ProgressNotification\Controller\RegisterController::class . ':ajaxGetCurrentSubscription')->setName('ajaxGetCurrentSubscription');

$app->post('/submit-kill', \ProgressNotification\Controller\SubmitController::class . ':submitKill')->setName('submitKill');