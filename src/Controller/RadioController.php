<?php

namespace App\Controller;

use App\Entity\Station;
use App\Form\RadioFormType;
use App\Form\RadioFilterFormType;
use Exception;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
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
    private const DEFAULT_PER_PAGE = 50;
    private const ROLE_ADMIN = 'ROLE_ADMIN';

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
        if ($style && $country && $top && !$title) {
            $entity = $entityManager->getRepository(Station::class)->findBy(
                ['style' => $style, 'country' => $country, 'top' => $top,],
                ['ordering' => 'asc']
            );
        } 
        else if ($title) {
            $qb = $entityManager->getRepository(
                Station::class)->createQueryBuilder('u')->where('u.title like :radioTitle');
            if ($top) {
                $qb->andWhere('u.top = 1');
            } 
            if ($country) {
                $qb->andWhere('u.country like :radioCountry');
                $qb->setParameter('radioCountry','%' . $country . '%');
            } 
            if ($style){
                $qb->andWhere('u.style like :radioStyle');
                $qb->setParameter('radioStyle','%' . $style . '%');
            } 
            $qb->setParameter('radioTitle','%' . $title . '%');
            $entity = $qb->getQuery()->getResult();
        }
        else if ($style && $country) {
            $entity = $entityManager->getRepository(Station::class)->findBy(
                ['style' => $style, 'country' => $country]
            );
        } 
        else if ($country && $top) {
            $entity = $entityManager->getRepository(Station::class)->findBy(
                ['country' => $country, 'top' => $top,],
                ['ordering' => 'asc']
            );
        } 
        else if ($style && $top) {
            $entity = $entityManager->getRepository(Station::class)->findBy(
                ['style' => $style, 'top' => $top,]
            );
        }
        else if ($country) {
            $entity = $entityManager->getRepository(Station::class)->findBy(
                ['country' => $country]
            );
        } 
        else if ($style) {
            $entity = $entityManager->getRepository(Station::class)->findBy(
                ['style' => $style]
            );
        }
        else if ($top) {
            $entity = $entityManager->getRepository(Station::class)->findBy(
                ['top' => $top]
            );
        }
        else {
            $entity = $entityManager->getRepository(Station::class)->findAll();
        }

        $dataPagination = $paginator->paginate(
            $entity,
            $request->query->getInt('page', 1),
            self::DEFAULT_PER_PAGE
        );

        return $this->render('radios/radioList.html.twig', [
            'controller_name' => 'PostsController',
            'radios' => $dataPagination,
            'totalCount' => $dataPagination->getTotalItemCount(),
            'form' => $form->createView(),
        ]);
    }


    /**
     * @Route("/radio-edit/{id}/{back}", name="radio_edit")
     * @IsGranted("ROLE_USER")
     */
    public function editRadio(Request $request, $back): Response
    {
        $isGranted = false;
        if (in_array(self::ROLE_ADMIN, $this->getUser()->getRoles(), true)) {
            $isGranted = true;
        }

        $id = $request->get('id');
        $entityManager = $this->doctrine->getManager();

        $data = $entityManager->getRepository(Station::class)->find($id);
        $form = $this->createForm(RadioFormType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $baseUrl = $request->getSchemeAndHttpHost();
            $route = $this->generateUrl('radio_list');
            $this->addFlash('success', 'Station edited successfully.');
            return $this->redirect($baseUrl . $route . '/?' . $back);
        }

        return $this->render(
            'radios/createForm.twig',
            [
                'form' => $form->createView(),
                'id' => $id,
                'isGranted' => $isGranted
            ]
        );
    }


    /**
     * @Route("/radio-add", name="radio_add")
     * @IsGranted("ROLE_USER")
     * @throws Exception
     */
    public function addRadio(Request $request): Response
    {
        $isGranted = false;
        if (in_array(self::ROLE_ADMIN, $this->getUser()->getRoles(), true)) {
            $isGranted = true;
        }
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
            $station->setCreatedAt(
                    new \DateTimeImmutable('now',
                    new \DateTimeZone('Europe/Bratislava')
                ));
            $entityManager->persist($station);
            $entityManager->flush();
            $this->addFlash('success', 'Station created successfully.');
            return $this->redirect($this->generateUrl('radio_list'), 302);
        }

        return $this->render(
            'radios/createForm.twig',
            [
                'form' => $form->createView(),
                'isGranted' => $isGranted
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

    /**
     * @Route("/radio-reload", name="radio_reload")
     * @throws Exception
     */
    public function reloadRadio(Request $request): JsonResponse
    {
        $entityManager = $this->doctrine->getManager();
        $entity = $entityManager->getRepository(Station::class)->findAll();

        foreach ($entity as $station) {
            $domain = $station->getUrl();
            $curlInit = curl_init($domain);
            curl_setopt($curlInit,CURLOPT_CONNECTTIMEOUT,10);
            curl_setopt($curlInit,CURLOPT_HEADER,true);
            curl_setopt($curlInit,CURLOPT_NOBODY,true);
            curl_setopt($curlInit,CURLOPT_RETURNTRANSFER,true);
            $response = curl_exec($curlInit);

            if ($response) {
                $station->setStatus(1);
                $station->setLastChecked(
                    new \DateTimeImmutable('now',
                        new \DateTimeZone('Europe/Bratislava')
                    ));
                $entityManager->flush();
            } else {
                $station->setStatus(0);
                $station->setLastChecked(
                    new \DateTimeImmutable('now',
                        new \DateTimeZone('Europe/Bratislava')
                    ));
                $entityManager->flush();
            }
        }
        return new JsonResponse(true, 200, []);
    }

    /**
     * @Route("/radio-reload-single/{id}", name="radio_reload_single")
     * @throws Exception
     */
    public function reloadRadioSingle(Request $request): JsonResponse
    {
        $entityManager = $this->doctrine->getManager();
        $id = $request->get('id');
        $station = $entityManager->getRepository(Station::class)->findBy(['id' => $id])[0];

        $domain = $station->getUrl();
        $curlInit = curl_init($domain);
        curl_setopt($curlInit,CURLOPT_CONNECTTIMEOUT,10);
        curl_setopt($curlInit,CURLOPT_HEADER,true);
        curl_setopt($curlInit,CURLOPT_NOBODY,true);
        curl_setopt($curlInit,CURLOPT_RETURNTRANSFER,true);
        $response = curl_exec($curlInit);

        if ($response) {
            $station->setStatus(1);
            $station->setLastChecked(
                new \DateTimeImmutable('now',
                    new \DateTimeZone('Europe/Bratislava')
                ));
            $entityManager->flush();
        } else {
            $station->setStatus(0);
            $station->setLastChecked(
                new \DateTimeImmutable('now',
                    new \DateTimeZone('Europe/Bratislava')
                ));
            $entityManager->flush();
        }
        return new JsonResponse(true, 200, []);
    }
}
