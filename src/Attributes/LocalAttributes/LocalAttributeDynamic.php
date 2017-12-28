<?php

namespace SPID\Attributes\LocalAttributes;

use Concrete\Core\Entity\Attribute\Key\UserKey;

/**
 * User attribute for dynamic fields.
 */
class LocalAttributeDynamic implements LocalAttributeInterface
{
    /**
     * The user attribute key.
     *
     * @var UserKey
     */
    protected $attributeKey;

    /**
     * Initialize the instance.
     *
     * @param UserKey $attributeKey the user attribute key
     */
    public function __construct(UserKey $attributeKey)
    {
        $this->attributeKey = $attributeKey;
    }

    /**
     * {@inheritdoc}
     *
     * @see \SPID\Attributes\LocalAttributes\LocalAttributeInterface::getHandle()
     */
    public function getHandle()
    {
        return 'd:' . $this->attributeKey->getAttributeKeyHandle();
    }

    /**
     * {@inheritdoc}
     *
     * @see \SPID\Attributes\LocalAttributes\LocalAttributeInterface::getDisplayName()
     */
    public function getDisplayName()
    {
        return $this->attributeKey->getAttributeKeyDisplayName('text');
    }

    /**
     * Get the user attribute key.
     *
     * @return \Concrete\Core\Entity\Attribute\Key\UserKey
     */
    public function getAttributeKey()
    {
        return $this->attributeKey;
    }
}
