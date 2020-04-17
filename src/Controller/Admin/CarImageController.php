<?php

namespace App\Controller\Admin;

use App\Entity\Admin\CarImage;
use App\Form\Admin\CarImageType;
use App\Repository\Admin\CarImageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/car/image")
 */
class CarImageController extends AbstractController
{
    /**
     * @Route("/", name="admin_car_image_index", methods={"GET"})
     */
    public function index(CarImageRepository $carImageRepository): Response
    {
        return $this->render('admin/car_image/index.html.twig', [
            'car_images' => $carImageRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new/{id}", name="admin_car_image_new", methods={"GET","POST"})
     */
    public function new(Request $request, $id, CarImageRepository $carImageRepository): Response
    {
        $carImage = new CarImage();
        $form = $this->createForm(CarImageType::class, $carImage);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $entityManager = $this->getDoctrine()->getManager();

            $file = $form['image']->getData();

            // this condition is needed because the 'brochure' field is not required
            // so the PDF file must be processed only when a file is uploaded
            if ($file) {
                $filename = $this->generateUniqueFileName() . '.' . $file->guessExtension();
                try {
                    $file->move(
                        $this->getParameter('images_directory'),
                        $filename
                    );
                } catch (FileException $e) {

                }

                $carImage->setImage($filename);
                $entityManager->persist($carImage);
                $entityManager->flush();

                return $this->redirectToRoute('admin_car_image_new', ['id'=>$id]);
            }
        }

        $images = $carImageRepository->findBy(['carid'=>$id]);

        return $this->render('admin/car_image/new.html.twig', [
            'car_images' => $images,
            'carid' => $id,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="admin_car_image_show", methods={"GET"})
     */
    public function show(CarImage $carImage): Response
    {
        return $this->render('admin/car_image/show.html.twig', [
            'car_image' => $carImage,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="admin_car_image_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, CarImage $carImage): Response
    {
        $form = $this->createForm(CarImageType::class, $carImage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('admin_car_image_index');
        }

        return $this->render('admin/car_image/edit.html.twig', [
            'car_image' => $carImage,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/{cid}", name="admin_car_image_delete", methods={"DELETE"})
     */
    public function delete(Request $request, CarImage $carImage, $cid): Response
    {
        if ($this->isCsrfTokenValid('delete'.$carImage->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($carImage);
            $entityManager->flush();
        }

        return $this->redirectToRoute('admin_car_image_new', ['id'=>$cid]);
    }

    /**
     * @return string
     */
    private function generateUniqueFileName()
    {
        return md5(uniqid());
    }
}
