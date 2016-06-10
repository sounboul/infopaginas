<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 6/8/16
 * Time: 12:07 PM
 */

namespace Domain\BusinessBundle\Form\Type;


use Oxa\ConfigBundle\Model\ConfigInterface;
use Oxa\ConfigBundle\Service\Config;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class GoogleMapType
 * @package Domain\BusinessBundle\Form\Type
 */
class GoogleMapType extends AbstractType
{
    /**
     * @var Config $config
     */
    protected $config;

    /**
     * @param Config $config
     */
    public function setConfig(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @param FormView $view
     * @param FormInterface $form
     * @param array $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);

        $view->vars['required'] = $options['required'];
        $view->vars['language'] = $options['language'];
        $view->vars['latitude'] = $options['latitude'];
        $view->vars['longitude'] = $options['longitude'];
        $view->vars['zoom'] = $options['zoom'];
        $view->vars['google_api_key'] = $options['google_api_key'];
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'required' => false,
            'language' => 'en',
            'latitude' => $this->config->getValue(ConfigInterface::DEFAULT_MAP_COORDINATE_LATITUDE),
            'longitude' => $this->config->getValue(ConfigInterface::DEFAULT_MAP_COORDINATE_LONGITUDE),
            'google_api_key' => $this->config->getValue(ConfigInterface::GOOGLE_API_KEY),
            'zoom' => 12,
        ]);
    }

    /**
     * @return mixed
     */
    public function getParent()
    {
        return TextType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'google_map';
    }
}
