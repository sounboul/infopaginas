<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Domain\EmergencyBundle\Entity\EmergencyCategory;
use Oxa\ConfigBundle\Entity\Config;
use Oxa\ConfigBundle\Model\ConfigInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Version20180315145234 extends AbstractMigration implements ContainerAwareInterface
{
    /**
     * @var $em \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var $container ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
        $this->em        = $this->container->get('doctrine.orm.entity_manager');
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->createConfigValue();
        $this->updateEmergencyCategorySearchName();

        $this->em->flush();
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {

    }

    protected function createConfigValue()
    {
        $config = $this->em->getRepository(Config::class)->findOneBy([
            'key' => ConfigInterface::GOOGLE_OPTIMIZATION_CONTAINER_ID,
        ]);

        if (!$config) {
            $config = new Config();
            $config->setKey(ConfigInterface::GOOGLE_OPTIMIZATION_CONTAINER_ID);
            $config->setTitle('Google optimize container id');
            $config->setValue('GTM-5G5BLWP');
            $config->setFormat('text');
            $config->setDescription(
                'Google optimize id config'
            );
            $config->setIsActive(true);

            $this->em->persist($config);
        }
    }

    protected function updateEmergencyCategorySearchName()
    {
        $categories = $this->em->getRepository(EmergencyCategory::class)->findAll();

        foreach ($categories as $category) {
            $category->updateSearchName();
        }
    }
}
