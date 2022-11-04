<?php

namespace App\Controller;

use App\Entity\Station;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class ApiRadioController extends AbstractController
{

    public ManagerRegistry $doctrine;

    public function __construct(
        ManagerRegistry $doctrine,
    )
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @Route("/api/get-stations", name="api_get_stations")
     */
    public function apiGetStations(Request $request): JsonResponse
    {
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);

        $entityManager = $this->doctrine->getManager();
        $country = $request->query->get('country');
        $style = $request->query->get('style');
        $top = $request->query->getBoolean('top');
        $title = $request->query->get('title');
        $recent = $request->query->getBoolean('recent');
        if ($style && $country && $top && !$title) {
            $entity = $entityManager->getRepository(Station::class)->findBy(
                ['style' => $style, 'country' => $country, 'top' => $top,],
                ['ordering' => 'asc']
            );
        } else if ($title) {
            $qb = $entityManager->getRepository(
                Station::class)->createQueryBuilder('u')->where('u.title like :radioTitle');
            if ($top) {
                $qb->andWhere('u.top = 1');
            }
            if ($country) {
                $qb->andWhere('u.country like :radioCountry');
                $qb->setParameter('radioCountry', '%' . $country . '%');
            }
            if ($style) {
                $qb->andWhere('u.style like :radioStyle');
                $qb->setParameter('radioStyle', '%' . $style . '%');
            }
            $qb->setParameter('radioTitle', '%' . $title . '%');
            $entity = $qb->getQuery()->getResult();
        } else if ($style && $country) {
            $entity = $entityManager->getRepository(Station::class)->findBy(
                ['style' => $style, 'country' => $country]
            );
        } else if ($country && $top) {
            $entity = $entityManager->getRepository(Station::class)->findBy(
                ['country' => $country, 'top' => $top,],
                ['ordering' => 'asc']
            );
        } else if ($style && $top) {
            $entity = $entityManager->getRepository(Station::class)->findBy(
                ['style' => $style, 'top' => $top,]
            );
        } else if ($country) {
            $entity = $entityManager->getRepository(Station::class)->findBy(
                ['country' => $country]
            );
        } else if ($style) {
            $entity = $entityManager->getRepository(Station::class)->findBy(
                ['style' => $style]
            );
        } else if ($top) {
            $entity = $entityManager->getRepository(Station::class)->findBy(
                ['top' => $top]
            );
        } else if ($recent) {
            $entity = $entityManager->getRepository(Station::class)->findBy(
                [],
                ['createdAt' => 'desc']
            );
            $entity = array_slice($entity, 0, 20 );
        } else {
            $entity = $entityManager->getRepository(Station::class)->findAll();
        }

        return new JsonResponse($serializer->serialize(
            $entity,
            'json',
            [AbstractNormalizer::IGNORED_ATTRIBUTES => ['createdAt', 'lastChecked', 'status']]),
            200,
            [],
            true);
    }
}