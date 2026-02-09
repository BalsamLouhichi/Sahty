<?php

namespace App\Form;

use App\Entity\Evenement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType; 
use App\Entity\GroupeCible;
class EvenementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('titre', TextType::class)

            ->add('description', TextareaType::class, [
                'required' => false
            ])

            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Webinaire' => 'webinaire',
                    'Atelier' => 'atelier',
                    'Dépistage' => 'depistage',
                    'Conférence' => 'conference',
                    'Groupe de parole' => 'groupe_parole',
                ]
            ])

            ->add('dateDebut', DateTimeType::class, [
    'widget' => 'single_text',
    'html5' => true,        // enable HTML5 datetime-local
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
                ]
            ])

            ->add('lieu', TextType::class, [
                'required' => false
            ])

            ->add('placesMax', IntegerType::class, [
                'required' => false
            ])

            ->add('statut', ChoiceType::class, [
                'choices' => [
                    'Brouillon' => 'brouillon',
                    'Planifié' => 'planifie',
                    'Confirmé' => 'confirme',
                    'En cours' => 'en_cours',
                    'Terminé' => 'termine',
                    'Annulé' => 'annule',
                ]
            ])

            ->add('tarif', MoneyType::class, [
                'currency' => 'TND',
                'required' => false
            ])
            ->add('groupeCibles', EntityType::class, [
                'class' => GroupeCible::class,
                'choice_label' => 'nom',
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'label' => 'Groupes cibles',
                'query_builder' => function (GroupeCibleRepository $repository) use ($userRole, $isAdmin) {
                    // Si admin, voir tous les groupes
                    if ($isAdmin) {
                        return $repository->createQueryBuilder('g')
                            ->orderBy('g.nom', 'ASC');
                    }
                    
                    // Sinon, filtrer selon le rôle
                    $qb = $repository->createQueryBuilder('g')
                        ->orderBy('g.nom', 'ASC');
                    
                    // Filtrer par type selon le rôle
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

        // Seul l'admin peut modifier le statut
        if ($isAdmin) {
            $builder->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Planifié' => 'planifie',
                    'En cours' => 'en_cours',
                    'Terminé' => 'termine',
                    'Annulé' => 'annule',
                    'En attente d\'approbation' => 'en_attente_approbation',
                    'Approuvé' => 'approuve',
                ],
                'required' => true,
            ]);
        }
    ;}
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Evenement::class,
        ]);
    }
}
