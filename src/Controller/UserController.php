<?php

namespace App\Controller;

use App\Entity\Admin\Comment;
use App\Entity\Admin\Reservation;
use App\Entity\User;
use App\Form\Admin\CommentType;
use App\Form\Admin\ReservationType;
use App\Form\UserType;
use App\Repository\Admin\CarRepository;
use App\Repository\Admin\CommentRepository;
use App\Repository\ReservationRepository;
use App\Repository\CompaniesRepository;
use App\Repository\SettingRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Constraints\Date;

/**
 * @Route("/user")
 */
class UserController extends AbstractController
{
    /**
     * @Route("/", name="user_index", methods={"GET"})
     */
    public function index(UserRepository $userRepository, SettingRepository $settingRepository): Response
    {
        $setting = $settingRepository->findAll();
        return $this->render('user/show.html.twig', [
            'users' => $userRepository->findAll(),
            'setting' => $setting
        ]);
    }

    /**
     * @Route("/reservations", name="user_reservations", methods={"GET"})
     */
    public function reservation(SettingRepository $settingRepository, ReservationRepository $reservationRepository): Response
    {
        $user = $this->getUser();
        $reservation = $reservationRepository->getReservation($user->getId());
        $setting = $settingRepository->findAll();
        return $this->render('user/reservations.html.twig',[
            'setting' => $setting,
            'reservations'=> $reservation
        ]);
    }

    /**
     * @Route("/reservations/{id}", name="user_reservations_show", methods={"GET"})
     */
    public function reservationshow(SettingRepository $settingRepository, $id, ReservationRepository $reservationRepository): Response
    {
        $user = $this->getUser();
        $id = $user->getId();
        $reservation = $reservationRepository->getReservation($id);
        $setting = $settingRepository->findAll();
        return $this->render('user/reservation_show.html.twig',[
            'setting' => $setting,
            'reservation'=> $reservation
        ]);
    }

    /**
     * @Route("/comments", name="user_comments", methods={"GET"})
     */
    public function comments(UserRepository $userRepository, SettingRepository $settingRepository, CommentRepository $commentRepository): Response
    {
        $user = $this->getUser();
        $comment = $commentRepository->findBy(['userid'=>$user->getId()]);
        $setting = $settingRepository->findAll();
        return $this->render('user/comments.html.twig',[
            'setting' => $setting,
            'comments'=>$comment
            ]);
    }

    /**
     * @Route("/new", name="user_new", methods={"GET","POST"})
     */
    public function new(Request $request,  UserPasswordEncoderInterface $passwordEncoder): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
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
                $user->setImage($filename);
            }
            $user->setPassword(
                $passwordEncoder->encodePassword(
                    $user,
                    $form->get('password')->getData()
                )
            );

            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('user_index');
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }
//
//    /**
//     * @Route("/reservation/10/{cid}", name="user_reservation_new", methods={"GET","POST"})
//     */
//    public function newreservation(Request $request, $cid, SettingRepository $settingRepository, CompaniesRepository $companiesRepository, CarRepository $carRepository): Response
//    {
//        $company = $companiesRepository->findOneBy(['id'=>'10']);
//        $car = $carRepository->findOneBy(['id'=>$cid]);
//
//        $days = $_REQUEST["days"];
//        $chekcin = $_REQUEST["checkin"];
//        $checkout = Date("Y-m-d H:i:s", strtotime($chekcin ." $days day"));
//        $checkin = Date("Y-m-d H:i:s", strtotime($chekcin ." 0 day"));
//
//        $data["total"] = $days * $car->getPrice();
//        $data["days"] = $days;
//        $data["checkin"] = $chekcin;
//        $data["checkout"] = $checkout;
//
//        $setting = $settingRepository->findAll();
//
//        $reservation = new Reservation();
//        $form = $this->createForm(ReservationType::class, $reservation);
//        $form->handleRequest($request);
//
//        $submittedToken = $request->get('token');
//        if ($form->isSubmitted()) {
//            if ($this->isCsrfTokenValid('form-reservation', $submittedToken)) {
//
//                $checkin = date_create_from_format("Y-m-d H:i:s", $checkin);
//                $checkout = date_create_from_format("Y-m-d H:i:s", $checkout);
//
//                $reservation->setCheckin($checkin);
//                $reservation->setCheckout($checkout);
//                $reservation->setStatus('New');
//                $reservation->setIp($_SERVER['REMOTE_ADDR']);
//          //      $reservation->setCompanyid($hid);
//                $reservation->setCarid($cid);
//                $user = $this->getUser();
//                $reservation->setUserid($user->getId());
//                $reservation->setDays($days);
//                $reservation->setTotal($data["total"]);
//                $reservation->setCreatedAt(new \DateTime());
//                $entityManager = $this->getDoctrine()->getManager();
//                $entityManager->persist($reservation);
//                $entityManager->flush();
//
//                return $this->redirectToRoute('user_reservations');
//            }
//        }
//
//        return $this->render('user/newreservation.html.twig', [
//            'reservation' => $reservation,
//            'setting' => $setting,
//            'car' => $car,
//            'company' => $company,
//            'data' => $data,
//            'form' => $form->createView(),
//        ]);
//    }


    /**
     * @Route("/reservation/10/{cid}", name="user_reservation_deneme", methods={"GET","POST"})
     */
    public function newreservationdeneme(Request $request, $cid, SettingRepository $settingRepository, CompaniesRepository $companiesRepository, CarRepository $carRepository): Response
    {
        $company = $companiesRepository->findOneBy(['id'=>'10']);
        $car = $carRepository->findOneBy(['id'=>$cid]);

        $checkin = $_REQUEST["checkin"];
        $departure = $_REQUEST["departure"];
        $arrival = $_REQUEST["arrival"];
        $time = $_REQUEST["usr_time"];

        $checkin = Date("Y-m-d", strtotime($checkin .""));
        $data["checkin"] = $checkin;
        $data["departure"] = $departure;
        $data["arrival"] = $arrival;
        $data["time"] = $time;


        $setting = $settingRepository->findAll();

        $reservation = new Reservation();
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        $submittedToken = $request->get('token');
        if ($form->isSubmitted()) {
            if ($this->isCsrfTokenValid('form-reservation', $submittedToken)) {

                $checkin = date_create_from_format("Y-m-d", $checkin);


                $reservation->setCompanyid('10');
                $reservation->setCheckin($checkin);
                $reservation->setDeparture($departure);
                $reservation->setArrival($data["arrival"]);
                $reservation->setTime($time);
                $reservation->setStatus('New');
                $reservation->setIp($_SERVER['REMOTE_ADDR']);
                $reservation->setCarid($cid);
                $user = $this->getUser();
                $reservation->setUserid($user->getId());
                $reservation->setCreatedAt(new \DateTime());
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($reservation);
                $entityManager->flush();

                return $this->redirectToRoute('user_reservations');
            }
        }

        return $this->render('user/newreservationdeneme.html.twig', [
            'reservation' => $reservation,
            'setting' => $setting,
            'car' => $car,
            'data'=> $data,
            'company' => $company,
            'form' => $form->createView(),
        ]);
    }



    /**
     * @Route("/newcomment/{id}", name="user_new_comment", methods={"GET","POST"})
     */
    public function newcomment(Request $request, $id): Response
    {
        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);
        $submittedToken = $request->get('token');
        if ($form->isSubmitted()) {
            if ($this->isCsrfTokenValid('comment', $submittedToken)) {
                $entityManager = $this->getDoctrine()->getManager();

                $comment->setStatus('New');
                $comment->setIp($_SERVER['REMOTE_ADDR']);
                $comment->setCarid($id);
                $user = $this->getUser();
                $comment->setUserid($user->getId());
                $entityManager->persist($comment);
                $entityManager->flush();
                $this->addFlash('success', 'Mesaj gönderildi');
                return $this->redirectToRoute('home_car_detail', ['id' =>$id]);
            }
        }

        return $this->redirectToRoute('home_car_detail', ['id'=>$id]);
    }


    /**
     * @Route("/{id}", name="user_show", methods={"GET"})
     */
    public function show(User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="user_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, $id, User $user, SettingRepository $settingRepository,  UserPasswordEncoderInterface $passwordEncoder): Response
    {
        $setting = $settingRepository->findAll();
        $user = $this->getUser();
        if ($user->getId()!= $id)
        {
            echo "Wrong User". $id . $user->getId();
            die();
        }
        $form = $this->createForm(UserType::class, $user);
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

                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents
                $user->setImage($filename);
                $user->setPassword(
                    $passwordEncoder->encodePassword(
                        $user,
                        $form->get('password')->getData()
                    )
                );
            }

            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('user_index');
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'setting' => $setting,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="user_delete", methods={"DELETE"})
     */
    public function delete(Request $request, User $user): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('user_index');
    }

     /**
     * @return string
     */
    private function generateUniqueFileName()
    {
        return md5(uniqid());
    }
}
