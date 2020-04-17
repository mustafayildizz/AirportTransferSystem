<?php

namespace App\Controller;

use App\Entity\Image;
use App\Form\Image1Type;
use App\Form\ImageType;
use App\Repository\ImageRepository;
use App\Repository\SettingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/user/image")
 */
class ImageController extends AbstractController
{
    /**
     * @Route("/", name="user_image_index", methods={"GET"})
     */
    public function index(ImageRepository $imageRepository, SettingRepository $settingRepository): Response
    {

        $setting = $settingRepository->findAll();
        return $this->render('image/index.html.twig', [
            'images' => $imageRepository->findAll(),
            'setting' => $setting
        ]);
    }

    /**
     * @Route("/new/{id}", name="user_image_new", methods={"GET","POST"})
     */
    public function new(Request $request, $id, ImageRepository $imageRepository): Response
    {
        $image = new Image();
        $form = $this->createForm(ImageType::class, $image);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();

            $file = $form['image']->getData();

            // this condition is needed because the 'brochure' field is not required
            // so the PDF file must be processed only when a file is uploaded
            if ($file) {
                $filename = $this->generateUniqueFileName(). '.' . $file->guessExtension();
                try {
                    $file->move(
                        $this->getParameter('images_directory'),
                        $filename
                    );
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }

                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents

                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents
                $image->setImage($filename);


                $entityManager->persist($image);
                $entityManager->flush();

                return $this->redirectToRoute('admin_image_new', ['id' => $id]);
            }
        }

        $images = $imageRepository->findBy(['company'=>$id]);

        return $this->render('image/new.html.twig', [
            'image' => $image,
            'id' => $id,
            'images' => $images,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="user_image_show", methods={"GET"})
     */
    public function show(Image $image): Response
    {
        return $this->render('image/show.html.twig', [
            'image' => $image,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="user_image_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Image $image): Response
    {
        $form = $this->createForm(Image1Type::class, $image);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('user_image_index');
        }

        return $this->render('image/edit.html.twig', [
            'image' => $image,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="user_image_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Image $image): Response
    {
        if ($this->isCsrfTokenValid('delete'.$image->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($image);
            $entityManager->flush();
        }

        return $this->redirectToRoute('user_image_index');
    }

    /**
     * @return string
     */
    private function generateUniqueFileName()
    {
        return md5(uniqid());
    }
}
