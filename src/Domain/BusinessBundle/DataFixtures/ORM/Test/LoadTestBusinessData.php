<?php
namespace Domain\BusinessBundle\DataFixture\Test;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Domain\BusinessBundle\Entity\Area;
use Domain\BusinessBundle\Entity\Brand;
use Domain\BusinessBundle\Entity\BusinessProfile;
use Domain\BusinessBundle\Entity\Category;
use Domain\BusinessBundle\Entity\PaymentMethod;
use Domain\BusinessBundle\Entity\Tag;
use Domain\BusinessBundle\Entity\Translation\AreaTranslation;
use Domain\BusinessBundle\Entity\Translation\BrandTranslation;
use Domain\BusinessBundle\Entity\Translation\BusinessProfileTranslation;
use Domain\BusinessBundle\Entity\Translation\CategoryTranslation;
use Domain\BusinessBundle\Entity\Translation\PaymentMethodTranslation;
use Domain\BusinessBundle\Entity\Translation\TagTranslation;
use Domain\MenuBundle\Model\MenuInterface;
use Oxa\Sonata\AdminBundle\Model\Fixture\OxaAbstractFixture;
use Oxa\Sonata\UserBundle\Entity\Group;
use Oxa\Sonata\UserBundle\Entity\User;
use Sonata\TranslationBundle\Model\Gedmo\AbstractPersonalTranslation;
use Sonata\TranslationBundle\Model\Gedmo\TranslatableInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class LoadTestBusinessData extends OxaAbstractFixture
{
    protected $order = 5;

    /**
     * @var Tag[] $tags
     */
    private $tags = [];

    /**
     * @var Brand[] $brands
     */
    private $brands = [];

    /**
     * @var Area[] $areas
     */
    private $areas = [];

    /**
     * @var Category[] $categories
     */
    private $categories = [];

    /**
     * @var PaymentMethod[] $paymentMethods
     */
    private $paymentMethods = [];

    protected function loadData()
    {
        $categories = $this->manager
            ->getRepository('DomainBusinessBundle:Category')
            ->findAll();

        foreach ($categories as $category) {
            $this->categories[$category->getName()] = $category;
        }

        $this->loadBusiness();
    }

    protected function loadBusiness()
    {
        // get data from yml file
        try {
            $data = Yaml::parse(file_get_contents(__DIR__ . '/Yml/businessData.yml'));
        } catch (ParseException $e) {
            throw new \Exception("Unable to parse the YML string: %s", $e->getMessage());
        }

        $addressManager = $this->container->get('domain_business.manager.address_manager');

        foreach ($data['businesses'] as $business => $item) {
            $googleResponse = $addressManager->validateAddress($item['google_address']);

            if ($googleResponse['error']) {
                throw new \Exception(
                    'Invalid business address. %s - %s, %s',
                    $business,
                    $item['google_address'],
                    $googleResponse['error']
                );
            }

            $object = new BusinessProfile();
            $object->setName($item['name']);
            $object->setEmail($item['email']);
            $object->setWebsite($item['website']);
            $object->setPhone($item['phone']);
            $object->setSlogan($item['slogan']);
            $object->setProduct($item['product']);
            $object->setDescription($item['description']);

            $object->setGoogleAddress($item['google_address']);
            $addressManager->setGoogleAddress($googleResponse['result'], $object);

            foreach ($item['tags'] as $value) {
                $record = $this->loadTag($value);
                $object->addTag($record);
            }

            foreach ($item['areas'] as $value) {
                $record = $this->loadArea($value);
                $object->addArea($record);
            }

            foreach ($item['brands'] as $value) {
                $record = $this->loadBrand($value);
                $object->addBrand($record);
            }

            foreach ($item['payment_methods'] as $value) {
                $record = $this->loadPaymentMethod($value);
                $object->addPaymentMethod($record);
            }

            foreach ($item['categories'] as $value) {
                $record = $this->loadCategory($value);
                $object->addCategory($record);
            }
            
            $object->setCountry($this->getReference('country.PR'));
            $object->setUser($this->getReference('user.manager'));

            $this->addTranslation(
                new BusinessProfileTranslation(),
                'name',
                sprintf('Spain %s', $item['name']),
                $object
            );
            $this->addTranslation(
                new BusinessProfileTranslation(),
                'slogan',
                sprintf('Spain %s', $item['slogan']),
                $object
            );
            $this->addTranslation(
                new BusinessProfileTranslation(),
                'product',
                sprintf('Spain %s', $item['product']),
                $object
            );
            $this->addTranslation(
                new BusinessProfileTranslation(),
                'description',
                sprintf('Spain %s', $item['description']),
                $object
            );

            $this->manager->persist($object);
        }
    }

    /**
     * @param AbstractPersonalTranslation $translation
     * @param string $fieldName
     * @param string $value
     * @param TranslatableInterface $object
     * @param string $locale
     */
    protected function addTranslation($translation, $fieldName, $value, $object, $locale = 'es')
    {
        $translation->setField($fieldName);
        $translation->setContent($value);
        $translation->setLocale($locale);
        $translation->setObject($object);
        $this->manager->persist($translation);
    }

    /**
     * @param $value
     * @return Tag
     */
    protected function loadTag($value)
    {
        if (array_key_exists($value, $this->tags)) {
            return $this->tags[$value];
        } else {
            $object = new Tag();
            $object->setName($value);

            $translation = new TagTranslation();
            $translation->setContent(sprintf('Spain %s', $value));
            $translation->setField('name');
            $translation->setLocale('es');
            $translation->setObject($object);

            $this->manager->persist($translation);
            $this->manager->persist($object);

            $this->tags[$value] = $object;

            // set reference to find this
            $this->addReference('tag.'.$value, $object);

            return $object;
        }
    }

    /**
     * @param $value
     * @return Brand
     */
    protected function loadBrand($value)
    {
        if (array_key_exists($value, $this->brands)) {
            return $this->brands[$value];
        } else {
            $object = new Brand();
            $object->setName($value);

            $translation = new BrandTranslation();
            $translation->setContent(sprintf('Spain %s', $value));
            $translation->setField('name');
            $translation->setLocale('es');
            $translation->setObject($object);

            $this->manager->persist($translation);
            $this->manager->persist($object);

            $this->brands[$value] = $object;

            // set reference to find this
            $this->addReference('brand.'.$value, $object);

            return $object;
        }
    }

    /**
     * @param $value
     * @return Area
     */
    protected function loadArea($value)
    {
        if (array_key_exists($value, $this->areas)) {
            return $this->areas[$value];
        } else {
            $object = new Area();
            $object->setName($value);

            $translation = new AreaTranslation();
            $translation->setContent(sprintf('Spain %s', $value));
            $translation->setField('name');
            $translation->setLocale('es');
            $translation->setObject($object);

            $this->manager->persist($translation);
            $this->manager->persist($object);

            $this->areas[$value] = $object;

            // set reference to find this
            $this->addReference('area.'.$value, $object);

            return $object;
        }
    }

    /**
     * @param $value
     * @return PaymentMethod
     */
    protected function loadPaymentMethod($value)
    {
        if (array_key_exists($value, $this->paymentMethods)) {
            return $this->paymentMethods[$value];
        } else {
            $object = new PaymentMethod();
            $object->setName($value);

            $translation = new PaymentMethodTranslation();
            $translation->setContent(sprintf('Spain %s', $value));
            $translation->setField('name');
            $translation->setLocale('es');
            $translation->setObject($object);

            $this->manager->persist($translation);
            $this->manager->persist($object);

            $this->paymentMethods[$value] = $object;

            // set reference to find this
            $this->addReference('paymentMethod.'.$value, $object);

            return $object;
        }
    }

    /**
     * @param $value
     * @return Category
     */
    protected function loadCategory($value)
    {
        if (array_key_exists($value, $this->categories)) {
            return $this->categories[$value];
        } else {
            $object = new Category();
            $object->setName($value);

            $translation = new CategoryTranslation();
            $translation->setContent(sprintf('Spain %s', $value));
            $translation->setField('name');
            $translation->setLocale('es');
            $translation->setObject($object);

            $this->manager->persist($translation);
            $this->manager->persist($object);

            $this->categories[$value] = $object;

            // set reference to find this
            $this->addReference('category.'.$value, $object);

            return $object;
        }
    }
}