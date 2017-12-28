<?php

namespace Concrete\Package\Spid\Controller\SinglePage\Dashboard\System\Registration\Spid;

use Concrete\Core\Http\ResponseFactoryInterface;
use Concrete\Core\Page\Controller\DashboardPageController;
use SPID\Attributes\LocalAttributes\LocalAttributeFactory;
use SPID\Attributes\SpidAttributes;
use SPID\Repository\IdentityProviderRepository;

defined('C5_EXECUTE') or die('Access Denied.');

class Attributes extends DashboardPageController
{
    public function view()
    {
        $this->set('spidAttributes', $this->getAllSpidAttributes());
        $this->set('localAttributes', $this->app->make(LocalAttributeFactory::class)->getLocalAttributes());
        $this->set('mappedAttributes', $this->app->make('spid/config')->get('mapped_attributes'));
    }

    public function save()
    {
        if (!$this->token->validate('spid-attributes-save')) {
            $this->error->add($this->token->getErrorMessage());
        } else {
            $newMapping = [];
            $post = $this->request->request;
            $spidAttributes = $this->app->make(SpidAttributes::class)->getAttributes();
            $localAttributes = $this->app->make(LocalAttributeFactory::class)->getLocalAttributes();
            foreach ($this->getAllSpidAttributes() as $handle => $name) {
                $mappedTo = $post->get($handle);
                $mappedTo = is_string($mappedTo) ? trim($mappedTo) : '';
                if ($mappedTo !== '-') {
                    if ($mappedTo === '' || !isset($localAttributes[$mappedTo])) {
                        $this->error->add('Please specify the mapping for the attribute named %s', $name);
                    } else {
                        $newMapping[$handle] = $mappedTo;
                    }
                }
            }
        }
        if ($this->error->has()) {
            $this->view();
        } else {
            $this->app->make('spid/config')->save('mapped_attributes', $newMapping);
            $this->flash('success', t('The attribute mapping has been saved.'));

            return $this->app->make(ResponseFactoryInterface::class)->redirect($this->action(''), 302);
        }
    }

    private function getAllSpidAttributes()
    {
        $spidAttributes = $this->app->make(SpidAttributes::class)->getAttributes();
        $repo = $this->app->make(IdentityProviderRepository::class);
        foreach ($repo->getAllAttributeHandles(true) as $handle) {
            if (!isset($spidAttributes[$handle])) {
                $spidAttributes[$handle] = $handle;
            }
        }

        return $spidAttributes;
    }
}
