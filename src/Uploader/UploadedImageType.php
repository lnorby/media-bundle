<?php

namespace Lnorby\MediaBundle\Uploader;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class UploadedImageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'mediaId',
                HiddenType::class,
                [
                    'attr' => [
                        'class' => 'js-uploaded-image-media-id',
                    ],
                ]
            )
            ->add(
                'position',
                HiddenType::class,
                [
                    'attr' => [
                        'class' => 'js-uploaded-image-position',
                    ],
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => UploadedImageDto::class,
            ]
        );
    }
}
