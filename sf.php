<?php

namespace Hotmind\TodoBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

class DefaultController extends Controller {

    public function listAction() {
        $returns = [
            'status' => false,
            'todo' => [],
            'tasks' => [],
            'dealings' => []
        ];
        
        $user = $this->getUser();
        
        $trans = $this->get('translator');
        $imagin = $this->get('imagine.cache.path.resolver');
        $avatar_default = $this->container->getParameter('avatar_default');
        
        $userData = [
            'id' => $user->getId(),
            'name' => $user->GetFullName(),
            'photo' => $user->getPhoto() ? $imagin->getBrowserPath($user->getImageUrl(), 'avatar') : $imagin->getBrowserPath($avatar_default, 'avatar')
        ];
        
        $todo = $this->getRepository()->getTodoByUser($user->getId());
        
        if($todo) {
            foreach($todo as $t) {
                $returns['todo'][] = [
                    'id' => $t->getId(),
                    'todo' => $t->getTodo(),
                    'isCompleted' => $t->getIsCompleted()
                ];
            }
        }
        
        $tasks = $this->getDoctrine()->getRepository('HotmindTasksBundle:Tasks')->getTasksByUserId($user->getId());
        
        if($tasks) {
            foreach($tasks as $task) {
                $proejct = $task->getProjectId();
                
                $returns['tasks'][] = [
                    'id' => $task->getId(),
                    'title' => $task->getTitle(),
                    'project' => [
                        'id' => $proejct->getId(),
                        'title' => $proejct->getTitle()
                    ],
                    'user' => $userData,
                    'attached' => $this->getDoctrine()->getRepository('HotmindTasksBundle:Tasks')->isAttached($task->getId(), $proejct->getId(), $user->getId())
                ];
            }
        }
        
        $dealings = $this->getDoctrine()->getRepository('HotmindDealingsBundle:Dealings')->getDealingsByUser($user->getId());
        
        if($dealings) {
            foreach($dealings as $dealing) {
                $status = $dealing->getStatusId();
                
                $returns['dealings'][] = [
                    'id' => $dealing->getId(),
                    'title' => $dealing->getTitle(),
                    'status' => [
                        'id' => $status->getId(),
                        'name' => $trans->trans($status->getTranslateCode(), array(), 'MainBundle'),
                        'color' => 'todo_' . $status->getColor()
                    ]
                ];
            }
        }
        
        $returns['status'] = true;
        
        return new JsonResponse($returns);
    }

    public function completedAction() {
        $returns = [
            'status' => false
        ];
        
        $request = json_decode($this->get('request')->getContent());
        
        if(is_numeric($request->id)) {
            $completed = $this->getRepository()->completed($request->id, $this->getUser()->getId());

            $returns['status'] = true;
            $returns['completed'] = $completed;
        }
        
        return new JsonResponse($returns);
    }
    
    public function addAction() {
        $returns = [
            'status' => false
        ];
        
        $request = json_decode($this->get('request')->getContent());
        
        if(!empty($request->todo)) {
            $todo = $this->getRepository()->add($request->todo, $this->getUser());

            if($todo) {
                $returns['status'] = true;
                $returns['todo'] = [
                    'id' => $todo->getId(),
                    'todo' => $todo->getTodo(),
                    'isCompleted' => $todo->getIsCompleted()
                ];
            }
        }
        
        return new JsonResponse($returns);
    }
    
    public function removeAction($id) {
        $returns = [
            'status' => false
        ];
        
        $todo = $this->getRepository()->remove($id, $this->getUser()->getId());

        if($todo) $returns['status'] = true;
        
        return new JsonResponse($returns);
    }
    
    public function getRepository() {
        return $this->getDoctrine()->getRepository('HotmindTodoBundle:Todo');
    }
    
}
