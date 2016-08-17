<?php

namespace Domain\ArticleBundle\Model\Manager;

use Doctrine\ORM\EntityManager;
use Oxa\ManagerArchitectureBundle\Model\Manager\Manager;

/**
 * Class ArticleManager
 * Article management entry point
 *
 * @package Domain\ArticleBundle\Manager
 */
class ArticleManager extends Manager
{
    const HOMEPAGE_ARTICLES_LIMIT = 2;

    public function fetchHomepageArticles()
    {
        $homepageArticles = $this->getRepository()->getArticlesForHomepage(self::HOMEPAGE_ARTICLES_LIMIT);

        return $homepageArticles;
    }

    public function getPublishedArticles()
    {
        return $this->getRepository()->getPublishedArticles();
    }

    public function getArticleBySlug($slug)
    {
        return $this->getRepository()->findOneBy(['slug' => $slug]);
    }

    public function getPublishedArticlesByCategory($category)
    {
        return $this->getRepository()->getPublishedArticlesByCategory($category);
    }
}
