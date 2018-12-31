<?php

namespace SPID;

use Concrete\Core\Application\Application;
use Concrete\Core\Foundation\Service\Provider;
use Concrete\Core\Package\PackageService;
use Doctrine\ORM\EntityManager;
use SPID\Attributes\LocalAttributes\LocalAttributeFactory;

class ServiceProvider extends Provider
{
    public function register()
    {
        $this->registerSingletons();
        $this->registerEntityRepositories();
    }

    /**
     * Register the singleton services.
     */
    private function registerSingletons()
    {
        foreach ([
            LocalAttributeFactory::class,
        ] as $fqn) {
            $this->app->singleton($fqn);
        }
    }

    /**
     * Register the Doctrine entity repositories.
     */
    private function registerEntityRepositories()
    {
        foreach ([
            'IdentityProvider',
        ] as $path) {
            $this->app->singleton("SPID\\Repository\\{$path}Repository", function (Application $app) use ($path) {
                return $app->make(EntityManager::class)->getRepository('SPID\\Entity\\' . $path);
            });
        }
    }
}
