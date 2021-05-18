<?php

namespace CEKW\WpPluginFramework\Routing;

use AltoRouter;
use Yoast\WP\SEO\Presenters\Open_Graph\Locale_Presenter;
use PLL_WPSEO_OGP;

class SeoHelper
{
    private array $langLocales;
    private AltoRouter $router;
    private string $routeName;
    private array $params;

    public function __construct(AltoRouter $router, array $match)
    {
        $this->router = $router;
        $this->routeName = $match['name'];
        $this->params = $match['params'];
        $this->langLocales = function_exists('pll_languages_list') ? pll_languages_list(['fields' => 'locale']) : [];
    }

    public function addAlternateLinks(): void
    {
        add_action('wp_head', function () {
            foreach ($this->langLocales as $locale) {
                $lang = substr($locale, 0, 2);
                $href = $this->generateUrl($this->routeName, ['_lang' => $lang]);
                printf('<link rel="alternate" href="%s" hreflang="%s" />' . "\n", esc_url($href), esc_attr($lang));
            }
        });
    }

    public function addOgAlternateLocales(): void
    {
        if (!class_exists(Locale_Presenter::class)) {
            return;
        }

        if (!class_exists(PLL_WPSEO_OGP::class)) {
            return;
        }

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

    public function generateUrl(string $routeName, array $params = []): string
    {
        $params = array_merge($this->params, $params);

        // If we are on a page with a localized route name (e.g. my_route.en) return default route name.
        // This way we can find all other localized routes within the same name.
        if (isset($params['_lang']) && strpos($routeName, '.') !== false) {
            $routeName = explode('.', $routeName)[0];
        }

        // If we are on a page with the default language remove the lang parameter since it is not part of the URL nor the route name.
        if (function_exists('pll_current_language') && isset($params['_lang'])) {
            $polylang = get_option('polylang');
            if ($polylang['hide_default'] === 1 && pll_default_language() === $params['_lang']) {
                unset($params['_lang']);
            }
        }

        // Check if localized route name (e.g. my_route.en) with current language exists and return it.
        // So that the router will generate the localized URL.
        if (!empty($params['_lang'])) {
            $localizedRouteName = "{$routeName}.{$params['_lang']}";
            $localizedRouteNameExists = false;
            foreach ($this->router->getRoutes() as $routes) {
                if (in_array($localizedRouteName, $routes)) {
                    $localizedRouteNameExists = true;
                }
            }

            if ($localizedRouteNameExists) {
                $routeName = $localizedRouteName;
            }
        }

        return home_url($this->router->generate($routeName, $params));
    }

    public function setCurrentUrl(): void
    {
        add_filter('pll_the_language_link', fn() => $this->generateUrl($this->routeName));
        add_filter('wpseo_canonical', fn() => $this->generateUrl($this->routeName));
        add_filter('wpseo_opengraph_url', fn() => $this->generateUrl($this->routeName));
    }

    public function setTitle(string $title): void
    {
        add_filter('document_title_parts', function ($titleParts) use ($title) {
            $titleParts['title'] = $title;

            return $titleParts;
        });

        add_filter('wpseo_opengraph_title', 'wp_get_document_title');
    }

    public function setResponseStatus(int $code = 200): void
    {
        add_filter('status_header', fn($statusHeader, $header, $text, $protocol) => "{$protocol} {$code} " . get_status_header_desc($code), 10, 4);

        if ($code !== 404) {
            add_action('template_redirect', fn() => $GLOBALS['wp_query']->is_404 = false); // phpcs:ignore
        }
    }
}