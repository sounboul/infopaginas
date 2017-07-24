<?php

namespace Domain\SiteBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class RedirectController extends Controller
{
    const REDIRECT_PREFIX_BUSINESS = 'page';
    const REDIRECT_PREFIX_CATALOG  = 'business';
    const LOCALE_EN = 'en';
    const LOCALE_ES = 'es';

    /**
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function indexAction(Request $request)
    {
        $uri = strtolower($request->attributes->get('path'));

        if (strpos($uri, 'google') === 0 and file_exists($uri . '.html')) {
            // for google domain verification
            include($uri . '.html');
            die();
        }

        $pathParts = explode('/', $uri);

        $locale = self::LOCALE_EN;
        $currentLocale = $request->getLocale();

        if (strpos($uri, '/' . self::LOCALE_ES . '/') !== false) {
            $locale = self::LOCALE_ES;
        }

        switch ($pathParts[0]) {
            case self::REDIRECT_PREFIX_BUSINESS:
                // business page redirect
                $data = $this->getBusinessRedirectData($pathParts);
                break;
            case self::REDIRECT_PREFIX_CATALOG:
                // catalog redirect
                $data = $this->getCatalogRedirectData($pathParts);
                break;
            default:
                // redirect to search page
                $data = $this->getDefaultRedirectData($pathParts);
                break;
        }

        $redirectUrl = $this->getRedirectUrl($data, $locale, $currentLocale);

        return $this->redirect($redirectUrl, 301);
    }

    /**
     * @param array $pathParts
     *
     * @return array
     */
    protected function getBusinessRedirectData($pathParts)
    {
        end($pathParts);
        $business   = prev($pathParts);
        $locality   = prev($pathParts);

        return [
            'params' => [
                'citySlug' => $locality,
                'slug'     => $business,
            ],
            'route' => 'domain_business_profile_view',
        ];
    }

    /**
     * @param array $pathParts
     *
     * @return array
     */
    protected function getCatalogRedirectData($pathParts)
    {
        end($pathParts);
        $category   = prev($pathParts);
        $locality   = prev($pathParts);

        return [
            'params' => [
                'localitySlug' => $locality,
                'categorySlug' => $category,
            ],
            'route' => 'domain_search_catalog',
        ];
    }

    /**
     * @param array $pathParts
     *
     * @return array
     */
    protected function getDefaultRedirectData($pathParts)
    {
        return [
            'params' => [
                'q' => implode(' ', $pathParts),
            ],
            'route' => 'domain_search_index',
        ];
    }

    /**
     * @param array     $data
     * @param string    $locale
     * @param string    $currentLocale
     *
     * @return string
     */
    protected function getRedirectUrl($data, $locale, $currentLocale)
    {
        $router = $this->get('router');

        if ($locale and $locale != $currentLocale) {
            $defaultHost = $this->getParameter('router.request_context.host');
            $context = $router->getContext();

            if ($locale == self::LOCALE_EN) {
                $context->setHost($locale . '.' . $defaultHost);
            } else {
                $context->setHost($defaultHost);
            }
        }

        $redirectUrl = $router->generate(
            $data['route'],
            $data['params'],
            true
        );

        return $redirectUrl;
    }
}