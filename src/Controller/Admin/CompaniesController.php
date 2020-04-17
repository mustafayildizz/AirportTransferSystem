<?php

namespace App\Controller\Admin;

use App\Entity\Companies;
use App\Form\CompaniesType;
use App\Repository\CompaniesRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("admin/companies")
 */
class CompaniesController extends AbstractController
{
    /**
     * @Route("/", name="admin_companies_index", methods={"GET"})
     */
    public function index(CompaniesRepository $companiesRepository): Response
    {
        return $this->render('admin/companies/index.html.twig', [
            'companies' => $companiesRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="admin_companies_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $companies = new Companies();
        $form = $this->createForm(CompaniesType::class, $companies);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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
                $companies->setImage($filename);
            }
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($companies);
            $entityManager->flush();

            return $this->redirectToRoute('admin_companies_index');
        }

        return $this->render('admin/companies/new.html.twig', [
            'company' => $companies,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="admin_companies_show", methods={"GET"})
     */
    public function show(Companies $company): Response
    {
        return $this->render('admin/companies/show.html.twig', [
            'company' => $company,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="admin_companies_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Companies $company): Response
    {
        $form = $this->createForm(CompaniesType::class, $company);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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
                $company->setImage($filename);
            }
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('admin_companies_index');
        }

        return $this->render('admin/companies/edit.html.twig', [
            'company' => $company,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="admin_companies_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Companies $company): Response
    {
        if ($this->isCsrfTokenValid('delete'.$company->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($company);
            $entityManager->flush();
        }

        return $this->redirectToRoute('admin_companies_index');
    }


    /**
     * @return string
     */
    private function generateUniqueFileName()
    {
        return md5(uniqid());
    }
}
