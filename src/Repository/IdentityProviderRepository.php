<?php

namespace SPID\Repository;

use Doctrine\ORM\EntityRepository;

class IdentityProviderRepository extends EntityRepository
{
    /**
     * Get the configured identity providers.
     *
     * @param bool $includeDisabled Include the disabled identity providers too?
     *
     * @return \SPID\Entity\IdentityProvider[]
     */
    public function getIdentityProviders($includeDisabled = false)
    {
        $criteria = [];
        if (!$includeDisabled) {
            $criteria['ipEnabled'] = true;
        }

        return $this->findBy($criteria, ['ipSort' => 'ASC']);
    }

    /**
     * Get the next available sort position for the identity providers.
     *
     * @return int
     */
    public function getNextIdentityProviderSort()
    {
        $idp = $this->findOneBy([], ['ipSort' => 'DESC']);

        return $idp === null ? 0 : $idp->getIdentityProviderSort() + 1;
    }

    /**
     * Get all the attribute handles for the configured identity providers.
     *
     * @param mixed $includeStandardOnes
     * @param mixed $includeDisabledEntityProviders
     *
     * @return string[]
     */
    public function getAllAttributeHandles($includeDisabledEntityProviders = false)
    {
        $all = [];
        foreach ($this->getIdentityProviders($includeDisabledEntityProviders) as $identityProvider) {
            $all = array_merge($all, $identityProvider->getIdentityProviderSupportedAttributes());
        }
        $all = array_unique($all);

        return array_values($all);
    }
}
