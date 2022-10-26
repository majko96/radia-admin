<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChangePasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('oldPassword', PasswordType::class, [
                        'required' => true,
                        'label' => 'Type your current password',
                        ])
                ->add('newPassword', RepeatedType::class, [
                        'type' => PasswordType::class,
                        'invalid_message' => 'Passwords do not match.',
                        'first_options'  => ['label' => 'Type your new password'],
                        'second_options' => ['label' => 'Retype your new password']    
                 ]);                 
    }

    public function setDefaultOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => ChangePassword::class,
        ));
    }

    public function getName()
    {
        return 'change_passwd';
    }
}