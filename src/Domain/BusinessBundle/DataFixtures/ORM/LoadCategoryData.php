<?php
namespace Domain\BusinessBundle\DataFixture\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Domain\BusinessBundle\Entity\Category;
use Domain\BusinessBundle\Util\SlugUtil;
use Domain\MenuBundle\Model\MenuModel;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadCategoryData extends AbstractFixture implements ContainerAwareInterface, OrderedFixtureInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var ObjectManager
     */
    protected $manager;

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->manager = $manager;

        $data = MenuModel::getAllCategoriesNames();

        foreach ($data as $menuCode => $value) {
            $object = new Category();
            $object->setName($value['en']);

            // set to both locales
            $object->setSearchTextEn($value['en']);
            $object->setSearchTextEs($value['es']);

            $object->setSlugEn(SlugUtil::convertSlug($value['en']));
            $object->setSlugEs(SlugUtil::convertSlug($value['es']));

            $this->manager->persist($object);

            // set reference to find this
            $this->addReference('category.'.$menuCode, $object);
        }

        $manager->flush();
    }

    /**
     * Get the order of this fixture
     *
     * @return integer
     */
    public function getOrder()
    {
        return 4;
    }

    /**
     * @param ContainerInterface|null $container
     * @return $this
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;

        return $this;
    }
}
