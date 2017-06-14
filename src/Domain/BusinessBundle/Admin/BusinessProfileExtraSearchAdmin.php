<?php

namespace Domain\BusinessBundle\Admin;

use Domain\BusinessBundle\Entity\BusinessProfileExtraSearch;
use Oxa\Sonata\AdminBundle\Admin\OxaAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\CoreBundle\Validator\ErrorElement;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class BusinessProfileExtraSearchAdmin extends OxaAdmin
{
    /**
     * @param DatagridMapper $datagridMapper
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('id')
        ;
    }

    /**
     * @param ListMapper $listMapper
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('id')
        ;
    }

    /**
     * @param FormMapper $formMapper
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('categories', null, [
                'multiple' => true,
                'required' => true,
                'query_builder' => function (\Domain\BusinessBundle\Repository\CategoryRepository $rep) {
                    return $rep->getAvailableCategoriesQb();
                },
            ])
            ->add('areas', null, [
                'multiple' => true,
                'required' => false,
                'label' => 'Areas',
                'query_builder' => function (\Domain\BusinessBundle\Repository\AreaRepository $rep) {
                    return $rep->getAvailableAreasQb();
                },
            ])
            ->add('localities', null, [
                'multiple' => true,
                'required' => false,
                'label' => 'Localities',
                'query_builder' => function (\Domain\BusinessBundle\Repository\LocalityRepository $rep) {
                    return $rep->getAvailableLocalitiesQb();
                },
            ])
            ->add('serviceAreasType', ChoiceType::class, [
                'choices'  => BusinessProfileExtraSearch::getServiceAreasTypes(),
                'multiple' => false,
                'expanded' => true,
                'required' => true,
            ])
            ->add('milesOfMyBusiness', null, [
                'required' => false,
            ])
        ;
    }

    /**
     * @param ShowMapper $showMapper
     */
    protected function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->add('id')
            ->add('categories')
            ->add('localities')
            ->add('serviceAreasType')
            ->add('milesOfMyBusiness')
        ;
    }

    /**
     * @param ErrorElement $errorElement
     * @param mixed $object
     * @return null
     */
    public function validate(ErrorElement $errorElement, $object)
    {
        /** @var BusinessProfileExtraSearch $object */
        if ($object->getServiceAreasType() == BusinessProfileExtraSearch::SERVICE_AREAS_AREA_CHOICE_VALUE) {
            if (empty($object->getMilesOfMyBusiness())) {
                $errorElement->with('milesOfMyBusiness')
                    ->addViolation($this->getTranslator()->trans(
                        'business_profile.extra_search.miles_empty',
                        [],
                        $this->getTranslationDomain()
                    ))
                    ->end()
                ;
            }
        } elseif ($object->getLocalities()->isEmpty()) {
            $errorElement->with('localities')
                ->addViolation($this->getTranslator()->trans(
                    'business_profile.extra_search.localities_empty',
                    [],
                    $this->getTranslationDomain()
                ))
                ->end()
            ;
        }
    }
}
