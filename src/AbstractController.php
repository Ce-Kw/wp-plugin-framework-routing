<?php

namespace CEKW\WpPluginFramework\Routing;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

abstract class AbstractController implements ControllerInterface
{
    private $seoHelper;

    public function setSeoHelper(SeoHelper $seoHelper): void
    {
        $this->seoHelper = $seoHelper;
    }

    protected function setTitle(string $title): void
    {
        $this->seoHelper->setTitle($title);
    }

    protected function generateUrl(string $routeName, array $params = []): string
    {
        return $this->seoHelper->generateUrl($routeName, $params);
    }

    protected function redirect(string $url, int $status = 302): void
    {
        wp_redirect($url, $status, static::class);
        exit();
    }

    protected function redirectToRoute(string $routeName, array $params = [], int $status = 302): void
    {
        $this->redirect($this->generateUrl($routeName, $params), $status);
    }

    protected function render(string $template, array $params = []): void
    {
        foreach ($params as $key => $value) {
            set_query_var($key, $value);
        }

        $this->seoHelper->setResponseStatus(basename($template) === '404.php' ? 404 : 200);
        $this->seoHelper->setCurrentUrl();
        $this->seoHelper->addAlternateLinks();
        $this->seoHelper->addOgAlternateLocales();

        add_filter('body_class', function ($classes) use ($template) {
            $classes[] = 'page-template';
            $classes[] = 'page-template-' . basename($template, '.php');
            $classes[] = 'page-template-' . sanitize_title(basename($template));
            $classes[] = 'page';

            return $classes;
        });

        add_filter('template_include', fn() => is_readable($template) ? $template : locate_template($template));
    }
}