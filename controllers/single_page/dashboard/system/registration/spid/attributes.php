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
        $localAttributes = $this->app->make(LocalAttributeFactory::class)->getLocalAttributes();
        unset($localAttributes['d:spidCode']);
        $this->set('localAttributes', $localAttributes);
        $this->set('mappedAttributes', $this->app->make('spid/config')->get('mapped_attributes'));
    }

    public function save()
    {
        if (!$this->token->validate('spid-attributes-save')) {
            $this->error->add($this->token->getErrorMessage());
        } else {
            $newMapping = [];
            $post = $this->request->request;
            $spidAttributes = $this->getAllSpidAttributes();
            $localAttributes = $this->app->make(LocalAttributeFactory::class)->getLocalAttributes();
            unset($localAttributes['d:spidCode']);
            $alreadyMappedErrors = [];
            foreach ($this->getAllSpidAttributes() as $handle => $name) {
                $mappedTo = $post->get($handle);
                $mappedTo = is_string($mappedTo) ? trim($mappedTo) : '';
                switch ($mappedTo) {
                    case '-':
                    default:
                        if ($mappedTo === '' || !isset($localAttributes[$mappedTo])) {
                            $this->error->add(t('Please specify the mapping for the attribute named %s', $name));
                        } else {
                            $alreadyHandle = array_search($mappedTo, $newMapping);
                            if ($alreadyHandle !== false) {
                                $alreadyMappedError = t('The user attribute "%1$s" is already mapped to the SPID attribute "%2$s".', $localAttributes[$mappedTo]->getDisplayName(), $spidAttributes[$alreadyHandle]);
                                if (!in_array($alreadyMappedError, $alreadyMappedErrors, true)) {
                                    $alreadyMappedErrors[] = $alreadyMappedError;
                                    $this->error->add($alreadyMappedError);
                                }
                            } else {
                                $newMapping[$handle] = $mappedTo;
                            }
                        }
                        break;
                }
            }
            if ($this->app->make('spid/config')->get('registration.enabled') && array_search('f:uEmail', $newMapping) === false) {
                $this->error->add(t('You have to map the email field to a SPID field to in order to allow automatic users registration.'));
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

    /**
     * @return array
     */
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
