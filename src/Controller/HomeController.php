<?php

namespace App\Controller;

use App\Entity\Admin\Messages;
use App\Entity\Admin\Reservation;
use App\Entity\Companies;
use App\Form\Admin\ReservationType;
use App\Repository\Admin\CarImageRepository;
use App\Repository\Admin\CarRepository;
use App\Repository\Admin\CommentRepository;
use App\Repository\ImageRepository;
use Symfony\Component\Mailer\Bridge\Google\Smtp\GmailTransport;
use Symfony\Component\Mailer\Mailer;
use App\Form\Admin\MessagesType;
use App\Repository\CompaniesRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\SettingRepository;

class HomeController extends AbstractController
{
    /**
     * @Route("/", name="home")
     */
    public function index(SettingRepository $settingRepository, CompaniesRepository $companiesRepository, ImageRepository $imageRepository, CarRepository $carRepository)
    {
        $setting = $settingRepository->findAll();
        $company = $companiesRepository->findOneBy(['id'=>10]);
        $slider = $imageRepository->findBy(['company'=>10]);
        $car = $carRepository->findAll();

        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'setting' => $setting,
            'slider' => $slider,
            'company' => $company,
            'cars'=> $car
            ]);
    }

    /**
     * @Route("/about", name="home_about")
     */
    public function about(SettingRepository $settingRepository)
    {
        $setting = $settingRepository->findAll();
        return $this->render('home/about.html.twig', [
            'setting' => $setting
        ]);
    }

    /**
     * @Route("/detail/{id}", name="home_car_detail")
     */
    public function cardetail(SettingRepository $settingRepository, $id, CarImageRepository $carImageRepository, CarRepository $carRepository, CommentRepository $commentRepository)
    {
        $car = $carRepository->findBy(['id'=>$id]);
        $image = $carImageRepository->findBy(['carid'=>$id]);
        $comment = $commentRepository->findBy(['carid'=>$id, 'status'=>'True']);
        $setting = $settingRepository->findAll();
        return $this->render('home/cardetail.html.twig', [
            'setting' => $setting,
            'images' => $image,
            'car' => $car,
            'comment' => $comment,
        ]);
    }


    /**
     * @Route("/carshow", name="home_car_list", methods={"GET","POST"})
     */
    public function carlist(Request $request, SettingRepository $settingRepository, CarRepository $carRepository, ImageRepository $imageRepository, CommentRepository $commentRepository) : Response
    {
        $comment = $commentRepository->findBy(['companyid'=>'10', 'status'=>'True']);
        $images = $imageRepository->findBy(['company'=>'10']);
        $cars = $carRepository->findBy(['companyid'=>'10', 'status'=>'True']);
        $setting = $settingRepository->findAll();

        $checkin = $_REQUEST["checkin"];
        $departure = $_REQUEST["departure"];
        $arrival = $_REQUEST["arrival"];
        $time = $_REQUEST["usr_time"];

        $data["checkin"] = $checkin;
        $data["departure"] = $departure;
        $data["arrival"] = $arrival;
        $data["time"] = $time;



        return $this->render('home/carshow.html.twig', [
            'setting' => $setting,
            'cars'=>$cars,
            'comment'=>$comment,
            'company'=>$images,
            'data'=>$data

        ]);
    }

    /**
     * @Route("/contact", name="home_contact", methods={"GET","POST"})
     */
    public function contact(SettingRepository $settingRepository, Request $request)
    {
        $setting = $settingRepository->findAll();
        $message = new Messages();
        $form = $this->createForm(MessagesType::class, $message);
        $form->handleRequest($request);
        $submittedToken = $request->get('token');
        if ($form->isSubmitted()) {
            if ($this->isCsrfTokenValid('form-message', $submittedToken)) {
            $entityManager = $this->getDoctrine()->getManager();
            $message->setStatus('New');
            $message->setIp($_SERVER['REMOTE_ADDR']);
            $entityManager->persist($message);
            $entityManager->flush();
            $this->addFlash('success', 'Mesaj gönderildi');

                $email = (new Email())
                    ->from($setting[0]->getSmtpemail())
                    ->to($form['email']->getData())
                    //->cc('cc@example.com')
                    //->bcc('bcc@example.com')
                    //->replyTo('fabien@example.com')
                    //->priority(Email::PRIORITY_HIGH)
                    ->subject('Car reserv Company Your request!')
                    ->text('Sending emails is fun again!')
                    ->html("Dear ". $form['name']->getData(). "<br>
                            <p> We will the evaluate your request and contact you as soon as possible </p>
                            Thank You <br>
                            =============================================================
                            <br>". $setting[0]->getCompany(). " <br>
                            Adress : ". $setting[0]->getAddress(). "<br>
                            Phone  :" . $setting[0]->getPhone(). "<br>"
                    );

                $transport = new GmailTransport($setting[0]->getSmtpemail(), $setting[0]->getSmtppassword());
                $mailer = new Mailer($transport);
                $mailer->send($email);
            return $this->redirectToRoute('home_contact');
            }
        }

        $setting = $settingRepository->findAll();
        return $this->render('home/contact.html.twig', [
            'setting' => $setting,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/companies/{id}", name="home_show", methods={"GET"})
     */
    public function show(CarRepository $carRepository, SettingRepository $settingRepository, $id,ImageRepository $imageRepository, Companies $companies, CommentRepository $commentRepository): Response
    {
        $cars = $carRepository->findBy(['companyid'=>$id, 'status'=>'True']);
        $comment = $commentRepository->findBy(['companyid'=>$id, 'status'=>'True']);
        $images = $imageRepository->findBy(['company'=>$id]);
        $setting = $settingRepository->findAll();
        return $this->render('home/companyshow.html.twig', [
            'company' => $images,
            'setting' => $setting,
            'companies'=>$companies,
            'comment'=>$comment,
            'cars'=>$cars,
        ]);
    }

    /**
     * @Route("/reservation/{hid}/{cid}", name="home_reservation_new", methods={"GET","POST"})
     */
    public function newreservation(Request $request, $hid, $cid, SettingRepository $settingRepository, CompaniesRepository $companiesRepository, CarRepository $carRepository): Response
    {
        $company = $companiesRepository->findOneBy(['id'=>$hid]);
        $car = $carRepository->findOneBy(['id'=>$cid]);

        $setting = $settingRepository->findAll();

        $reservation = new Reservation();
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        $submittedToken = $request->get('token');
        if ($form->isSubmitted()) {
            if ($this->isCsrfTokenValid('form-reservation', $submittedToken)) {



                return $this->redirectToRoute('user_reservations');
            }
        }

        return $this->render('home/companyshow.html.twig', [
            'reservation' => $reservation,
            'setting' => $setting,
            'car' => $car,
            'company' => $company,
            'form' => $form->createView(),
        ]);
    }

}
