<?php

namespace App\Controller;

use App\Entity\Companies;
use App\Form\Companies1Type;
use App\Form\CompaniesType;
use App\Repository\CompaniesRepository;
use App\Repository\SettingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/user/companies")
 */
class CompaniesController extends AbstractController
{
    /**
     * @Route("/", name="user_companies_index", methods={"GET"})
     */
    public function index(CompaniesRepository $companiesRepository,  SettingRepository $settingRepository): Response
    {
        $setting = $settingRepository->findAll();
        $user = $this->getUser();
        return $this->render('companies/index.html.twig', [
            'companies' => $companiesRepository->findBy(['userid'=>$user->getId()]),
            'setting' => $setting
            ]);
    }

    /**
     * @Route("/new", name="user_companies_new", methods={"GET","POST"})
     */
    public function new(Request $request, SettingRepository $settingRepository): Response
    {
        $setting = $settingRepository->findAll();
        $company = new Companies();
        $form = $this->createForm(CompaniesType::class, $company);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $user = $this->getUser();
            $company->setUserid($user->getId());
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
            $entityManager->persist($company);
            $entityManager->flush();

            return $this->redirectToRoute('user_companies_index');
        }

        return $this->render('companies/new.html.twig', [
            'company' => $company,
            'form' => $form->createView(),
            'setting' => $setting
        ]);
    }

    /**
     * @Route("/{id}", name="user_companies_show", methods={"GET"})
     */
    public function show(Companies $company, SettingRepository $settingRepository, $id,CompaniesRepository $companiesRepository): Response
    {
        $setting = $settingRepository->findAll();
        return $this->render('home/companyshow.html.twig', [
            'company' => $companiesRepository->findBy(['id'=>$id]),
            'setting' => $setting
        ]);
    }

    /**
     * @Route("/{id}/edit", name="user_companies_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Companies $company,  SettingRepository $settingRepository): Response
    {
        $setting = $settingRepository->findAll();
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

            return $this->redirectToRoute('user_companies_index');
        }

        return $this->render('companies/edit.html.twig', [
            'company' => $company,
            'form' => $form->createView(),
            'setting' => $setting
        ]);
    }

    /**
     * @Route("/{id}", name="user_companies_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Companies $company): Response
    {
        if ($this->isCsrfTokenValid('delete'.$company->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($company);
            $entityManager->flush();
        }

        return $this->redirectToRoute('user_companies_index');
    }


    /**
     * @return string
     */
    private function generateUniqueFileName()
    {
        return md5(uniqid());
    }
}
