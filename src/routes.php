<?php
// Routes

$app->map(['GET', 'POST'], '/', \ProgressNotification\Controller\RegisterController::class . ':homeAction')->setName('homePage');

$app->post('/submit-kill', \ProgressNotification\Controller\SubmitController::class . ':submitKill')->setName('submitKill');