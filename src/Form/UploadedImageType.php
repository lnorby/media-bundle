<?php

namespace Lnorby\MediaBundle\Form;

use Lnorby\MediaBundle\Form\DataTransformer\MediaTransformer;
use Lnorby\MediaBundle\Form\Dto\UploadedImageDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class UploadedImageType extends AbstractType
{
    /**
     * @var MediaTransformer
     */
    private $transformer;

    public function __construct(MediaTransformer $transformer)
    {
        $this->transformer = $transformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'media',
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

        $builder->get('media')->addModelTransformer($this->transformer);
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
