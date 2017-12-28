<?php

namespace SPID\Attributes\LocalAttributes;

/**
 * User attribute for fixed fields.
 */
class LocalAttributeFixed implements LocalAttributeInterface
{
    /**
     * The database field name.
     *
     * @var string
     */
    protected $fieldName;
    /**
     * The name of the attribute.
     *
     * @var string
     */
    protected $attributeName;

    /**
     * Initialize the instance.
     *
     * @param string $fieldName The database field name
     * @param string $attributeName The name of the attribute
     */
    public function __construct($fieldName, $attributeName)
    {
        $this->fieldName = $fieldName;
        $this->attributeName = $attributeName;
    }

    /**
     * {@inheritdoc}
     *
     * @see \SPID\Attributes\LocalAttributes\LocalAttributeInterface::getHandle()
     */
    public function getHandle()
    {
        return 'f:' . $this->fieldName;
    }

    /**
     * {@inheritdoc}
     *
     * @see \SPID\Attributes\LocalAttributes\LocalAttributeInterface::getDisplayName()
     */
    public function getDisplayName()
    {
        return t($this->attributeName);
    }
}
