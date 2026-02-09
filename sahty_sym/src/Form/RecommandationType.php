<?php

namespace App\Form;

use App\Entity\Quiz;
use App\Entity\Recommandation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RecommandationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de la recommandation',
                'attr'  => ['placeholder' => 'Ex : Recommandation pour anxiété modérée'],
            ])
            ->add('description', TextareaType::class, [
                'label'    => 'Description / Conseils',
                'required' => false,
                'attr'     => ['rows' => 5],
            ])
            ->add('min_score', IntegerType::class, [
                'label' => 'Score minimum',
                'attr'  => ['min' => 0],
            ])
            ->add('max_score', IntegerType::class, [
                'label' => 'Score maximum',
                'attr'  => ['min' => 0],
            ])
            ->add('quiz', EntityType::class, [
                'class'         => Quiz::class,
                'choice_label'  => 'name',
                'label'         => 'Quiz associé',
                'placeholder'   => '— Choisir un quiz —',
                'required'      => true,
                'attr'          => ['class' => 'form-select', 'id' => 'recommandation_quiz'],
            ])
            ->add('type_probleme', ChoiceType::class, [
                'label'       => 'Question / Problème concerné',
                'choices'     => [],
                'placeholder' => '— Choisissez d’abord un quiz —',
                'required'    => true,
                'disabled'    => true,
                'attr'        => ['class' => 'form-select', 'id' => 'recommandation_type_probleme'],
            ])
        ;

        // Listener pour recharger les choix quand le quiz change
        $formModifier = function (FormInterface $form, ?Quiz $quiz = null) {
            $choices = [];

            if ($quiz) {
                $questions = $quiz->getQuestions() ?? [];
                foreach ($questions as $index => $question) {
                    $text = $question['question'] ?? "Question sans texte (#$index)";
                    $choices[$text] = $text;  // ← ON STOCKE LE TEXTE DIRECTEMENT
                }
            }

            $form->add('type_probleme', ChoiceType::class, [
                'label'       => 'Question / Problème concerné',
                'choices'     => $choices,
                'placeholder' => $quiz ? '— Choisir une question —' : 'Choisissez d’abord un quiz',
                'required'    => true,
                'attr'        => ['class' => 'form-select'],
                'disabled'    => empty($choices),
            ]);
        };

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($formModifier) {
                $recommandation = $event->getData();
                $formModifier($event->getForm(), $recommandation?->getQuiz());
            }
        );

        $builder->get('quiz')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($formModifier) {
                $quiz = $event->getForm()->getData();
                $formModifier($event->getForm()->getParent(), $quiz);
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Recommandation::class,
        ]);
    }
}