<?php

namespace App\Controller;

use App\Entity\Station;
use App\Form\RadioFormType;
use App\Form\RadioFilterFormType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class RadioController extends AbstractController
{

    public ManagerRegistry $doctrine;
    public Security $security;

    public function __construct(
        ManagerRegistry $doctrine,
        Security $security
    ) {
        $this->doctrine = $doctrine;
        $this->security = $security;
    }

    /**
     * @Route("/radio-list", name="radio_list")
     * @IsGranted("ROLE_USER")
     */
    public function radioList(Request $request,  PaginatorInterface $paginator): Response
    {
        $form = $this->createForm(RadioFilterFormType::class);
        $entityManager = $this->doctrine->getManager();
        $country = $request->query->get('country');
        $style = $request->query->get('style');
        $top = $request->query->getBoolean('top');
        $title = $request->query->get('title');
        if (($style !== 'null' && $style !== null) && ($country !== 'null' && $country !== null) && ($top) && (!$title)) {
            $entity = $entityManager->getRepository(Station::class)->findBy(['style' => $style, 'country' => $country, 'top' => $top,], ['ordering' => 'asc']);
        } 
        else if ($title) {
            $qb = $entityManager->getRepository(Station::class)->createQueryBuilder('u')->where('u.title like :radioTitle');
            if ($top) {
                $qb->andWhere('u.top = 1');
            } 
            if ($country !== 'null' && $country !== null ) {
                $qb->andWhere('u.country like :radioCountry');
                $qb->setParameter('radioCountry','%' . $country . '%');
            } 
            if ($style!== 'null' && $style !== null ) {
                $qb->andWhere('u.style like :radioStyle');
                $qb->setParameter('radioStyle','%' . $style . '%');
            } 
            $qb->setParameter('radioTitle','%' . $title . '%');
            $entity = $qb->getQuery()->getResult();
        }
        else if (($style !== 'null' && $style !== null) && ($country !== 'null' && $country !== null)) {
            $entity = $entityManager->getRepository(Station::class)->findBy(['style' => $style, 'country' => $country]);
        } 
        else if ($country !== 'null' && $country !== null && ($top)) {
            $entity = $entityManager->getRepository(Station::class)->findBy(['country' => $country, 'top' => $top,], ['ordering' => 'asc']);
        } 
        else if ($style !== 'null' && $style !== null && ($top)) {
            $entity = $entityManager->getRepository(Station::class)->findBy(['style' => $style, 'top' => $top,]);
        }
        else if ($country !== 'null' && $country !== null ) {
            $entity = $entityManager->getRepository(Station::class)->findBy(['country' => $country]);
        } 
        else if ($style !== 'null' && $style !== null ) {
            $entity = $entityManager->getRepository(Station::class)->findBy(['style' => $style]);
        }
        else if ($top) {
            $entity = $entityManager->getRepository(Station::class)->findBy(['top' => $top]);
        }
        else {
            $entity = $entityManager->getRepository(Station::class)->findAll();
        }

        $dataPagination = $paginator->paginate(
            $entity,
            $request->query->getInt('page', 1),
            50
        );

        return $this->render('posts/radioList.html.twig', [
            'controller_name' => 'PostsController',
            'posts' => $dataPagination,
            'totalCount' => $dataPagination->getTotalItemCount(),
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/radio-edit/{id}", name="radio_edit")
     * @IsGranted("ROLE_USER")
     */
    public function editRadio(Request $request): Response
    { 
        $id = $request->get('id');
        $entityManager = $this->doctrine->getManager();

        $data = $entityManager->getRepository(Station::class)->find($id);
        $form = $this->createForm(RadioFormType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirect($this->generateUrl('radio_list'), 302);
        }

        return $this->render(
            'posts/create.html.twig',
            [
                'form' => $form->createView(),
                'id' => $id
            ]
        );
    }


     /**
     * @Route("/radio-add", name="radio_add")
     * @IsGranted("ROLE_USER")
     */
    public function addRadio(Request $request): Response
    {
        $form = $this->createForm(RadioFormType::class, []);
        $entityManager = $this->doctrine->getManager();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $dataFromForm = $form->getData();
            $station = new Station();
            $station->setTitle($dataFromForm['title']);
            $station->setUrl($dataFromForm['url']);
            $station->setImg($dataFromForm['img']);
            $station->setCountry($dataFromForm['country']);
            $station->setStyle($dataFromForm['style']);
            $station->setTop($dataFromForm['top']);
            $station->setOrdering($dataFromForm['ordering']);
            $station->setCreatedAt(new \DateTimeImmutable('now', new \DateTimeZone('Europe/Bratislava')));
            $entityManager->persist($station);
            $entityManager->flush();
            return $this->redirect($this->generateUrl('radio_list'), 302);
        }

        return $this->render(
            'posts/create.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }


    /**
     * @Route("/radio-delete/{id}", name="radio_delete")
     * @IsGranted("ROLE_USER")
     */
    public function deleteRadio(Request $request): Response
    {
        $id = $request->get('id');
        $entityManager = $this->doctrine->getManager();
        $entity = $entityManager->getRepository(Station::class)->find($id);
        $entityManager->remove($entity);
        $entityManager->flush();
        return $this->redirect($this->generateUrl('radio_list'), 302);
    }


}
