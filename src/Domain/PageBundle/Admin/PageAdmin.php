<?php

namespace Domain\PageBundle\Admin;

use Oxa\Sonata\AdminBundle\Admin\OxaAdmin;
use Oxa\Sonata\MediaBundle\Model\OxaMediaInterface;
use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class PageAdmin extends OxaAdmin
{
    /**
     * @param DatagridMapper $datagridMapper
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('id')
            ->add('title')
            ->add('isPublished', null, [], null, $this->defaultDatagridBooleanTypeOptions)
            ->add('template')
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
            ->add('template')
            ->add('isPublished', null, ['editable' => true])
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
            ->with('General', array('class' => 'col-md-6'))->end()
            ->with('Status', array('class' => 'col-md-6'))->end()
            ->with('Body', array('class' => 'col-md-12'))->end()
        ;

        $formMapper
            ->with('General')
                ->add('title')
                ->add('description')
                ->add('template', 'sonata_type_model_list', [
                    'required' => false,
                    'btn_add' => false,
                ])
            ->end()
            ->with('Status')
                ->add('isPublished')
                ->add('updatedAt', 'sonata_type_datetime_picker', ['required' => false, 'disabled' => true])
                ->add('updatedUser', 'sonata_type_model', [
                    'required' => false,
                    'btn_add' => false,
                    'disabled' => true,
                ])
                ->add('url', TextType::class, [
                    'mapped' => false,
                    'read_only' => true,
                    'required' => false,
                    'data' => sprintf(
                        '%s\%s',
                        $this->getRequest()->getHost(),
                        $this->getSubject()->getSlug()
                    )
                ])
                ->add('slug', null, ['read_only' => true, 'required' => false])
            ->end()
            ->with('Body')
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
            ->add('description')
            ->add('body', null, array('template' => 'DomainPageBundle:Admin:show__body.html.twig'))
            ->add('isPublished')
            ->add('slug')
        ;
    }

    /**
     * @param RouteCollection $collection
     */
    protected function configureRoutes(RouteCollection $collection)
    {
        parent::configureRoutes($collection);

        $collection
            ->remove('delete_physical')
            ->remove('delete')
            ->remove('remove')
            ->remove('create')
        ;
    }
}
