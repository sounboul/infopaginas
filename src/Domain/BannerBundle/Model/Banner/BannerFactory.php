<?php

namespace Domain\BannerBundle\Model\Banner;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Domain\BannerBundle\Model\TypeModel;
use Oxa\ManagerArchitectureBundle\Model\Factory\Factory;
use Domain\BannerBundle\Model\TypeInterface as BannerType;
use Domain\BannerBundle\Entity\Banner;

class BannerFactory extends Factory
{
    const UNDEFINED_BANNER_TYPE_ERROR = 'Undefined banner type!';

    protected $bannersCollection;

    public function __construct(EntityManager $entityManager)
    {
        parent::__construct($entityManager);

        $this->bannersCollection = new ArrayCollection;
    }

    public function prepearBanners(array $banners)
    {
        foreach ($banners as $bannerKey) {
            if ($this->bannersCollection->containsKey($bannerKey)) {
                throw new \Exception(sprintf("Banner type %s already loaded", $bannerKey), 1);
            }

            $this->bannersCollection->set($bannerKey, $this->get($bannerKey));
        }
    }

    public function get($type)
    {
        $banner = null;

        if (in_array($type, TypeModel::getBannerTypes())) {
            $banner = $this->getBannerByCode($type);
        }

        return $banner;
    }

    public function retrieve($type)
    {
        if ($this->bannersCollection->containsKey($type)) {
            return $this->bannersCollection->get($type);
        } else {
            throw new \Exception(sprintf("Banners with type %s have not been loaded.", $type), 1);
        }
    }

    public function getHomepageVertical()
    {
        return $this->retrieve(BannerType::CODE_HOME_VERTICAL);
    }

    public function getSearchPageBottom()
    {
        return $this->retrieve(BannerType::CODE_SEARCH_PAGE_BOTTOM);
    }

    public function getSearchPageTop()
    {
        return $this->retrieve(BannerType::CODE_SEARCH_PAGE_TOP);
    }

    public function getRightBlock()
    {
        return $this->retrieve(BannerType::CODE_PORTAL_RIGHT);
    }

    public function getStaticBlock()
    {
        return $this->retrieve(BannerType::CODE_STATIC_BOTTOM);
    }

    public function getItemsHeaders()
    {
        return array_map(
            function ($item) {
                if (null !== $item && null !== $item->getTemplate() and null !== $item->getType()) {
                    $bannerTypeCode = $item->getType()->getCode();

                    if (in_array($bannerTypeCode, TypeModel::getBannerResizable())) {
                        $header = $item->getTemplate()->getResizableHeader();
                    } elseif (in_array($bannerTypeCode, TypeModel::getBannerResizableInBlock())) {
                        $header = $item->getTemplate()->getResizableInBlockHeader();
                    } else {
                        $header = $item->getTemplate()->getTemplateHeader();
                    }

                    return $header;
                }

                return null;
            },
            $this->bannersCollection->toArray()
        );
    }

    protected function getBannerByCode($code)
    {
        $banners = $this->em->getRepository('DomainBannerBundle:Banner')
            ->getBannerByTypeCode($code);

        if (count($banners)) {
            return $banners[0];
        }
        return null;
    }
}
