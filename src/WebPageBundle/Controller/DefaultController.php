<?php

namespace WebPageBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use WebPageBundle\Lib\api;


class DefaultController extends Controller
{

    public function generateAccessTokenAction()
    {

        $t = md5(uniqid(rand(), true));

        return new JsonResponse(array('access_token'=> $t));

    }

}
