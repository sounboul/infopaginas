<?php

namespace Domain\BannerBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Oxa\Sonata\AdminBundle\Model\DefaultEntityInterface;
use Oxa\Sonata\AdminBundle\Util\Traits\DefaultEntityTrait;
use Gedmo\Mapping\Annotation as Gedmo;
use Sonata\TranslationBundle\Model\Gedmo\TranslatableInterface;
use Oxa\Sonata\AdminBundle\Util\Traits\OxaPersonalTranslatable as PersonalTranslatable;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Template
 *
 * @ORM\Table(name="banner_template")
 * @ORM\Entity(repositoryClass="Domain\BannerBundle\Repository\TemplateRepository")
 * @Gedmo\TranslationEntity(class="Domain\BannerBundle\Entity\Translation\TemplateTranslation")
 */
class Template implements DefaultEntityInterface, TranslatableInterface
{
    use DefaultEntityTrait;
    use PersonalTranslatable;

    const TAG_RESIZABLE_COMMON   = '.defineSizeMapping(googleResponsiveCommonSize)';
    const TAG_RESIZABLE_IN_BLOCK = '.defineSizeMapping(googleResponsiveBlockSize)';

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string - Script template name
     *
     * @Gedmo\Translatable(fallback=true)
     * @ORM\Column(name="name", type="string", length=100)
     * @Assert\NotBlank()
     */
    protected $name;

    /**
     * @var string - Script template header code
     *
     * @ORM\Column(name="header", type="text")
     * @Assert\NotBlank()
     */
    protected $templateHeader;

    /**
     * @var string - Script template body
     *
     * @ORM\Column(name="body", type="text")
     * @Assert\NotBlank()
     */
    protected $body;

    /**
     * @var Banner[]
     *
     * @ORM\OneToMany(
     *     targetEntity="Domain\BannerBundle\Entity\Banner",
     *     mappedBy="template",
     *     cascade={"persist", "remove"}
     *     )
     */
    protected $banners;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(
     *     targetEntity="Domain\BannerBundle\Entity\Translation\TemplateTranslation",
     *     mappedBy="object",
     *     cascade={"persist", "remove"}
     * )
     */
    protected $translations;

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->banners = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function __toString()
    {
        return $this->getName() ?: '';
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Template
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set Header
     *
     * @param string $templateHeader
     *
     * @return Template
     */
    public function setTemplateHeader($templateHeader)
    {
        $this->templateHeader = $templateHeader;

        return $this;
    }

    /**
     * Get Header
     *
     * @return string
     */
    public function getTemplateHeader()
    {
        return $this->templateHeader;
    }

    /**
     * Set body
     *
     * @param string $body
     *
     * @return Template
     */
    public function setBody($body)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Get body
     *
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Add banner
     *
     * @param \Domain\BannerBundle\Entity\Banner $banner
     *
     * @return Template
     */
    public function addBanner(\Domain\BannerBundle\Entity\Banner $banner)
    {
        $this->banners[] = $banner;

        return $this;
    }

    /**
     * Remove banner
     *
     * @param \Domain\BannerBundle\Entity\Banner $banner
     */
    public function removeBanner(\Domain\BannerBundle\Entity\Banner $banner)
    {
        $this->banners->removeElement($banner);
    }

    /**
     * Get banners
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getBanners()
    {
        return $this->banners;
    }

    /**
     * Add translation
     *
     * @param \Domain\BannerBundle\Entity\Translation\TemplateTranslation $translation
     *
     * @return Template
     */
    public function addTranslation(\Domain\BannerBundle\Entity\Translation\TemplateTranslation $translation)
    {
        $this->translations[] = $translation;

        return $this;
    }

    /**
     * Remove translation
     *
     * @param \Domain\BannerBundle\Entity\Translation\TemplateTranslation $translation
     */
    public function removeTranslation(\Domain\BannerBundle\Entity\Translation\TemplateTranslation $translation)
    {
        $this->translations->removeElement($translation);
    }

    /**
     * Get translations
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTranslations()
    {
        return $this->translations;
    }

    /**
     * @return string
     */
    public function getResizableHeader()
    {
        return $this->getHeaderWithSizeTag(self::TAG_RESIZABLE_COMMON);
    }

    /**
     * @return string
     */
    public function getResizableInBlockHeader()
    {
        return $this->getHeaderWithSizeTag(self::TAG_RESIZABLE_IN_BLOCK);
    }

    /**
     * @param string $tag
     *
     * @return string
     */
    protected function getHeaderWithSizeTag($tag)
    {
        $header = $this->getTemplateHeader();

        $position = strpos($header, '.addService');

        if ($position !== false) {
            $resizableHeader = substr_replace($header, $tag, $position, 0);
        } else {
            $resizableHeader = '';
        }

        return $resizableHeader;
    }
}
