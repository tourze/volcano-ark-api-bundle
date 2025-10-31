<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Service;

use Symfony\Bundle\FrameworkBundle\Routing\AttributeRouteControllerLoader;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Routing\RouteCollection;
use Tourze\RoutingAutoLoaderBundle\Service\RoutingAutoLoaderInterface;
use Tourze\VolcanoArkApiBundle\Controller\ApiKeyCrudController;
use Tourze\VolcanoArkApiBundle\Controller\ApiKeyUsageCrudController;
use Tourze\VolcanoArkApiBundle\Controller\AuditLogCrudController;

#[AutoconfigureTag(name: 'routing.loader')]
class AttributeControllerLoader extends Loader implements RoutingAutoLoaderInterface
{
    private AttributeRouteControllerLoader $controllerLoader;

    private RouteCollection $collection;

    public function __construct()
    {
        parent::__construct();
        $this->controllerLoader = new AttributeRouteControllerLoader();

        $this->collection = new RouteCollection();
        $this->collection->addCollection($this->controllerLoader->load(ApiKeyCrudController::class));
        $this->collection->addCollection($this->controllerLoader->load(ApiKeyUsageCrudController::class));
        $this->collection->addCollection($this->controllerLoader->load(AuditLogCrudController::class));
    }

    public function load(mixed $resource, ?string $type = null): RouteCollection
    {
        return $this->collection;
    }

    public function supports(mixed $resource, ?string $type = null): bool
    {
        return false;
    }

    public function autoload(): RouteCollection
    {
        return $this->collection;
    }
}
