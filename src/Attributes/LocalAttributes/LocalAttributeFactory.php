<?php

namespace SPID\Attributes\LocalAttributes;

use Concrete\Core\Attribute\Category\UserCategory;

class LocalAttributeFactory
{
    /**
     * The User attribute category.
     *
     * @var UserCategory
     */
    protected $attributeCategory;

    /**
     * The list of local attributes.
     *
     * @var LocalAttributeInterface[]|null
     */
    protected $localAttributes;

    /**
     * Initialize the instance.
     *
     * @param UserCategory $attributeCategory the User attribute category
     */
    public function __construct(UserCategory $attributeCategory)
    {
        $this->attributeCategory = $attributeCategory;
    }

    /**
     * Get all the local attributes.
     *
     * @return LocalAttributeInterface[]
     */
    public function getLocalAttributes()
    {
        if ($this->localAttributes === null) {
            $list = [
                new LocalAttributeFixed('uName', 'Username'),
                new LocalAttributeFixed('uEmail', 'Email'),
            ];
            foreach ($this->attributeCategory->getList() as $ak) {
                $list[] = new LocalAttributeDynamic($ak);
            }
            $dictionary = [];
            foreach ($list as $a) {
                $dictionary[$a->getHandle()] = $a;
            }
            $this->localAttributes = $dictionary;
        }

        return $this->localAttributes;
    }

    /**
     * Get a local attribute given its handle.
     *
     * @param string $handle
     *
     * @return LocalAttributeInterface|null
     */
    public function getLocalAttribyteByHandle($handle)
    {
        $la = $this->getLocalAttributes();

        return isset($la[$handle]) ? $la[$handle] : null;
    }
}
