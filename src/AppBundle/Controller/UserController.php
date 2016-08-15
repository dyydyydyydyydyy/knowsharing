<?php

namespace AppBundle\Controller;

use AppBundle\Controller\BaseController;
use Symfony\Component\HttpFoundation\Request;
use Biz\User\Impl\UserServiceImpl;
use Symfony\Component\HttpFoundation\JsonResponse;
use Topxia\Common\ArrayToolKit;
use AppBundle\Common\Paginator;

class UserController extends BaseController
{
    public function indexAction(Request $request,$id)
    {
        $user = $this->getUserService()->getUser($id);
        $hasfollowed = $this->getUserService()->getFollowUserByUserIdAndObjectUserId(1,$id);
        $conditions = array(
            'userId' => $user['id']
        );
        $knowledgesCount = $this->getKnowledgeService()->getKnowledgesCount($conditions);
        $favoritesCount = $this->getFavoriteService()->getFavoritesCount($conditions);

        $knowledges = $this->getKnowledgeService()->findKnowledgesByUserId($user['id']);
        $knowledges = $this->getFavoriteService()->hasFavoritedKnowledge($knowledges,$id);
        $knowledges = $this->getLikeService()->haslikedKnowledge($knowledges,$id);

        return $this->render('AppBundle:User:index.html.twig', array(
            'user' => $user,
            'knowledgesCount' => $knowledgesCount,
            'favoritesCount' => $favoritesCount,
            'hasfollowed' => $hasfollowed,
            'knowledges' => $knowledges
        ));
    }

    public function listFavoritesAction(Request $request, $userId)
    {
        $userId = 1;
        $user = $this->getUserService()->getUser(1);
        $conditions = array(
            'userId' => $user['id']
        );

        $favorites = $this->getFavoriteService()->findFavoritesByUserId($userId);
        $knowledgeIds = ArrayToolKit::column($favorites,'knowledgeId');
        $knowledges = $this->getKnowledgeService()->findKnowledgesByKnowledgeIds($knowledgeIds);

        $users = $this->getUserService()->findUsersByIds(ArrayToolKit::column($knowledges, 'userId'));
        $users = ArrayToolKit::index($users, 'id');

        $hasfollowed = $this->getUserService()->getFollowUserByUserIdAndObjectUserId(1,$userId);
        $knowledgesCount = $this->getKnowledgeService()->getKnowledgesCount($conditions);
        $favoritesCount = $this->getFavoriteService()->getFavoritesCount($conditions);

        return $this->render('AppBundle:User:favorite.html.twig', array(
            'users' => $users,
            'user' => $user,
            'knowledgesCount' => $knowledgesCount,
            'favoritesCount' => $favoritesCount,
            'hasfollowed' => $hasfollowed,
            'knowledges' => $knowledges
        ));
    }

    public function myFavoritesAction(Request $request)
    {
        $userId = 1;
        $favorites = $this->getFavoriteService()->findFavoritesByUserId($userId);
        $knowledgeIds = ArrayToolKit::column($favorites,'knowledgeId');

        $conditions = array('knowledgeIds' => $knowledgeIds);
        $orderBy = array('createdTime', 'DESC');
        $paginator = new Paginator(
            $this->get('request'),
            $this->getKnowledgeService()->getKnowledgesCount($conditions),
            2
        );
        $knowledges = $this->getKnowledgeService()->searchKnowledges(
            $conditions,
            $orderBy,
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        $users = $this->getUserService()->findUsersByIds(ArrayToolKit::column($knowledges, 'userId'));
        $users = ArrayToolKit::index($users, 'id');

        return $this->render('AppBundle:MyKnowledgeShare:my-favorites.html.twig', array(
            'knowledges' => $knowledges,
            'users' => $users,
            'paginator' => $paginator
        ));
    }

    public function listFollowsAction(Request $request, $userId, $type)
    {
        $userId = 1;//传过来的用户
        $myUserId = 2;//当前登录用户
        $user = $this->getUserService()->getUser($userId);
        $conditions = array(
            'userId' => $user['id']
        );
        $knowledgesCount = $this->getKnowledgeService()->getKnowledgesCount($conditions);
        $favoritesCount = $this->getFavoriteService()->getFavoritesCount($conditions);
        $hasfollowed = $this->getUserService()->getFollowUserByUserIdAndObjectUserId($myUserId,$userId);

        $follows = $this->getUserService()->searchMyFollowsByUserIdAndType($userId, $type);
        $objectIds = ArrayToolKit::column($follows,'objectId');
        if ($type == 'user') {
            $objects = $this->getUserService()->findUsersByIds($objectIds);
        } elseif ($type == 'topic') {
            $objects = $this->getTopicService()->findTopicsByIds($objectIds);
            $objects = $this->getFollowTopicService()->hasFollowTopics($objects,$myUserId);
        }

        return $this->render('AppBundle:User:follows.html.twig', array(
            'objects' => $objects,
            'type' => $type,
            'knowledgesCount' => $knowledgesCount,
            'favoritesCount' => $favoritesCount,
            'hasfollowed' => $hasfollowed,
            'user' => $user
        ));
    }

    public function myFollowsAction(Request $request, $type)
    {
        $userId = 1;
        $myFollows = $this->getUserService()->searchMyFollowsByUserIdAndType($userId, $type);
        $objectIds = ArrayToolKit::column($myFollows,'objectId');
        if ($type == 'user') {
            $objects = $this->getUserService()->findUsersByIds($objectIds);
        } elseif ($type == 'topic') {
            $objects = $this->getTopicService()->findTopicsByIds($objectIds);
            $objects = $this->getFollowTopicService()->hasFollowTopics($objects,$userId);
        }

        return $this->render('AppBundle:MyKnowledgeShare:my-follows.html.twig', array(
            'objects' => $objects,
            'type' => $type
        ));
    }

    public function followAction(Request $request, $id)
    {
        $this->getUserService()->followUser($id);

        return new JsonResponse(true);
    }

    public function unfollowAction(Request $request, $id)
    {
        $this->getUserService()->unfollowUser($id);

        return new JsonResponse(true);
    }

    public function createToreadAction(Request $request, $id)
    {
        $this->getToreadService()->createToreadKnowledge($id);

        return new JsonResponse(true);
    }

    public function deleteToreadAction(Request $request, $id)
    {
        $this->getToreadService()->deleteToreadKnowledge($id);

        return new JsonResponse(true);
    }

    protected function getKnowledgeService()
    {
        return $this->biz['knowledge_service'];
    }

    protected function getTopicService()
    {
        return $this->biz['topic_service'];
    }

    protected function getFavoriteService()
    {
        return $this->biz['favorite_service'];
    }

    protected function getUserService()
    {
        return $this->biz['user_service'];
    }

    protected function getLikeService()
    {
        return $this->biz['like_service'];
    }

    protected function getToreadService()
    {
        return $this->biz['toread_service'];
    }

    protected function getFollowTopicService()
    {
        return $this->biz['follow_topic_service'];
    }
}