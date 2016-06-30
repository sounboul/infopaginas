<?php

namespace Domain\BusinessBundle\Admin;

use Doctrine\Common\Collections\Collection;
use Domain\BusinessBundle\Entity\BusinessProfile;
use Domain\BusinessBundle\Entity\Review\BusinessReview;
use Domain\BusinessBundle\Manager\SonataQueryManager;
use Gedmo\Loggable\Entity\LogEntry;
use Geocoder\HttpAdapter\CurlHttpAdapter;
use Ivory\GoogleMap\Base\Coordinate;
use Ivory\GoogleMap\Controls\ControlPosition;
use Ivory\GoogleMap\Overlays\Animation;
use Ivory\GoogleMap\Overlays\Marker;
use Ivory\GoogleMap\Overlays\Polyline;
use Ivory\GoogleMap\Places\AutocompleteComponentRestriction;
use Ivory\GoogleMap\Places\AutocompleteType;
use Ivory\GoogleMap\Services\Geocoding\Geocoder;
use Ivory\GoogleMap\Services\Geocoding\GeocoderProvider;
use Oxa\ConfigBundle\Model\ConfigInterface;
use Oxa\Sonata\AdminBundle\Admin\OxaAdmin;
use Oxa\Sonata\MediaBundle\Model\OxaMediaInterface;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\AdminBundle\Validator\ErrorElement;
use Sonata\DoctrineORMAdminBundle\Model\ModelManager;

class BusinessProfileAdmin extends OxaAdmin
{
    /**
     * @param DatagridMapper $datagridMapper
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('id')
            ->add('name')
            ->add('user')
            ->add('categories')
            ->add('registrationDate', 'doctrine_orm_datetime_range', $this->defaultDatagridDatetimeTypeOptions)
            ->add('createDate', 'doctrine_orm_datetime_range', $this->defaultDatagridDatetimeTypeOptions)
            ->add('isActive', null, [], null, $this->defaultDatagridBooleanTypeOptions)
        ;
    }

    /**
     * @param ListMapper $listMapper
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('id')
            ->add('logo', null, ['template' => 'DomainBusinessBundle:Admin:BusinessProfile/list_image.html.twig'])
            ->add('name')
            ->add('user')
            ->add('subscriptionPlan', null, [
                'template' => 'DomainBusinessBundle:Admin:BusinessProfile/list_subscription.html.twig'
            ])
            ->add('discount', null, [
                'template' => 'DomainBusinessBundle:Admin:BusinessProfile/list_discount.html.twig'
            ])
            ->add('categories')
            ->add('registrationDate')
            ->add('isActive', null, ['editable' => true])
            ->add('sorting', null, ['template' => 'OxaSonataAdminBundle:CRUD:list_sorting.html.twig'])
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
            ->tab('Profile', array('class' => 'col-md-6'))
                ->with('General', array('class' => 'col-md-4'))->end()
                ->with('Description', array('class' => 'col-md-8'))->end()
                ->with('Address', array('class' => 'col-md-4'))->end()
                ->with('Map', array('class' => 'col-md-8'))->end()
                ->with('Gallery')->end()
            ->end()
            ->tab('Categories', array('class' => 'col-md-12'))
                ->with('Categories', array('class' => 'col-md-12'))->end()
            ->end()
            ->tab('Status', array('class' => 'col-md-6'))
                ->with('General', array('class' => 'col-md-6'))->end()
                ->with('Displayed blocks', array('class' => 'col-md-6'))->end()
                ->with('Subscriptions', array('class' => 'col-md-12'))->end()
                ->with('Discounts', array('class' => 'col-md-12'))->end()
            ->end()
            ->tab('Reviews', array('class' => 'col-md-6'))
                ->with('User Reviews')->end()
            ->end();

        $oxaConfig = $this->getConfigurationPool()
            ->getContainer()
            ->get('oxa_config');

        if ($this->getSubject()->getLatitude() && $this->getSubject()->getLongitude()) {
            $latitude   = $this->getSubject()->getLatitude();
            $longitude  = $this->getSubject()->getLongitude();
        } else {
            $latitude   = $oxaConfig->getValue(ConfigInterface::DEFAULT_MAP_COORDINATE_LATITUDE);
            $longitude  = $oxaConfig->getValue(ConfigInterface::DEFAULT_MAP_COORDINATE_LONGITUDE);
        }

        $formMapper
            ->tab('Profile')
                ->with('General')
                    ->add('name')
                    ->add('user', 'sonata_type_model_list', [
                        'required' => false,
                        'btn_delete' => false,
                        'btn_add' => false,
                    ])
                    ->add('logo', 'sonata_type_model_list', [], ['link_parameters' => [
                        'context' => OxaMediaInterface::CONTEXT_BUSINESS_PROFILE_LOGO,
                        'provider' => OxaMediaInterface::PROVIDER_IMAGE,
                    ]])
                    ->add('website')
                    ->add('email', 'email')
                    ->add('phone')
                    ->add('slug', null, ['read_only' => true, 'required' => false])
                ->end()
                ->with('Description')
                    ->add('slogan')
                    ->add('product')
                    ->add('workingHours')
                    ->add('description', 'ckeditor')
                ->end()
                ->with('Address')
                    ->add('country', 'sonata_type_model_list', [
                        'required' => false,
                        'btn_delete' => false,
                        'btn_add' => false,
                    ])
                    ->add('state')
                    ->add('city')
                    ->add('zipCode')
                    ->add('streetAddress')
                    ->add('extendedAddress')
                    ->add('streetNumber')
                    ->add('customAddress')
                ->end()
                ->with('Map')
                    ->add('useMapAddress', null, [
                        'label' => 'Update address using map coordinates'
                    ])
                    ->add('fullAddress', 'google_map', [
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                    ])
                ->end()
                ->with('Gallery')
                    ->add('images', 'sonata_type_collection', ['by_reference' => false], [
                        'edit' => 'inline',
                        'sortable'  => 'position',
                        'delete_empty' => true,
                        'inline' => 'table',
                        'link_parameters' => [
                            'context' => OxaMediaInterface::CONTEXT_BUSINESS_PROFILE_IMAGES,
                            'provider' => OxaMediaInterface::PROVIDER_IMAGE,
                        ]
                    ])
                ->end()
            ->end()
            ->tab('Categories')
                ->with('Categories')
                    ->add('areas', null, ['multiple' => true])
                    ->add('brands', null, ['multiple' => true])
                    ->add('tags', null, ['multiple' => true])
                    ->add('categories', null, ['multiple' => true])
                    ->add('paymentMethods', null, [
                        'multiple' => true,
                        'expanded' => true,
                    ])
                ->end()
            ->end()
            ->tab('Status')
                ->with('General')
                    ->add('isActive')
                    ->add('updatedAt', 'sonata_type_datetime_picker', ['required' => false, 'disabled' => true])
                    ->add('updatedUser', 'sonata_type_model', [
                        'required' => false,
                        'btn_add' => false,
                        'disabled' => true,
                    ])
                    ->add('createdAt', 'sonata_type_datetime_picker', ['required' => false, 'disabled' => true])
                    ->add('createdUser', 'sonata_type_model', [
                        'required' => false,
                        'btn_add' => false,
                        'disabled' => true,
                    ])
                ->end()
                ->with('Displayed blocks')
                    ->add('isSetDescription')
                    ->add('isSetMap')
                    ->add('isSetAd')
                    ->add('isSetLogo')
                    ->add('isSetSlogan')
                ->end()
                ->with('Subscriptions')
                    ->add('subscriptions', 'sonata_type_collection', [
                        'modifiable' => false,
                        'btn_add' => false,
                        'mapped' => true,
                        'required' => false,
                        'type_options' => [
                            'delete' => false,
                            'delete_options' => [
                                'type' => 'checkbox',
                                'type_options' => ['mapped' => false, 'required' => false]
                            ]]
                    ], [
                        'read_only' => true,
                        'edit' => 'inline',
                        'inline' => 'table',
                        'allow_delete' => false,
                    ])
                ->end()
                ->with('Discounts')
                    ->add('discounts', 'sonata_type_collection', [
                        'modifiable' => false,
                        'btn_add' => false,
                        'mapped' => true,
                        'required' => false,
                        'type_options' => [
                            'delete' => false,
                            'delete_options' => [
                                'type' => 'checkbox',
                                'type_options' => ['mapped' => false, 'required' => false]
                            ]]
                    ], [
                        'read_only' => true,
                        'edit' => 'inline',
                        'inline' => 'table',
                        'allow_delete' => false,
                    ])
                ->end()
            ->end()
            ->tab('Reviews')
                ->with('User Reviews')
                    ->add('businessReviews', 'sonata_type_collection', [
                        'mapped' => true,
                        'type_options' => [
                            'delete' => true,
                            'delete_options' => [
                                'type' => 'checkbox',
                                'type_options' => ['mapped' => false, 'required' => false]
                            ]]
                    ], [
                        'edit' => 'inline',
                        'inline' => 'table',
                        'sortable' => 'id',
                        'allow_delete' => true,
                    ])
                ->end()
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
            ->add('logo', null, [
                'template' => 'DomainBusinessBundle:Admin:BusinessProfile/show_image.html.twig'
            ])
            ->add('name')
            ->add('images')
            ->add('user')
            ->add('subscription')
            ->add('subscriptions')
            ->add('discount')
            ->add('discounts')
            ->add('categories')
            ->add('areas')
            ->add('brands')
            ->add('paymentMethods')
            ->add('tags')
            ->add('businessReviews')
            ->add('website')
            ->add('email')
            ->add('phone')
            ->add('registrationDate')
            ->add('slogan')
            ->add('description', null, [
                'template' => 'DomainBusinessBundle:Admin:BusinessProfile/show_description.html.twig'
            ])
            ->add('product')
            ->add('workingHours')
            ->add('isSetDescription')
            ->add('isSetMap')
            ->add('isSetAd')
            ->add('isSetLogo')
            ->add('isSetSlogan')
            ->add('slug')
            ->add('updatedAt')
            ->add('updatedUser')
            ->add('isActive')
        ;
    }

    /**
     * @param ErrorElement $errorElement
     * @param mixed $object
     */
    public function validate(ErrorElement $errorElement, $object)
    {
        if ($object->getUseMapAddress()) {

            $addressManager = $this->configurationPool
                ->getContainer()
                ->get('domain_business.manager.address_manager');

            $addressResult = $addressManager->validateAddress($object->getFullAddress());

            if (!empty($addressResult['error'])) {
                $errorElement->with('fullAddress')
                    ->addViolation($addressResult['error'])
                    ->end()
                ;
            } else {
                $addressManager->setGoogleAddress($addressResult['result'], $object);
            }
        }

    }
}
