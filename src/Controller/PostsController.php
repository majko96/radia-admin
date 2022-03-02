<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\User;
use App\Form\PostFormType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class PostsController extends AbstractController
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
     * @Route("/posts", name="posts")
     * @IsGranted("ROLE_USER")
     */
    public function getMyPosts(Request $request, PaginatorInterface $paginator): Response
    {
        $entityManager = $this->doctrine->getManager();
        $user = $this->security->getUser();
        if (!$user) {
            $data = [];
        } else {
            $data = $entityManager->getRepository(Post::class)->findBy(['userId' => $user->getId()], ['id' => 'DESC']);
        }
        $dataPagination = $paginator->paginate(
            $data,
            $request->query->getInt('page', 1),
            6
        );

        return $this->render('posts/list.html.twig', [
            'controller_name' => 'PostsController',
            'posts' => $dataPagination,
        ]);
    }


    /**
     * @Route("/post-create", name="post_create")
     * @IsGranted("ROLE_USER")
     */
    public function createPost(Request $request): Response
    {
        /** @var User $user */
        $user = $this->security->getUser();

        $form = $this->createForm(PostFormType::class, [
        ]);
        $entityManager = $this->doctrine->getManager();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $dataFromForm = $form->getData();
            $post = new Post();
            $post->setUserId($user->getId());
            $post->setTitle($dataFromForm['title']);
            $post->setText($dataFromForm['text']);
            $post->setCreatedAt(new \DateTimeImmutable('now + 1 hour'));
            $post->setUpdatedAt(new \DateTimeImmutable('now + 1 hour'));
            $entityManager->persist($post);
            $entityManager->flush();
            return $this->redirect($this->generateUrl('posts'), 302);
        }

        return $this->render(
            'posts/create.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }



    /**
     * @Route("/post-edit/{id}", name="post_edit")
     * @IsGranted("ROLE_USER")
     */
    public function editPost(Request $request): Response
    {
        /** @var User $user */
        $user = $this->security->getUser();

        $id = $request->get('id');
        $entityManager = $this->doctrine->getManager();

        $data = $entityManager->getRepository(Post::class)->find($id);

        $form = $this->createForm(PostFormType::class, $data);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $dataFromForm = $form->getData();
            $post = new Post();
            $post->setId($dataFromForm->getId());
            $post->setUserId($user->getId());
            $post->setTitle($dataFromForm->getTitle());
            $post->setText($dataFromForm->getText());
            $post->setCreatedAt($data->getCreatedAt());
            $post->setUpdatedAt(new \DateTimeImmutable('now + 1 hour'));
            $entityManager->merge($post);
            $entityManager->flush();
            return $this->redirect($this->generateUrl('posts'), 302);
        }

        return $this->render(
            'posts/create.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }


    /**
     * @Route("/post-delete/{id}", name="post_delete")
     * @IsGranted("ROLE_USER")
     */
    public function deletePost(Request $request): Response
    {
        $id = $request->get('id');
        $entityManager = $this->doctrine->getManager();
        $entity = $entityManager->getRepository(Post::class)->find($id);
        $entityManager->remove($entity);
        $entityManager->flush();
        return $this->redirect($this->generateUrl('posts'), 302);
    }
}
