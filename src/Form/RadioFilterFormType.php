<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;


class RadioFilterFormType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('country', ChoiceType::class, [
                'choices' => [
                    '-' => 'null',
                    'CZ' => 'cz',
                    'SK' => 'sk',
                    'USA' => 'us',
                    'RUS' => 'ru',
                    'POL' => 'pol',
                    'NED' => 'ned',
                    'GER' => 'ger',
                    'UK' => 'uk',
                    'FR' => 'fr',
                    'AU' => 'au',
                    'SLO' => 'slo',
                    'OTHER' => 'other'
                ]
            ])
            ->add('style', ChoiceType::class, [
                'choices' => [
                    '-' => 'null',
                    'POP' => 'pop',
                    'RELAX' => 'relax',
                    'DANCE' => 'dance',
                    'OLDIES' => 'oldies',
                    'ROCK' => 'rock',
                    'RNB' => 'rnb',
                    'FOLK' => 'folk',
                    'NEWS' => 'news',
                    'JAZZ' => 'jazz',
                    'TALK' => 'talk',
                    'SOLO' => 'solo'
                ]
            ])
            ->add('top', ChoiceType::class, [
                'choices' => [
                    '-' => null,
                    'Active' => true,
                ]
            ])
            ->add('searchByTitle', TextType::class, []);
    }
}
