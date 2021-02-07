<?php

namespace CEKW\WpPluginFramework\Routing;

use AltoRouter;
use CEKW\WpPluginFramework\Core\Package\AbstractPackage;

class RoutingPackage extends AbstractPackage
{
    private AltoRouter $router;

    public function load(): void
    {
        $this->router = new AltoRouter();
        $routeCollector = new RouteCollector($this->router);

        $this->loadConfig('routes/web.php');
        do_action('cekw.wp_plugin_framework.routes', $routeCollector);
        add_action('init', [$this, 'matchRequest']);
    }

    public function matchRequest(): void
    {
        $match = $this->router->match();
        if (empty($match) || !is_array($match['target'])) {
            return;
        }

        list($class, $method) = $match['target'];

        $args = [];
        foreach ($match['params'] as $key => $value) {
            if (strpos($key, '_') === 0) {
                continue;
            }

            $args[':' . $key] = $value;
        }

        $classInstance = $this->injector->make($class);
        if ($classInstance instanceof ControllerInterface) {
            $classInstance->setSeoHelper(new SeoHelper($this->router, $match['name']));
        }

        $this->injector->execute([$classInstance, $method], $args);
    }
}