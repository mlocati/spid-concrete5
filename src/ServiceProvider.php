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
        $this->app
            ->when(Saml::class)
            ->needs('$config')
            ->give(function (Application $app) {
                return $app->make('spid/config');
            });
        $this->app
            ->when(Logger::class)
            ->needs('$config')
            ->give(function (Application $app) {
                return $app->make('spid/config');
            });
        $this->app
            ->when(User::class)
            ->needs('$config')
            ->give(function (Application $app) {
                return $app->make('spid/config');
            });
        $this->registerSingletons();
        $this->registerEntityRepositories();
        $this->registerAliases();
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

    /**
     * Register the service aliases.
     */
    private function registerAliases()
    {
        $this->app->singleton('spid/config', function (Application $app) {
            $pkg = $app->make(PackageService::class)->getClass('spid');

            return $pkg->getFileConfig();
        });
    }
}
