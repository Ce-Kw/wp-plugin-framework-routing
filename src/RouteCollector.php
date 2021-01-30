<?php

namespace CEKW\WpPluginFramework\Routing;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class RouteCollector
{
    private ?RouteCollection $collection;
    private string $currentName = '';

    /**
     * @var Route[]
     */
    private array $routes = [];

    public function __construct(RouteCollection $collection)
    {
        $this->collection = $collection;
    }

    /**
     * @param array|string $path
     */
    public function add(string $name, $path): RouteCollector
    {
        $this->currentName = $name;
        $this->routes[$this->currentName] = new Route($path);

        return $this;
    }

    public function getRoutes(): RouteCollection
    {
        return $this->collection;
    }

    public function setController(array $controller): void
    {
        $this->collection->add(
            $this->currentName,
            $this->routes[$this->currentName]->addDefaults(['_controller' => $controller])
        );
    }
}