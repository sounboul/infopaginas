<?php

namespace Domain\ArticleBundle\Twig\Extension;

/**
 * Class CutBodyExtension
 * @package Domain\ArticleBundle\Twig\Extension
 */
class CutBodyExtension extends \Twig_Extension
{
    const PREVIEW_BODY_LENGTH = 250;
    /**
     * @return array
     */
    public function getFunctions()
    {
        return ['cut_body_extension' => new \Twig_Function_Method($this, 'cutBody')];
    }

    /**
     * @param string $body
     * @return string
     */
    public function cutBody(string $body)
    {
        $body = strip_tags($body);
        $body = strlen($body) > self::PREVIEW_BODY_LENGTH ? substr($body, 0, self::PREVIEW_BODY_LENGTH) . '...' : $body;

        return $body;
    }

    /**
     * @return string
     */
    public function getName() : string
    {
        return 'cut_body_extension';
    }
}