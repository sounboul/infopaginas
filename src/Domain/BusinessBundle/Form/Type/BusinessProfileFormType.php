<?php

namespace Domain\BusinessBundle\Form\Type;

use Domain\BusinessBundle\Entity\BusinessProfile;
use Domain\BusinessBundle\Entity\Media\BusinessGallery;
use Domain\BusinessBundle\Entity\PaymentMethod;
use Domain\BusinessBundle\Entity\SubscriptionPlan;
use Domain\BusinessBundle\Model\SubscriptionPlanInterface;
use Domain\BusinessBundle\Repository\AreaRepository;
use Domain\BusinessBundle\Repository\CategoryRepository;
use Domain\BusinessBundle\Repository\CountryRepository;
use Domain\BusinessBundle\Repository\LocalityRepository;
use Domain\BusinessBundle\Repository\NeighborhoodRepository;
use Domain\BusinessBundle\Repository\PaymentMethodRepository;
use Domain\BusinessBundle\Repository\TagRepository;
use Domain\SiteBundle\Validator\Constraints\ConstraintUrlExpanded;
use Oxa\Sonata\MediaBundle\Model\OxaMediaInterface;
use Oxa\WistiaBundle\Form\Type\WistiaMediaType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

/**
 * Class BusinessProfileFormType
 * @package Domain\BusinessBundle\Form\Type
 */
class BusinessProfileFormType extends AbstractType
{
    protected $isUserSectionRequired = false;

    public function __construct($isUserSectionRequired = false)
    {
        $this->isUserSectionRequired = $isUserSectionRequired;
    }

    public function setCurrentUser(Session $session)
    {
        if ($session->has('_security_user')) {
            $this->isUserSectionRequired = false;
        } else {
            $this->isUserSectionRequired = true;
        }
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($this->isUserSectionRequired) {
            $emailConstraints = [new NotBlank()];
        } else {
            $emailConstraints = [];
        }


        $builder
            ->add('name', TextType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Sons Notebook',
                ],
                'label' => 'Name',
            ])
            ->add('website', TextType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'website.example.placeholder',
                ],
                'label' => 'Website',
                'required' => false,
            ])
            ->add('phones', CollectionType::class, [
                'allow_add'    => true,
                'allow_delete' => true,
                'entry_type'   => BusinessProfilePhoneType::class,
                'label' => 'Phone number',
                'required' => false,
            ])
            ->add('areas', EntityType::class, [
                'attr' => [
                    'class' => 'form-control select-control select-multiple',
                    'data-placeholder' => 'Select areas',
                    'multiple' => 'multiple',
                ],
                'class' => 'Domain\BusinessBundle\Entity\Area',
                'label' => 'Areas',
                'multiple' => true,
                'query_builder' => function (AreaRepository $repository) {
                    return $repository->getAvailableAreasQb();
                }
            ])
            ->add('email', TextType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'email.example.placeholder',
                ],
                'label' => 'Email',
                'constraints' => $emailConstraints,
            ])
            ->add('brands', TextareaType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Organize, store, plan, prioritize',
                    'rows' => 3,
                ],
                'label' => 'Brands',
                'required' => false,
            ])
            ->add('description', TextareaType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'business.add.description.placeholder',
                    'rows' => 5,
                ],
                'label' => 'Description',
                'required' => false,
            ])
            ->add('tags', EntityType::class, [
                'attr' => [
                    'class' => 'form-control select-control select-multiple',
                    'data-placeholder' => 'Advertising, Cafeterias, Grooming, Restaurants',
                    'multiple' => 'multiple',
                ],
                'class' => 'Domain\BusinessBundle\Entity\Tag',
                'label' => 'Tags',
                'multiple' => true,
                'query_builder' => function (TagRepository $repository) {
                    return $repository->getAvailableTagsQb();
                },
                'required' => false,
            ])
            ->add('product', TextareaType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'business.add.product.placeholder',
                    'rows' => 3,
                ],
                'label' => 'Products',
                'required' => false,
            ])
            ->add('paymentMethods', EntityType::class, [
                'attr' => [
                    'class' => 'form-control select-control select-multiple',
                    'data-placeholder' => 'Select payment methods',
                    'multiple' => 'multiple',
                ],
                'class' => 'Domain\BusinessBundle\Entity\PaymentMethod',
                'label' => 'Payment methods',
                'multiple' => true,
                'query_builder' => function (PaymentMethodRepository $repository) {
                    return $repository->getAvailablePaymentMethodsQb();
                },
                'required' => false,
            ])
            ->add('workingHours', TextareaType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'business.add.workingHours.placeholder',
                    'rows' => 3,
                ],
                'label' => 'Working hours',
                'required' => false,
            ])
            ->add('serviceAreasType', ChoiceType::class, [
                'choices' => BusinessProfile::getServiceAreasTypes(),
                'label' => 'Service Areas',
                'multiple' => false,
                'expanded' => true,
                'required' => true,
                'choice_translation_domain' => true,
            ])
            ->add('streetAddress', TextType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'business.add.streetAddress.placeholder',
                ],
                'label' => 'Street address',
            ])
            ->add('map', GoogleMapFrontType::class, [
                'mapped' => false,
            ])
            ->add('latitude', TextType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'latitude.example.placeholder',
                ],
                'label' => 'Latitude',
                'required' => false,
            ])
            ->add('longitude', TextType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'longitude.example.placeholder',
                ],
                'label' => 'Longitude',
                'required' => false,
            ])
            ->add('country', EntityType::class, [
                'attr' => [
                    'class' => 'form-control select-control',
                    'data-placeholder' => 'Select country',
                ],
                'class' => 'Domain\BusinessBundle\Entity\Address\Country',
                'label' => 'Country',
                'query_builder' => function (CountryRepository $repository) {
                    return $repository->getAvailableCountriesQb();
                }
            ])
            ->add('state', TextType::class, [
                'attr' => [
                    'class' => 'form-control',
                ],
                'label' => 'State',
                'required' => false,
            ])
            ->add('catalogLocality', EntityType::class, [
                'attr' => [
                    'class' => 'form-control select-control',
                    'data-placeholder' => 'Select catalog locality',
                ],
                'class' => 'Domain\BusinessBundle\Entity\Locality',
                'label' => 'Catalog Locality',
            ])
            ->add('city', TextType::class, [
                'attr' => [
                    'class' => 'form-control',
                ],
                'label' => 'City',
            ])
            ->add('zipCode', TextType::class, [
                'attr' => [
                    'class' => 'form-control',
                ],
                'label' => 'Zip code',
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('extendedAddress', TextType::class, [
                'attr' => [
                    'class' => 'form-control',
                ],
                'label' => 'Extended address',
                'required' => false,
            ])
            ->add('crossStreet', TextType::class, [
                'attr' => [
                    'class' => 'form-control',
                ],
                'label' => 'Cross street',
                'required' => false,
            ])
            ->add('customAddress', TextType::class, [
                'attr' => [
                    'class' => 'form-control',
                ],
                'label' => 'Display custom address',
                'required' => false,
            ])
            ->add('hideAddress', CheckboxType::class, [
                'label' => 'Hide Address',
                'required' => false,
            ])
            ->add('twitterURL', TextType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'twitter.example.email.placeholder',
                ],
                'constraints' => [
                    new ConstraintUrlExpanded(),
                ],
                'label' => 'Twitter',
                'required' => false,
            ])
            ->add('facebookURL', TextType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'facebook.example.email.placeholder',
                ],
                'constraints' => [
                    new ConstraintUrlExpanded(),
                ],
                'label' => 'Facebook',
                'required' => false,
            ])
            ->add('googleURL', TextType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'plus.google.example.email.placeholder',
                ],
                'constraints' => [
                    new ConstraintUrlExpanded(),
                ],
                'label' => 'Google Plus',
                'required' => false,
            ])
            ->add('youtubeURL', TextType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'youtube.example.email.placeholder',
                ],
                'constraints' => [
                    new ConstraintUrlExpanded(),
                ],
                'label' => 'Youtube',
                'required' => false,
            ])
        ;

        if ($this->isUserSectionRequired) {
            $builder
                ->add('firstname', TextType::class, [
                    'attr' => [
                        'class' => 'form-control',
                        'placeholder' => 'Catalá',
                    ],
                    'label' => 'First Name',
                    'required' => true,
                    'mapped' => false,
                    'constraints' => [
                        new NotBlank(),
                    ]
                ])
                ->add('lastname', TextType::class, [
                    'attr' => [
                        'class' => 'form-control',
                        'placeholder' => 'Joyeros',
                    ],
                    'label' => 'Last Name',
                    'required' => true,
                    'mapped' => false,
                    'constraints' => [
                        new NotBlank(),
                    ]
                ])
            ;
        }

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            /** @var BusinessProfile $businessProfile */
            $businessProfile = $event->getData() !== null ? $event->getData() : new BusinessProfile();

            $subscription = (new SubscriptionPlan())->setCode(SubscriptionPlanInterface::CODE_FREE);

            if ($businessProfile !== null && $businessProfile->getSubscriptionPlan() !== null) {
                $subscription = $businessProfile->getSubscriptionPlan();
            }

            $this->setupServiceAreasFormFields($businessProfile, $event->getForm());

            switch ($subscription->getCode()) {
                case SubscriptionPlanInterface::CODE_PRIORITY:
                    $this->setupPriorityPlanFormFields($businessProfile, $event->getForm());
                    break;
                case SubscriptionPlanInterface::CODE_PREMIUM_PLUS:
                    $this->setupPremiumPlusPlanFormFields($businessProfile, $event->getForm());
                    break;
                case SubscriptionPlanInterface::CODE_PREMIUM_GOLD:
                    $this->setupPremiumGoldPlanFormFields($businessProfile, $event->getForm());
                    break;
                case SubscriptionPlanInterface::CODE_PREMIUM_PLATINUM:
                    $this->setupPremiumPlatinumPlanFormFields($businessProfile, $event->getForm());
                    break;
                default:
                    $this->setupFreePlanFormFields($businessProfile, $event->getForm());
            }

            $this->setupCategories($businessProfile, $event->getForm());
        });
    }

    private function setupServiceAreasFormFields(BusinessProfile $businessProfile, FormInterface $form)
    {
        $milesOfMyBusinessFieldOptions = [
            'attr' => [
                'class' => 'form-control',
                'placeholder' => '100',
            ],
            'label' => 'Within miles of my business',
            'required' => true,
        ];

        $localitiesFieldOptions = [
            'attr' => [
                'class' => 'form-control select-control',
                'data-placeholder' => 'Select Localities',
                'multiple' => 'multiple',
            ],
            'class' => 'Domain\BusinessBundle\Entity\Locality',
            'label' => 'Localities',
            'multiple' => true,
            'query_builder' => function (LocalityRepository $repository) {
                return $repository->getAvailableLocalitiesQb();
            },
            'required' => false,
        ];

        $neighborhoodsFieldOptions = [
            'attr' => [
                'class' => 'form-control select-control',
                'data-placeholder' => 'Select Neighborhoods',
                'multiple' => 'multiple',
            ],
            'class' => 'Domain\BusinessBundle\Entity\Neighborhood',
            'label' => 'Neighborhoods',
            'multiple' => true,
            'query_builder' => function (NeighborhoodRepository $repository) {
                return $repository->getAvailableNeighborhoodsQb();
            },
            'required' => false,
        ];

        if ($businessProfile->getServiceAreasType() === BusinessProfile::SERVICE_AREAS_AREA_CHOICE_VALUE) {
            $localitiesFieldOptions['attr']['disabled'] = 'disabled';
            $neighborhoodsFieldOptions['attr']['disabled'] = 'disabled';
        } else {
            $milesOfMyBusinessFieldOptions['attr']['disabled'] = 'disabled';
            $milesOfMyBusinessFieldOptions['required'] = false;

            $neighborhoodsFieldOptions['attr']['disabled'] = 'disabled';
        }

        $form->add('milesOfMyBusiness', IntegerType::class, $milesOfMyBusinessFieldOptions);
        $form->add('localities', EntityType::class, $localitiesFieldOptions);
        $form->add('neighborhoods', EntityType::class, $neighborhoodsFieldOptions);
    }

    private function setupPremiumPlatinumPlanFormFields(BusinessProfile $businessProfile, FormInterface $form)
    {
        $this->setupPremiumGoldPlanFormFields($businessProfile, $form);

        $form->add('isSetVideo', CheckboxType::class, [
            'attr' => [
                'readonly' => 'readonly',
                'disabled' => 'disabled',
            ],
            'label' => 'yes',
            'required' => false,
            'read_only' => true,
        ]);

        $form->add('videoFile', FileType::class, [
            'attr' => [
                'style' => 'display:none',
                'accept' => 'mov, avi, mp4, wmv, flv, video/quicktime, application/x-troff-msvideo, video/avi,
                    video/msvideo, video/x-msvideo, video/mp4, video/x-ms-wmv, video/x-flv',
            ],
            'data_class' => null,
            'mapped' => false,
        ]);

        $form->add('video', WistiaMediaType::class, [
            'data_class' => 'Oxa\WistiaBundle\Entity\WistiaMedia',
            'by_reference' => false,
        ]);
    }

    private function setupPremiumGoldPlanFormFields(BusinessProfile $businessProfile, FormInterface $form)
    {
        $this->setupPremiumPlusPlanFormFields($businessProfile, $form);

        $form
            ->add(
                'files',
                'file',
                [
                    'attr' => [
                        'style' => 'display:none',
                        'accept' => 'jpg, png, gif, bmp, image/jpeg, image/pjpeg, image/png, image/gif,
                            image/bmp, image/x-windows-bmp',
                    ],
                    'data_class' => null,
                    'mapped' => false,
                    'multiple' => true,
                ]
            )
            ->add('images', \Symfony\Component\Form\Extension\Core\Type\CollectionType::class, [
                'entry_type' => BusinessGalleryType::class,
                'required' => false,
                'allow_add' => true,
                'allow_delete' => true,
                'allow_extra_fields' => true,
            ])
        ;
    }

    private function setupPremiumPlusPlanFormFields(BusinessProfile $businessProfile, FormInterface $form)
    {
        $this->setupPriorityPlanFormFields($businessProfile, $form);

        $isSloganSet = !empty($businessProfile->getSlogan());

        $isLogoSet = $businessProfile->getLogo() !== null;

        $form
            ->add('isSetLogo', CheckboxType::class, [
                'attr' => [
                    'readonly' => 'readonly',
                    'disabled' => 'disabled',
                ],
                'label' => 'yes',
                'required' => false,
                'read_only' => true,
                'data' => $isLogoSet
            ])
            ->add('isSetSlogan', CheckboxType::class, [
                'attr' => [
                    'readonly' => 'readonly',
                    'disabled' => 'disabled',
                ],
                'label' => 'yes',
                'required' => false,
                'read_only' => true,
                'data' => $isSloganSet,
            ])
            ->add('slogan', TextType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Organize, store, plan, prioritize',
                ],
                'label' => 'Slogan',
                'required' => false,
            ])
        ;
    }

    private function setupPriorityPlanFormFields(BusinessProfile $businessProfile, FormInterface $form)
    {
        $this->setupFreePlanFormFields($businessProfile, $form);

        $isAdSet = false;

        /** @var BusinessGallery $image */
        foreach ($businessProfile->getImages() as $image) {
            if ($image->getType() == OxaMediaInterface::CONTEXT_BANNER) {
                $isAdSet = true;
                break;
            }
        }

        $form
            ->add('isSetAd', CheckboxType::class, [
                'attr' => [
                    'readonly' => 'readonly',
                    'disabled' => 'disabled',
                ],
                'label' => 'yes',
                'required' => false,
                'read_only' => true,
                'data' => $isAdSet,
            ])
        ;
    }

    private function setupFreePlanFormFields(BusinessProfile $businessProfile, FormInterface $form)
    {
        $isMapSet = !empty($businessProfile->getLatitude()) && !empty($businessProfile->getLongitude());
        $isDescriptionSet = !empty($businessProfile->getDescription());

        $form
            ->add('isSetDescription', CheckboxType::class, [
                'attr' => [
                    'readonly' => 'readonly',
                    'disabled' => 'disabled',
                ],
                'label' => 'yes',
                'required' => false,
                'read_only' => true,
                'data' => $isDescriptionSet,
            ])
            ->add('isSetMap', CheckboxType::class, [
                'attr' => [
                    'readonly' => 'readonly',
                    'disabled' => 'disabled',
                ],
                'label' => 'yes',
                'required' => false,
                'read_only' => true,
                'data' => $isMapSet,
            ])
        ;
    }

    private function setupCategories(BusinessProfile $businessProfile, FormInterface $form)
    {
        $category      = $businessProfile->getCategory();
        $subcategories = $businessProfile->getSubcategories();

        $form
            ->add('categories', EntityType::class, [
                'attr' => [
                    'class' => 'form-control select-control select-multiple',
                    'data-placeholder' => 'Select category',
                    'multiple' => false,
                ],
                'class' => 'Domain\BusinessBundle\Entity\Category',
                'label' => 'Category',
                'multiple' => false,
                'query_builder' => function (CategoryRepository $repository) {
                    return $repository->getAvailableParentCategoriesQb();
                },
                'data' => $category,
                'mapped' => false,
                'validation_groups' => ['userBusinessProfile'],
            ])
            ->add('subcategories', EntityType::class, [
                'attr' => [
                    'class' => 'form-control select-control select-multiple',
                    'data-placeholder' => 'Select subcategories',
                    'multiple' => 'multiple',
                ],
                'class' => 'Domain\BusinessBundle\Entity\Category',
                'label' => 'Subcategory',
                'multiple' => true,
                'query_builder' => function (CategoryRepository $repository) {
                    return $repository->getAvailableCategoriesQb();
                },
                'data' => $subcategories,
                'mapped' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'allow_extra_fields' => true,
            'data_class' => 'Domain\BusinessBundle\Entity\BusinessProfile',
            'validation_groups' => function (FormInterface $form) {
                /** @var BusinessProfile $profile */
                $profile = $form->getData();

                if (BusinessProfile::SERVICE_AREAS_AREA_CHOICE_VALUE == $profile->getServiceAreasType()) {
                    return array('Default', 'service_area_chosen');
                } else {
                    return ['Default'];
                }
            },
        ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'domain_business_bundle_business_profile_form_type';
    }
}
