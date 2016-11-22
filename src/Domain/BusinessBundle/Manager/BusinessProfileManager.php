<?php

namespace Domain\BusinessBundle\Manager;

use AntiMattr\GoogleBundle\Analytics;
use AntiMattr\GoogleBundle\Analytics\Impression;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Domain\BusinessBundle\Entity\BusinessProfile;
use Domain\BusinessBundle\Entity\BusinessProfilePhone;
use Domain\BusinessBundle\Entity\ChangeSet;
use Domain\BusinessBundle\Entity\ChangeSetEntry;
use Domain\BusinessBundle\Entity\Media\BusinessGallery;
use Domain\BusinessBundle\Entity\Review\BusinessReview;
use Domain\BusinessBundle\Form\Type\BusinessProfileFormType;
use Domain\BusinessBundle\Model\SubscriptionPlanInterface;
use Domain\BusinessBundle\Repository\BusinessGalleryRepository;
use Domain\BusinessBundle\Repository\BusinessReviewRepository;
use Domain\BusinessBundle\Util\ChangeSetCalculator;
use Domain\BusinessBundle\Util\Task\PhoneChangeSetUtil;
use FOS\UserBundle\Model\UserInterface;
use Gedmo\Translatable\TranslatableListener;
use Oxa\ManagerArchitectureBundle\Model\Manager\Manager;
use Oxa\Sonata\MediaBundle\Entity\Media;
use Oxa\Sonata\MediaBundle\Model\OxaMediaInterface;
use Oxa\Sonata\UserBundle\Entity\User;
use Oxa\WistiaBundle\Entity\WistiaMedia;
use Oxa\WistiaBundle\Manager\WistiaMediaManager;
use Domain\BusinessBundle\Entity\Address\Country;
use Sonata\MediaBundle\Entity\MediaManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Oxa\GeolocationBundle\Model\Geolocation\LocationValueObject;
use Domain\SearchBundle\Model\DataType\SearchDTO;
use Domain\SearchBundle\Model\DataType\DCDataDTO;

/**
 * Class BusinessProfileManager
 * @package Domain\BusinessBundle\Manager
 */
class BusinessProfileManager extends Manager
{
    const DEFAULT_LOCALE_NAME = 'San Juan';

    /**
     * @var CategoryManager
     */
    protected $categoryManager;

    /** @var UserInterface */
    private $currentUser = null;

    /** @var  TranslatableListener */
    private $translatableListener;

    /** @var FormFactory */
    private $formFactory;

    /** @var WistiaMediaManager */
    private $wistiaMediaManager;

    /** @var Analytics $analytics */
    private $analytics;

    /** @var  MediaManager */
    private $sonataMediaManager;

    /** @var ContainerInterface $container */
    private $container;

    /**
     * Manager constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container) {
        $this->container = $container;

        $this->em = $container->get('doctrine.orm.entity_manager');

        $this->categoryManager = $container->get('domain_business.manager.category');

        $tokenStorage = $container->get('security.token_storage');

        if ($tokenStorage->getToken() !== null) {
            $this->currentUser = $tokenStorage->getToken()->getUser();
        }

        $this->currentUser = $this->em->getRepository(User::class)->find(1);

        $this->translatableListener = $container->get('sonata_translation.listener.translatable');

        $this->formFactory = $container->get('form.factory');

        $this->wistiaMediaManager = $container->get('oxa.manager.wistia_media');

        $this->sonataMediaManager = $container->get('sonata.media.manager.media');

        $this->analytics = $container->get('google.analytics');
    }

    public function searchByPhraseAndLocation(string $phrase, LocationValueObject $location, $categoryFilter = null)
    {
        $locationName = $location->name;
        if (empty($locationName)) {
            $locationName = self::DEFAULT_LOCALE_NAME;
        }

        // TODO Move to filtering functionality
        $phrase = preg_replace("/[^a-zA-Z0-9\s]+/", "", $phrase);
        return $this->getRepository()->searchWithQueryBuilder($phrase, $locationName, $categoryFilter);
    }

    public function searchAutosuggestByPhraseAndLocation($query, $locale)
    {
        $categories = $this->categoryManager->searchAutosuggestByName($query, $locale);
        $businessProfiles = $this->getRepository()->searchAutosuggestWithBuilder($query, ucwords($locale));

        $result = array_merge($categories, $businessProfiles);

        return $result;
    }

    public function searchWithMapByPhraseAndLocation(string $phrase, string $location)
    {
        if (!$location) {
            $location = self::DEFAULT_LOCALE_NAME;
        }

        // TODO Move to filtering functionality
        $phrase = preg_replace("/[^a-zA-Z0-9\s]+/", "", $phrase);
        return $this->getRepository()->searchWithQueryBuilder($phrase, $location);
    }

    public function getLocationMarkersFromProfileData(array $profilesList)
    {
        $profilesArray = [];

        /** @var BusinessProfile $profile */
        foreach ($profilesList as $profile) {
            $logoPath = null;
            $logo = $profile->getLogo();

            if ($logo) {
                $provider = $this->container->get($logo->getProviderName());

                $logoPath = $provider->generatePublicUrl($logo, 'admin');
            }

            $profilesArray[] = [
                "id"            => $profile->getId(),
                "name"          => $profile->getName(),
                "address"       => $profile->getShortAddress(),
                "reviewsCount"  => $profile->getBusinessReviewsCount(),
                "logo"          => $logoPath,
                "latitude"      => $profile->getLatitude(),
                "longitude"     => $profile->getLongitude(),
                'rating'        => $this->calculateReviewsAvgRatingForBusinessProfile($profile),
                "profileUrl"    => $this->container->get('router')->generate('domain_business_profile_view', [
                    'slug'          => $profile->getSlug(),
                    'citySlug'      => $profile->getCitySlug(),
                ]),
            ];
        }

        return json_encode($profilesArray);
    }

    public function search(SearchDTO $searchParams, string $locale)
    {
        $searchResultsData = $this->getRepository()->search($searchParams, $locale);
        $searchResultsData = array_map(function ($item) {
            return $item[0]->setDistance($item['distance']);
        }, $searchResultsData);

        return $searchResultsData;
    }

    public function searchCatalog(SearchDTO $searchParams, string $locale)
    {
        $searchResultsData = $this->getRepository()->searchCatalog($searchParams, $locale);

        $searchResultsData = array_map(function ($item) {
            return $item[0]->setDistance($item['distance']);
        }, $searchResultsData);

        return $searchResultsData;
    }

    /**
     * @param int $id
     * @param string $locale
     * @return null|object
     */
    public function find(int $id, string $locale = 'en')
    {
        $business = $this->getRepository()->find($id);

        $this->getTranslatableListener()->setTranslatableLocale($locale);
        $this->getTranslatableListener()->setTranslationFallback('');

        $this->getEntityManager()->refresh($business);

        return $business;
    }

    /**
     * @param string $uid
     * @return null|object
     */
    public function findByUid(string $uid)
    {
        $businessProfile = $this->getRepository()->findOneBy([
            'uid' => $uid,
        ]);

        return $businessProfile;
    }

    /**
     * @param string $slug
     * @return null|object
     */
    public function findBySlug(string $slug)
    {
        $businessProfile = $this->getRepository()->findOneBy([
            'slug' => $slug,
            'isActive' => true,
        ]);

        return $businessProfile;
    }

    /**
     * @return BusinessProfile
     */
    public function createProfile() : BusinessProfile
    {
        $profile = new BusinessProfile();
        $profile->setIsActive(false);

        return $profile;
    }

    /**
     * @param BusinessProfile $businessProfile
     * @param string $locale
     */
    public function saveProfile(BusinessProfile $businessProfile, string $locale = 'en')
    {
        if (!$businessProfile->getId()) {
            $businessProfile->setIsActive(false);
        }

        if ($locale !== BusinessProfile::DEFAULT_LOCALE) {
            $businessProfile->setLocale($locale);
        }

        foreach ($businessProfile->getImages() as $image) {
            $clonedImage = clone $image;
            $clonedImage->setBusinessProfile($businessProfile);
            $this->getEntityManager()->persist($clonedImage);
        }

        $this->commit($businessProfile);
    }

    /**
     * @param BusinessProfile $businessProfile
     */
    public function activate(BusinessProfile $businessProfile)
    {
        $businessProfile->setIsActive(true);
        $this->commit($businessProfile);
    }

    /**
     * @param BusinessProfile $businessProfile
     */
    public function deactivate(BusinessProfile $businessProfile)
    {
        $businessProfile->setIsActive(false);
        $businessProfile->setIsClosed(true);
        $this->commit($businessProfile);
    }

    /**
     * @param BusinessProfile $businessProfile
     */
    public function remove(BusinessProfile $businessProfile)
    {
        $this->drop($businessProfile);
    }


    public function publish(BusinessProfile $businessProfile, ChangeSet $changeSet, $locale = 'en')
    {
        $accessor = PropertyAccess::createPropertyAccessor();

        /** @var ChangeSetEntry $change */
        foreach ($changeSet->getEntries() as $change) {
            switch ($change->getAction()) {
                case ChangeSetCalculator::PROPERTY_ADD:
                    $accessor->setValue($businessProfile, $change->getFieldName(), $change->getNewValue());
                    break;
                case ChangeSetCalculator::PROPERTY_REMOVE:
                    $accessor->setValue($businessProfile, $change->getFieldName(), null);
                    break;
                case ChangeSetCalculator::PROPERTY_CHANGE:
                    if (!$change->getClassName()) {
                        if (strstr($change->getFieldName(), 'isSet') && empty($change->getNewValue())) {
                            $newValue = false;
                        } else {
                            $newValue = $change->getNewValue();
                        }
                        $accessor->setValue($businessProfile, $change->getFieldName(), $newValue);
                    } else {
                        if ($change->getClassName() === BusinessProfilePhone::class) {
                            $collection = PhoneChangeSetUtil::getPhonesCollectionsFromChangeSet(
                                $change,
                                $businessProfile,
                                $this->getEntityManager()
                            );

                            $accessor->setValue($businessProfile, $change->getFieldName(), $collection);
                        } else {
                            $ids = array_map(function($element) {
                                return $element->id;
                            }, json_decode($change->getNewValue()));

                            $collection = $this->getEntitiesByIds($change->getClassName(), $ids);
                            $accessor->setValue($businessProfile, $change->getFieldName(), $collection);
                        }
                    }
                    break;
                case ChangeSetCalculator::IMAGE_ADD:
                    $data = json_decode($change->getNewValue());
                    $media = $this->getEntityManager()->getRepository(Media::class)->find($data->media);
                    if ($data->type == OxaMediaInterface::CONTEXT_BUSINESS_PROFILE_LOGO) {
                        $businessProfile->addImage(BusinessGallery::createFromChangeSet($data, $media));
                        $businessProfile->setLogo($media);
                    } else {
                        $businessProfile->addImage(BusinessGallery::createFromChangeSet($data, $media));
                        $this->getSonataMediaManager()->save($media, false);
                    }
                    break;
                case ChangeSetCalculator::IMAGE_REMOVE:
                    $data = json_decode($change->getOldValue());
                    $gallery = $this->getEntityManager()->getRepository(BusinessGallery::class)->find($data->id);
                    if ($gallery) {
                        $businessProfile->removeImage($gallery);
                    }
                    break;
                case ChangeSetCalculator::IMAGE_UPDATE:
                    $data = json_decode($change->getNewValue());
                    $gallery = $this->getEntityManager()->getRepository(BusinessGallery::class)->find($data->id);

                    if (isset($data->description)) {
                        $gallery->setDescription($data->description[1]);
                    }

                    if (isset($data->isPrimary)) {
                        $gallery->setIsPrimary($data->isPrimary[1]);
                    }

                    if (isset($data->type)) {
                        $gallery->setType($data->type[1]);

                        if ($gallery->getType() == OxaMediaInterface::CONTEXT_BUSINESS_PROFILE_LOGO) {
                            $media = $this->makeLogoFromPhoto($gallery->getMedia());
                            $businessProfile->setLogo($media);
                            $gallery->setMedia($media);
                        } else {
                            $media = $gallery->getMedia();

                            if ($data->type[0] != $data->type[1]) {
                                $businessProfile->setLogo(null);
                            }

                            if ($media->getContext() == OxaMediaInterface::CONTEXT_BUSINESS_PROFILE_LOGO) {
                                $media = $this->makePhotoFromLogo($gallery->getMedia());
                                $gallery->setMedia($media);
                                $businessProfile->addImage($gallery);
                            }
                        }
                    }
                    $this->getEntityManager()->persist($gallery);
                    break;
                case ChangeSetCalculator::VIDEO_ADD:
                    $data = json_decode($change->getNewValue());
                    $video = $this->getEntityManager()->getRepository(WistiaMedia::class)->find($data->id);
                    $businessProfile->setVideo($video);
                    break;
                case ChangeSetCalculator::VIDEO_REMOVE:
                    $businessProfile->setVideo(null);
                    break;
                case ChangeSetCalculator::VIDEO_UPDATE:
                    $data = json_decode($change->getNewValue());
                    //if video was replaced
                    if (!empty($change->getOldValue())) {
                        $video = $this->getEntityManager()->getRepository(WistiaMedia::class)->find($data->id);
                        $businessProfile->setVideo($video);
                    } else {
                        if (isset($data->description)) {
                            $businessProfile->getVideo()->setDescription($data->description[1]);
                        }
                        if (isset($data->name)) {
                            $businessProfile->getVideo()->setName($data->name[1]);
                        }
                    }
                    break;
            }
        }

        $this->getEntityManager()->persist($businessProfile);
        $this->getEntityManager()->flush();
    }

    /**
     * @param BusinessProfile $businessProfile
     * @return \Symfony\Component\Form\FormInterface
     */
    public function getBusinessProfileAsForm(BusinessProfile $businessProfile)
    {
        return $this->formFactory->create(new BusinessProfileFormType(), $businessProfile);
    }

    /**
     * @param BusinessProfile $businessProfile
     * @return BusinessProfile
     */
    public function checkBusinessProfileVideo(BusinessProfile $businessProfile)
    {
        if ($businessProfile->getVideo()) {
            $video = $this->getWistiaMediaManager()->updateNameAndDescriptionByWistiaID($businessProfile->getVideo());
            $businessProfile->setVideo($video);
        }

        return $businessProfile;
    }

    /**
     * @param BusinessProfile $businessProfile
     * @return array
     */
    public function getBusinessProfileAdvertisementImages(BusinessProfile $businessProfile)
    {
        $subscriptionPlanCode = $businessProfile->getSubscription()->getSubscriptionPlan()->getCode();

        if ($subscriptionPlanCode > SubscriptionPlanInterface::CODE_PREMIUM_PLUS) {
            $advertisements = $this->getBusinessGalleryRepository()
                ->findBusinessProfileAdvertisementImages($businessProfile);

            return $advertisements;
        }

        return [];
    }

    /**
     * @param BusinessProfile $businessProfile
     * @return array
     */
    public function getBusinessProfilePhotoImages(BusinessProfile $businessProfile)
    {
        $subscriptionPlanCode = $businessProfile->getSubscription()->getSubscriptionPlan()->getCode();

        if ($subscriptionPlanCode > SubscriptionPlanInterface::CODE_PREMIUM_PLUS) {
            $photos = $this->getBusinessGalleryRepository()->findBusinessProfilePhotoImages($businessProfile);
            return $photos;
        }

        return [];
    }

    /**
     * @param BusinessProfile $businessProfile
     * @return null|object
     */
    public function getLastReviewForBusinessProfile(BusinessProfile $businessProfile)
    {
        $lastReview = $this->getBusinessProfileReviewsRepository()->findBusinessProfileLastReview($businessProfile);
        return $lastReview;
    }

    /**
     * @param BusinessProfile $businessProfile
     * @return float|int
     */
    public function calculateReviewsAvgRatingForBusinessProfile(BusinessProfile $businessProfile)
    {
        $rating = 0;

        $reviewsAmount = $this->getReviewsCountForBusinessProfile($businessProfile);

        $reviews = $this->getBusinessProfileReviewsRepository()->findReviewsByBusinessProfile($businessProfile);

        if ($reviewsAmount) {
            foreach ($reviews as $review) {
                $rating += (int) $review->getRating();
            }

            return round($rating / $reviewsAmount);
        }

        return 0;
    }

    /**
     * @param BusinessProfile $businessProfile
     */
    public function drop(BusinessProfile $businessProfile)
    {
        $this->getEntityManager()->remove($businessProfile);
        $this->getEntityManager()->flush();
    }

    /**
     * @param SearchDTO $searchParams
     * @param string    $locale
     * @return mixed
     */
    public function countSearchResults(SearchDTO $searchParams, string $locale)
    {
        return $this->getRepository()->countSearchResults($searchParams, $locale);
    }

    /**
     * @param SearchDTO $searchParams
     * @param string    $locale
     * @return mixed
     */
    public function countCatalogSearchResults(SearchDTO $searchParams, string $locale)
    {
        return $this->getRepository()->countCatalogSearchResults($searchParams, $locale);
    }

    /**
     * @param BusinessProfile $businessProfile
     * @return int
     */
    public function getReviewsCountForBusinessProfile(BusinessProfile $businessProfile)
    {
        return $this->getBusinessProfileReviewsRepository()->getReviewsCountForBusinessProfile($businessProfile);
    }

    /**
     * @param $searchResultsDTO
     * @return mixed
     */
    public function removeItemWithHiddenAddress($searchResultsDTO)
    {
        foreach ($searchResultsDTO->resultSet as $key => $item)
        {
            if ($item->getHideAddress()) {
                unset($searchResultsDTO->resultSet[$key]);
            }
        }

        return $searchResultsDTO;
    }

    /**
     * @param array $businessProfiles
     */
    public function trackBusinessProfilesCollectionImpressions(array $businessProfiles)
    {
        /** @var BusinessProfile $businessProfile */
        foreach ($businessProfiles as $businessProfile) {
            $impression = new Impression();
            $impression->setSku($businessProfile->getSlug());
            $impression->setTitle($businessProfile->getName());
            $impression->setAction('detail');
            $impression->setBrand($businessProfile->getBrands());
            $impression->setCategory($businessProfile->getCategories()->first());
            $impression->setList('Search Results');

            $this->getGoogleAnalytics()->addImpression($impression);
        }
    }

    public function isAdUsageReportAllowedForBusiness(BusinessProfile $businessProfile)
    {
        $code = $businessProfile->getSubscription()->getSubscriptionPlan()->getCode();
        return $code >= SubscriptionPlanInterface::CODE_PRIORITY;
    }

    public function findOneBusinessProfile()
    {
        return $this->getRepository()->findOneBy([]);
    }

    protected function makeLogoFromPhoto(Media $media)
    {
        $media = clone $media;
        $media->setContext(OxaMediaInterface::CONTEXT_BUSINESS_PROFILE_LOGO);
        $this->getSonataMediaManager()->save($media, false);

        return $media;
    }

    protected function makePhotoFromLogo(Media $media)
    {
        $media = clone $media;
        $media->setContext(OxaMediaInterface::CONTEXT_BUSINESS_PROFILE_IMAGES);
        $this->getSonataMediaManager()->save($media, false);

        return $media;
    }

    /**
     * Persist & flush
     *
     * @access private
     * @param BusinessProfile $businessProfile
     */
    private function commit(BusinessProfile $businessProfile)
    {
        $this->getEntityManager()->persist($businessProfile);
        $this->getEntityManager()->flush();
    }

    /**
     * @return MediaManager
     */
    protected function getSonataMediaManager() : MediaManager
    {
        return $this->sonataMediaManager;
    }

    /**
     * @return BusinessGalleryRepository
     */
    private function getBusinessGalleryRepository() : BusinessGalleryRepository
    {
        return $this->getEntityManager()->getRepository(BusinessGallery::class);
    }

    /**
     * @return WistiaMediaManager
     */
    private function getWistiaMediaManager() : WistiaMediaManager
    {
        return $this->wistiaMediaManager;
    }

    private function getGoogleAnalytics()
    {
        return $this->analytics;
    }

    /**
     * @return TranslatableListener
     */
    private function getTranslatableListener() : TranslatableListener
    {
        return $this->translatableListener;
    }

    /**
     * @return BusinessReviewRepository
     */
    private function getBusinessProfileReviewsRepository()
    {
        return $this->getEntityManager()->getRepository(BusinessReview::class);
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager() : EntityManager
    {
        return $this->em;
    }

    public function getSlugDcDataDTO(BusinessProfile $profile) : DCDataDTO
    {
        return new DCDataDTO(array(), '', array(), $profile->getSlug());
    }

    private function getEntitiesByIds($class, $ids)
    {
        /** @var EntityRepository $repo */
        $repo = $this->getEntityManager()->getRepository($class);
        $objects = $repo->createQueryBuilder('qb')
            ->where('qb.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()->getResult();

        return $objects;
    }

    public function getSubcategories($categoryId, $businessProfileId)
    {
        $data = [];
        $checkedSubcategoryIds = [];

        if ($businessProfileId) {
            /* @var BusinessProfile $businessProfile */
            $businessProfile       = $this->getRepository()->find($businessProfileId);
            $checkedSubcategoryIds = $this->getBusinessProfileSubcategoryIds($businessProfile);
        }

        $subcategories = $this->getSubcategoriesForCategory($categoryId);

        foreach ($subcategories as $key => $subcategory) {
            $data[$key] = [
                'id'       => $subcategory->getId(),
                'name'     => $subcategory->getName(),
                'selected' => false,
            ];

            if (in_array($subcategory->getId(), $checkedSubcategoryIds)) {
                $data[$key]['selected'] = true;
            }
        }

        return $data;
    }

    public function getSubcategoriesForCategory($categoryId)
    {
        $subcategories = $this->getEntityManager()->getRepository('DomainBusinessBundle:Category')
            ->getAvailableSubCategories($categoryId);

        return $subcategories;
    }

    public function getCategoriesByIds($ids)
    {
        $subcategories = $this->getEntityManager()->getRepository('DomainBusinessBundle:Category')
            ->getAvailableCategoriesByIds($ids);

        return $subcategories;
    }

    /**
     * @param BusinessProfile $businessProfile
     *
     * @return array
     */
    public function getBusinessProfileSubcategoryIds(BusinessProfile $businessProfile)
    {
        $data = [];
        $subcategories = $businessProfile->getSubcategories();

        foreach ($subcategories as $subcategory) {
            $data[] = $subcategory->getId();
        }

        return $data;
    }

    /**
     * see https://developers.google.com/search/docs/data-types/local-businesses
     * @param BusinessProfile[]  $businessProfiles
     * @param bool               $showAll
     *
     * @return string
     */
    public function buildBusinessProfilesSchema($businessProfiles, $showAll = false)
    {
        $schema = [];

        foreach ($businessProfiles as $businessProfile) {
            $schemaItem = $this->buildBaseLocalBusinessSchema($businessProfile);

            $email = $businessProfile->getEmail();
            if ($email) {
                $schemaItem['email'] = $email;
            }

            if (!$businessProfile->getHideAddress()) {
                $customAddress = $businessProfile->getCustomAddress();

                if (!$customAddress) {
                    $country = $businessProfile->getCountry();

                    $schemaItem['address'] = [
                        '@type'           => 'PostalAddress',
                        'addressLocality' => $businessProfile->getCity(),
                        'streetAddress'   => $businessProfile->getStreetAddress(),
                        'postalCode'      => $businessProfile->getZipCode(),
                    ];

                    if ($country->getShortName() === strtoupper(Country::PUERTO_RICO_SHORT_NAME)) {
                        $schemaItem['address']['addressCountry'] = 'US';
                        $schemaItem['address']['addressRegion'] = $country->getName();
                    } else {
                        $schemaItem['address']['addressCountry'] = $country->getName();
                        $state = $businessProfile->getState();

                        if ($state) {
                            $schemaItem['address']['addressRegion'] = $state;
                        }
                    }
                }

                $schemaItem['hasMap'] = 'https://maps.google.com/?q=' . $businessProfile->getLatLng();

                $schemaItem['geo'] = [
                    '@type'     => 'GeoCoordinates',
                    'latitude'  => $businessProfile->getLatitude(),
                    'longitude' => $businessProfile->getLongitude(),
                ];
            }

            $openingHours = $businessProfile->getWorkingHours();
            if ($openingHours) {
                $schemaItem['openingHours'] = $openingHours;
            }

            $phones = $businessProfile->getPhones();
            foreach ($phones as $phone) {
                $schemaItem['telephone'] = $phone->getPhone();
            }

            if (!$businessProfile->getBusinessReviews()->isEmpty()) {
                $schemaItem['aggregateRating'] = [
                    '@type'       => 'AggregateRating',
                    'worstRating' => BusinessReview::RATING_MIN_VALUE,
                    'bestRating'  => BusinessReview::RATING_MAX_VALUE,
                    'ratingValue' => $businessProfile->getBusinessReviewsAvgMark(),
                    'ratingCount' => $businessProfile->getBusinessReviewsCount(),
                ];
            }

            if ($showAll) {
                $description = $businessProfile->getDescription();
                if ($description) {
                    $schemaItem['description'] = $description;
                }

                $sameAs = $this->addSameAsUrl([], $businessProfile->getFacebookURL());
                $sameAs = $this->addSameAsUrl($sameAs, $businessProfile->getTwitterURL());
                $sameAs = $this->addSameAsUrl($sameAs, $businessProfile->getGoogleURL());
                $sameAs = $this->addSameAsUrl($sameAs, $businessProfile->getYoutubeURL());

                $photos = $this->getBusinessProfilePhotoImages($businessProfile);

                foreach ($photos as $photo) {
                    $schemaItem['image'][] = $this->getMediaPublicUrl($photo->getMedia(), 'preview');
                }

                $lastReview = $this->getLastReviewForBusinessProfile($businessProfile);

                if ($lastReview) {
                    $schemaItem['review'] = $this->buildReviewItem($lastReview);
                }
            } else {
                if (empty($schemaItem['image'])) {
                    $schemaItem['image'] = $this->getDefaultLocalBusinessImage($schemaItem);
                }
            }

            if ((isset($sameAs) and $sameAs)) {
                if (isset($schemaItem['sameAs'])) {
                    $sameAs[] = $schemaItem['sameAs'];
                    $schemaItem['sameAs'] = $sameAs;
                }

                $schemaItem['sameAs'] = $sameAs;
            }

            $schema[] = $schemaItem;
        }

        return json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * see https://developers.google.com/search/docs/data-types/reviews
     * @param BusinessReview[] $reviews
     * @param BusinessProfile $businessProfile
     *
     * @return string
     */
    public function buildBusinessProfileReviewsSchema($reviews, $businessProfile)
    {
        $schemaItem = $this->buildBaseLocalBusinessSchema($businessProfile);

        foreach ($reviews as $review) {
            $schemaItem['review'][] = $this->buildReviewItem($review);
        }

        $schemaItem['image'] = $this->getDefaultLocalBusinessImage($schemaItem);

        return json_encode([$schemaItem], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @param Media  $media
     * @param string $format
     *
     * @return string
     */
    public function getMediaPublicUrl($media, $format)
    {
        $provider = $this->container->get($media->getProviderName());
        $format   = $provider->getFormatName($media, $format);
        $url      = $provider->generatePublicUrl($media, $format);

        return $url;
    }

    /**
     * @param BusinessProfile $businessProfile
     *
     * @return array
     */
    private function buildBaseLocalBusinessSchema($businessProfile)
    {
        $schemaItem = [
            '@context'   => 'http://schema.org',
            '@type'      => 'LocalBusiness',
            'name'       => $businessProfile->getName(),
            'branchCode' => $businessProfile->getUid(),
        ];

        $logo = $businessProfile->getLogo();
        if ($logo) {
            $schemaItem['logo'] = $this->getMediaPublicUrl($logo, 'preview');
        }

        $url = $this->getBusinessProfileUrl($businessProfile);
        if ($businessProfile->getWebsite()) {
            $schemaItem['url']    = $businessProfile->getWebsiteLink();
            $schemaItem['sameAs'] = $url;
        } else {
            $schemaItem['url'] = $url;
        }

        return $schemaItem;
    }

    /**
     * @param BusinessReview $review
     *
     * @return array
     */
    private function buildReviewItem($review)
    {
        $item = [
            '@type' => 'Review',
            'author' => [
                '@type' => 'Person',
                'name'  => $review->getUsername(),
            ],
            'datePublished' => $review->getCreatedAt()->format('c'),
            'description'   => $review->getContent(),
            'reviewRating' => [
                '@type'       => 'Rating',
                'worstRating' => BusinessReview::RATING_MIN_VALUE,
                'bestRating'  => BusinessReview::RATING_MAX_VALUE,
                'ratingValue' => $review->getRating(),
            ],
        ];

        return $item;
    }

    /**
     * @param array           $schemaItem
     *
     * @return string
     */
    private function getDefaultLocalBusinessImage($schemaItem)
    {
        if (isset($schemaItem['image']) and $schemaItem['image']) {
            $image = $schemaItem['image'];
        } elseif (isset($schemaItem['logo']) and $schemaItem['logo']) {
            $image = $schemaItem['logo'];
        } else {
            $request = $this->container->get('request');
            $image   = 'https://' . $request->getHost() . '/assets/images/header-logo.png';
        }

        return $image;
    }

    /**
     * @param array  $sameAs
     * @param string $url
     *
     * @return string
     */
    private function addSameAsUrl($sameAs, $url)
    {
        if ($url) {
            $sameAs[] = $url;
        }

        return $sameAs;
    }

    /**
     * @param BusinessProfile $businessProfile
     *
     * @return string
     */
    public function getBusinessProfileUrl($businessProfile)
    {
        $url = $this->container->get('router')->generate(
            'domain_business_profile_view',
            [
                'citySlug' => $businessProfile->getCitySlug(),
                'slug'     => $businessProfile->getSlug(),
            ],
            true
        );

        return $url;
    }
}
