<?php

namespace Concrete\Package\Spid\Controller\SinglePage\Dashboard\System\Registration\Spid;

use Concrete\Core\Http\ResponseFactoryInterface;
use Concrete\Core\Page\Controller\DashboardPageController;
use OneLogin_Saml2_Error;
use SPID\Saml;

defined('C5_EXECUTE') or die('Access Denied.');

class Metadata extends DashboardPageController
{
    public function view()
    {
        $saml = $this->app->make(Saml::class);

        try {
            $metadata = $saml->getMetadata();
            $rf = $this->app->make(ResponseFactoryInterface::class);

            return $rf->create($metadata, 200, ['Content-Type' => 'text/xml']);
        } catch (OneLogin_Saml2_Error $x) {
            $this->set('metadataError', $x);
        }
    }
}
