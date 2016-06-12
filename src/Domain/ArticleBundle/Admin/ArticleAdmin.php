<?php

namespace Domain\ArticleBundle\Admin;

use Oxa\Sonata\AdminBundle\Admin\OxaAdmin;
use Oxa\Sonata\MediaBundle\Model\OxaMediaInterface;
use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;

class ArticleAdmin extends OxaAdmin
{
    /**
     * @param DatagridMapper $datagridMapper
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $choiceOptions = [
            'choices' => [
                1 => 'label_yes',
                2 => 'label_no',
            ],
            'translation_domain' => $this->getTranslationDomain()
        ];

        $datagridMapper
            ->add('id')
            ->add('title')
            ->add('description')
            ->add('isPublished', null, [], null, $choiceOptions)
            ->add('isOnHomepage', null, [], null, $choiceOptions)
            ->add('updatedAt', 'doctrine_orm_datetime_range', [
                'field_type' => 'sonata_type_datetime_range_picker',
                'field_options' => [
                    'format' => 'dd-MM-y hh:mm:ss'
            ]])
            ->add('updatedUser')
        ;
    }

    /**
     * @param ListMapper $listMapper
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('id')
            ->add('title')
            ->add('description')
            ->add('isPublished')
            ->add('isOnHomepage')
            ->add('updatedAt')
            ->add('updatedUser')
        ;

        $this->addGridActions($listMapper);
    }

    /**
     * @param FormMapper $formMapper
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        // define group zoning
        $formMapper
            ->with('General', array('class' => 'col-md-4'))->end()
            ->with('Content', array('class' => 'col-md-8'))->end()
        ;

        $formMapper
            ->with('General')
                ->add('title')
                ->add('category')
                ->add('image', 'sonata_type_model_list', [], ['link_parameters' => [
                    'context' => OxaMediaInterface::CONTEXT_ARTICLE,
                    'provider' => OxaMediaInterface::PROVIDER_IMAGE,
                ]])
                ->add('isPublished')
                ->add('isOnHomepage')
                ->add('slug', null, ['read_only' => true])
                ->add('updatedAt', 'sonata_type_datetime_picker', [
                    'required' => false,
                    'disabled' => true
                ])
                ->add('updatedUser', 'sonata_type_model', [
                    'required' => false,
                    'btn_add' => false,
                    'disabled' => true,
                ])
            ->end()
            ->with('Content')
                ->add('description', null, [
                    'attr' => [
                        'rows' => 3,
                    ]
                ])
                ->add('body', 'ckeditor')
            ->end()
        ;
    }

    /**
     * @param ShowMapper $showMapper
     */
    protected function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->add('id')
            ->add('title')
            ->add('category')
            ->add('image', null, [
                'template' => 'DomainArticleBundle:Admin:show_image.html.twig'
            ])
            ->add('description')
            ->add('body', null, [
                'template' => 'DomainArticleBundle:Admin:show_body.html.twig'
            ])
            ->add('isPublished')
            ->add('isOnHomepage')
            ->add('slug')
            ->add('updatedAt')
            ->add('updatedUser')
        ;
    }
}