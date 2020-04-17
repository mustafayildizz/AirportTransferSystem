<?php

namespace App\Form\Admin;

use App\Entity\Admin\Reservation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReservationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('surname')
            ->add('departure')
            ->add('arrival')
            ->add('time')
            ->add('message')
            ->add('note')
            ->add('status', ChoiceType::class, [
                'choices'  => [
                    'New' => 'New',
                    'Accepted' => 'Accepted',
                    'Canceled' => 'Canceled',
                    'Completed' => 'Completed',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
        ]);
    }
}
