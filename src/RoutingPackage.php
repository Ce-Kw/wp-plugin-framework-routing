<?php

namespace CEKW\WpPluginFramework\Routing;

use CEKW\WpPluginFramework\Core\Package\AbstractPackage;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RouteCollection;

class RoutingPackage extends AbstractPackage
{
    private ?RouteCollector $routeCollector;

    public function load(): void
    {
        $this->routeCollector = new RouteCollector(new RouteCollection());

        $this->loadConfig('routes/web.php');
        do_action('cekw.wp_plugin_framework.routes', $this->routeCollector);
        add_action('init', [$this, 'matchRequest']);
    }

    public function matchRequest(): void
    {
        $collection = $this->routeCollector->getRoutes();
        $request = Request::createFromGlobals();
        $context = new RequestContext();
        $context->fromRequest($request);

        try {
            $matcher = new UrlMatcher($collection, $context);
            $parameters = $matcher->match($request->getPathInfo());
            if (isset($parameters['_locale'])) {
                $request->setLocale($parameters['_locale']);
            }

            list($class, $method) = $parameters['_controller'];

            $args = [];
            foreach ($parameters as $key => $value) {
                if (strpos($key, '_') === 0) {
                    continue;
                }

                $args[':' . $key] = $value;
            }

            $classInstance = $this->injector->make($class);
            if ($classInstance instanceof ControllerInterface) {
                $classInstance->setSeoHelper(new SeoHelper($request, new UrlGenerator($collection, $context)));
            }

            $this->injector->execute([$classInstance, $method], $args);
        } catch (ResourceNotFoundException $e) {
            // If nothing matches let WordPress handle the routing.
        }
    }
}