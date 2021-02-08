<?php

namespace CEKW\WpPluginFramework\Routing;

use AltoRouter;

class RouteCollector
{

    private AltoRouter $router;
    private string $currentName;
    private array $routes = [];

    public function __construct(AltoRouter $router)
    {
        $this->router = $router;
    }

    /**
     * @param string|array $path
     */
    public function add($path, string $name = ''): RouteCollector
    {
        $this->currentName = !empty($name) ? $name :  md5(serialize($path));
        $this->routes[$this->currentName] = [
            'methods' => 'GET|POST|PATCH|PUT|DELETE',
            'path' => $path,
        ];

        return $this;
    }

    public function setMethods(array $methods): RouteCollector
    {
        $this->routes[$this->currentName]['methods'] = implode('|', $methods);

        return $this;
    }

    public function setController(array $controller): void
    {
        $currentRoute = $this->routes[$this->currentName];
        if (is_array($currentRoute['path'])) {
            foreach ($currentRoute['path'] as $lang => $langPath) {
                if (!empty($lang)) {
                    $this->router->map($currentRoute['methods'], '/' . $lang . $langPath, $controller, "{$this->currentName}.{$lang}");
                } else {
                    $this->router->map($currentRoute['methods'], $langPath, $controller, $this->currentName);
                }
            }
        } else {
            $this->router->map($currentRoute['methods'], $currentRoute['path'], $controller, $this->currentName);
        }
    }
}