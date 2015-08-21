<?php
public function lessonsTargetAction(Request $request)
{
    $returns = array('status' => false);

    if ($request->isMethod('POST') && $request->get('action')) {
        $em = $this->getDoctrine()->getManager();

        $action = $request->get('action');
        $returns['action'] = $action;

        if ($action == 'add') {
            $target = new \App\MainBundle\Entity\LessonsTarget();
        }
        if (is_numeric($request->get('id')) && $action == 'edit' || $action == 'delete') {
            $target= $em->getRepository('AppMainBundle:LessonsTarget')->find($request->get('id'));

            if ($action == 'delete') {
                $em->remove($target);
                $em->flush();

                $returns['status'] = true;

                return new JsonResponse($returns);
            }
        }
        if (isset($target) && $target) {
            $target->setTitle($request->get('title'));

            $em->persist($target);

            $em->flush();

            $returns['target'] = $target;
        }
    }

    return $this->render('AppAdminBundle:Settings/LessonsTargets:row.html.twig', $returns);
}
