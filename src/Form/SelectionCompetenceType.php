<?php

namespace App\Form;

use App\Entity\Competence;
use App\Entity\Offre;
use App\Entity\SelectionCompetence;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SelectionCompetenceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('coeff_competence')
            ->add('offre', EntityType::class, [
                'class' => Offre::class,
                'choice_label' => 'id',
            ])
            ->add('competence', EntityType::class, [
                'class' => Competence::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SelectionCompetence::class,
        ]);
    }
}
