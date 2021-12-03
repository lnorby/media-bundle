<?php

namespace Lnorby\MediaBundle\Form;

use Doctrine\Common\Collections\ArrayCollection;
use Lnorby\MediaBundle\Entity\Media;
use Lnorby\MediaBundle\Form\Dto\UploadedImageDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class MultipleImageUploaderType extends AbstractType implements DataTransformerInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['transform']) {
            $builder->addModelTransformer($this);
        }
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['file_picker_label'] = $options['file_picker_label'];
        $view->vars['limit'] = $options['limit'];
        $view->vars['min_height'] = $options['min_height'];
        $view->vars['min_width'] = $options['min_width'];
        $view->vars['sortable'] = $options['sortable'];
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'allow_add' => true,
                'allow_delete' => true,
                'entry_type' => UploadedImageType::class,
                'file_picker_label' => 'Fénykép hozzáadása',
                'min_height' => 250,
                'min_width' => 250,
                'sortable' => false,
                'transform' => false,
            ]
        );

        $resolver->setRequired(['limit']);
        $resolver->setAllowedTypes('limit', ['int']);
        $resolver->setAllowedTypes('min_height', ['int']);
        $resolver->setAllowedTypes('min_width', ['int']);
        $resolver->setAllowedTypes('sortable', ['bool']);
        $resolver->setAllowedTypes('transform', ['bool']);
    }

    public function getParent()
    {
        return CollectionType::class;
    }

    public function transform($value)
    {
        return $value->map(
            function (Media $media) {
                return UploadedImageDto::create(null, $media);
            }
        );
    }

    public function reverseTransform($value)
    {
        return new ArrayCollection(
            array_map(
                function (UploadedImageDto $imageData) {
                    return $imageData->media;
                },
                $value
            )
        );
    }
}
