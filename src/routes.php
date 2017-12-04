<?php
// Routes

$app->get('/', \ProgressNotification\Controller\RegisterController::class . ':registerAction')->setName('homePage');

$app->get('/stream', \ProgressNotification\Controller\RegisterController::class . ':registerAction')->setName('streamPage');

$app->get('/stream/declined', \ProgressNotification\Controller\StreamlabController::class . ':errorOauth')->setName('streamErrorPage');

$app->get('/streamlabs/oauth', \ProgressNotification\Controller\StreamlabController::class . ':confirmCode')->setName('streamlabsRedirect');

$app->post('/ajax/register', \ProgressNotification\Controller\RegisterController::class . ':ajaxRegister')->setName('registerAjax');

$app->post('/ajax/current-subscription', \ProgressNotification\Controller\RegisterController::class . ':ajaxGetCurrentSubscription')->setName('ajaxGetCurrentSubscription');

$app->post('/submit-kill', \ProgressNotification\Controller\SubmitController::class . ':submitKill')->setName('submitKill');