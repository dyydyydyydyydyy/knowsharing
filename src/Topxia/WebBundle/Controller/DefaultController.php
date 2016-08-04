<?php

namespace Topxia\WebBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Topxia\Service\User\Impl\UserServiceImpl;

class DefaultController extends Controller
{
    public function indexAction(Request $request)
    {

        $user = $this->getUserService()->getUser(2);
        return $this->render('TopxiaWebBundle:Default:index.html.twig',array('user'=> $user));
    }
    public function getUserService()
    {
        $container = $this->container->get('biz_kernel');
        return $container['user_service'];
    }
}