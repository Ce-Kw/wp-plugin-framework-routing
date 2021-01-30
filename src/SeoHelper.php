<?php

namespace CEKW\WpPluginFramework\Routing;

use Yoast\WP\SEO\Presenters\Open_Graph\Locale_Presenter;
use PLL_WPSEO_OGP;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SeoHelper
{
    private array $langLocales;
    private ?Request $request;
    private ?UrlGeneratorInterface $urlGenerator;

    public function __construct(Request $request, UrlGeneratorInterface $urlGenerator)
    {
        $this->langLocales = function_exists('pll_languages_list') ? pll_languages_list(['fields' => 'locale']) : [];
        $this->request = $request;
        $this->urlGenerator = $urlGenerator;
    }

    public function addAlternateLinks(): void
    {
        add_action('wp_head', function () {
            foreach ($this->langLocales as $locale) {
                $href = $this->request->getPathInfo();
                $hreflang = substr($locale, 0, 2);
                printf('<link rel="alternate" href="%s" hreflang="%s" />' . "\n", esc_url($href), esc_attr($hreflang));
            }
        });
    }

    public function addOgAlternateLocales(): void
    {
        add_filter('wpseo_frontend_presenters', function ($presenters) {
            $return = [];
            foreach ($presenters as $presenter) {
                $return[] = $presenter;

                if (!$presenter instanceof Locale_Presenter) {
                    continue;
                }

                foreach ($this->langLocales as $locale) {
                    if ($locale === get_locale()) {
                        continue;
                    }

                    $return[] = new PLL_WPSEO_OGP($locale);
                }
            }

            return $return;
        });
    }

    public function generateUrl(string $route, array $parameters, int $referenceType): string
    {
        return $this->urlGenerator->generate($route, $parameters, $referenceType);
    }

    public function setCurrentUrl(): void
    {
        add_filter('pll_the_language_link', function () {
            return home_url($this->request->getPathInfo());
        });

        add_filter('wpseo_opengraph_url', function () {
            return home_url($this->request->getPathInfo());
        });
    }

    public function setTitle(string $title): void
    {
        add_filter('document_title_parts', function ($titleParts) use ($title) {
            $titleParts['title'] = $title;

            return $titleParts;
        });

        add_filter('wpseo_title', 'wp_get_document_title');
        add_filter('wpseo_opengraph_title', 'wp_get_document_title');
    }

    public function setResponseStatus(int $code = 200): void
    {
        add_filter('status_header', function ($statusHeader, $header, $text, $protocol) use ($code) {
            return "{$protocol} {$code} " . get_status_header_desc($code);
        }, 10, 4);

        if ($code !== 404) {
            add_action('template_redirect', function () {
                // phpcs:disable
                global $wp_query;
                $wp_query->is_404 = false;
                // phpcs:enable
            });
        }
    }
}