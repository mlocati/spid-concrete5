<?php

namespace Concrete\Package\Spid;

use Concrete\Core\Asset\AssetList;
use Concrete\Core\Authentication\AuthenticationType;
use Concrete\Core\Database\EntityManager\Provider\ProviderAggregateInterface;
use Concrete\Core\Database\EntityManager\Provider\StandardPackageProvider;
use Concrete\Core\Package\Package;
use Concrete\Core\Routing\Router;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use SPID\EventSubscriber;
use SPID\IdentityProviderFactory;
use SPID\Repository\IdentityProviderRepository;
use SPID\ServiceProvider;

class Controller extends Package implements ProviderAggregateInterface
{
    /**
     * The handle of the package.
     *
     * @var string
     */
    protected $pkgHandle = 'spid';

    /**
     * The version of the package.
     *
     * @var string
     */
    protected $pkgVersion = '0.1';

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::$appVersionRequired
     */
    protected $appVersionRequired = '8.3.2a1';

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::$pkgAutoloaderRegistries
     */
    protected $pkgAutoloaderRegistries = [
        'src' => 'SPID',
    ];

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::getPackageName()
     */
    public function getPackageName()
    {
        return t('SPID Authentication');
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::getPackageDescription()
     */
    public function getPackageDescription()
    {
        return t('This addon let users access the website with an Italian SPID account');
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::install()
     */
    public function install()
    {
        $this->registerAutoload();
        $this->registerServices();
        $entity = parent::install();
        $this->installXml();
        try {
            AuthenticationType::getByHandle('spid');
        } catch (Exception $x) {
            $authenticationType = AuthenticationType::add('spid', 'SPID', 0, $entity);
            $authenticationType->disable();
        }
        $this->installDefaultIdentityProviders();
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::upgrade()
     */
    public function upgrade()
    {
        parent::upgrade();
        $this->installXml();
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Database\EntityManager\Provider\ProviderAggregateInterface::getEntityManagerProvider()
     */
    public function getEntityManagerProvider()
    {
        return new StandardPackageProvider(
            $this->app,
            $this,
            [
                'src/Entity' => 'SPID\Entity',
            ]
        );
    }

    /**
     * Method called to initialize this package (when it's installed).
     */
    public function on_start()
    {
        $this->registerAutoload();
        $this->registerServices();
        $this->registerEvents();
        if ($this->app->isRunThroughCommandLineInterface()) {
        } else {
            $this->registerRoutes();
            $this->registerAssets();
        }
    }

    /**
     * Install items from CIF files.
     */
    private function installXml()
    {
        $this->installContentFile('install.xml');
    }

    /**
     * Register the package autoload.
     * This is required if the package has not beed installed with Composer.
     */
    private function registerAutoload()
    {
        $autoload = $this->getPackagePath() . '/vendor/autoload.php';
        if (is_file($autoload)) {
            require $autoload;
        }
    }

    /**
     * Register the package service classes.
     */
    private function registerServices()
    {
        $this->app->make(ServiceProvider::class)->register();
    }

    /**
     * Register the event hooks.
     */
    private function registerEvents()
    {
        $this->app->make('director')->addSubscriber($this->app->make(EventSubscriber::class));
    }

    /**
     * Install the default identity providers.
     */
    private function installDefaultIdentityProviders()
    {
        $repo = $this->app->make(IdentityProviderRepository::class);
        if ($repo->findOneBy([]) === null) {
            $em = $this->app->make(EntityManagerInterface::class);
            $factory = $this->app->make(IdentityProviderFactory::class);
            $done = 0;
            foreach ($factory->getDefaultIdentityProviderMetadataUrls() as $name => list($url, $iconHandle)) {
                try {
                    $ip = $factory->getIdentityProviderFromMetadataUrl($url);
                    $ip
                        ->setIdentityProviderName($name)
                        ->setIdentityProviderSort($done)
                        ->setIdentityProviderIcon($iconHandle)
                        ->setIdentityProviderMetadataUrl($url)
                    ;
                    $em->persist($ip);
                    $em->flush($ip);
                    ++$done;
                } catch (Exception $x) {
                }
            }
        }
    }

    /**
     * Register the routes.
     */
    private function registerRoutes()
    {
        $identityProviderRecordIdRX = '[1-9][0-9]*';
        $this->app->make(Router::class)->registerMultiple([
            // Login
            '/spid/{identityProviderRecordId}' => [
                /* $callback */
                'Concrete\Package\Spid\Controller\Frontend\Spid::startLogin',
                /* $handle */
                null,
                /* $requirements */
                ['identityProviderRecordId' => $identityProviderRecordIdRX],
                /* $options */
                [],
                /* $host */
                '',
                /* $schemes */
                [],
                /* $methods */
                ['GET'],
            ],
            '/spid/assertionConsumerService' => [
                /* $callback */
                'Concrete\Package\Spid\Controller\Frontend\Spid::assertion',
                /* $handle */
                null,
                /* $requirements */
                [],
                /* $options */
                [],
                /* $host */
                '',
                /* $schemes */
                ['https'],
                /* $methods */
                ['POST'],
            ],
        ]);
    }

    /**
     * Register the package JavaScript/CSS assets.
     */
    private function registerAssets()
    {
        $al = AssetList::getInstance();
        $al->registerMultiple([
            'spid/login' => [
                ['css', 'css/login.css', ['minify' => true, 'combine' => true], $this],
            ],
        ]);
        $al->registerGroupMultiple([
            'spid/login' => [
                [
                    ['css', 'spid/login'],
                ],
            ],
        ]);
    }
}
