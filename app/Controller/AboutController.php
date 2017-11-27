<?php
/**
 * Created by PhpStorm.
 * User: isak
 * Date: 11/27/17
 * Time: 1:04 PM
 */

namespace ProgressNotification\Controller;


use Slim\Http\Request;
use Slim\Http\Response;

class AboutController extends BaseController
{
    public function __invoke(Request $request, Response $response)
    {
        return $this->view->render($response, 'about.html.twig');
    }
}