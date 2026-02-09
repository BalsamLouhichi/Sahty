<?php

namespace App\Form;

use App\Entity\Evenement;
use App\Entity\GroupeCible;
use App\Repository\GroupeCibleRepository;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class EvenementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isAdmin  = $options['is_admin'];
        $userRole = $options['user_role'];
        $isEdit   = $options['is_edit'];

        $builder
            ->add('titre', TextType::class)

            ->add('description', TextareaType::class, [
                'required' => false,
            ])

            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Webinaire' => 'webinaire',
                    'Atelier' => 'atelier',
                    'Dépistage' => 'depistage',
                    'Conférence' => 'conference',
                    'Groupe de parole' => 'groupe_parole',
                ],
            ])

            ->add('dateDebut', DateTimeType::class, [
                'widget' => 'single_text',
                'html5' => true,
                'required' => true,
                'input' => 'datetime',
                'model_timezone' => 'Europe/Paris',
                'view_timezone' => 'Europe/Paris',
            ])

            ->add('dateFin', DateTimeType::class, [
                'widget' => 'single_text',
                'html5' => true,
                'required' => false,
                'empty_data' => null,
                'input' => 'datetime',
                'model_timezone' => 'Europe/Paris',
                'view_timezone' => 'Europe/Paris',
            ])

            ->add('mode', ChoiceType::class, [
                'choices' => [
                    'En ligne' => 'en_ligne',
                    'Présentiel' => 'presentiel',
                    'Hybride' => 'hybride',
                ],
            ])

            ->add('lieu', TextType::class, [
                'required' => false,
            ])

            ->add('placesMax', IntegerType::class, [
                'required' => false,
            ])

            ->add('tarif', MoneyType::class, [
                'currency' => 'TND',
                'required' => false,
            ])

            ->add('groupeCibles', EntityType::class, [
                'class' => GroupeCible::class,
                'choice_label' => 'nom',
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'label' => 'Groupes cibles',
                'query_builder' => function (GroupeCibleRepository $repository) use ($userRole, $isAdmin) {
                    $qb = $repository->createQueryBuilder('g')
                        ->orderBy('g.nom', 'ASC');

                    if ($isAdmin) {
                        return $qb;
                    }

                    if ($userRole === 'ROLE_PATIENT') {
                        $qb->where('g.type LIKE :type')
                           ->setParameter('type', '%patient%');
                    } elseif ($userRole === 'ROLE_MEDECIN') {
                        $qb->where('g.type LIKE :type')
                           ->setParameter('type', '%medecin%');
                    } elseif ($userRole === 'ROLE_RESPONSABLE_LABO') {
                        $qb->where('g.type LIKE :type')
                           ->setParameter('type', '%laboratoire%');
                    } elseif ($userRole === 'ROLE_RESPONSABLE_PARA') {
                        $qb->where('g.type LIKE :type')
                           ->setParameter('type', '%paramedical%');
                    }

                    return $qb;
                },
            ]);

        // Add statut field with different choices based on user role
        $statutChoices = [
            'Planifié' => 'planifie',
            'En cours' => 'en_cours',
            'Terminé' => 'termine',
            'Annulé' => 'annule',
        ];
        
        if ($isAdmin) {
            $statutChoices = array_merge($statutChoices, [
                'En attente d\'approbation' => 'en_attente_approbation',
                'Approuvé' => 'approuve',
            ]);
        }

        $builder->add('statut', ChoiceType::class, [
            'choices' => $statutChoices,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Evenement::class,
            'is_edit' => false,
            'is_admin' => false,
            'user_role' => null,
        ]);

        $resolver->setAllowedTypes('is_edit', 'bool');
        $resolver->setAllowedTypes('is_admin', 'bool');
        $resolver->setAllowedTypes('user_role', ['null', 'string']);
    }
}