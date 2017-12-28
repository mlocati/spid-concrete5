<?php

namespace SPID\Attributes\LocalAttributes;

/**
 * Interface that all the local user attributes must implement.
 */
interface LocalAttributeInterface
{
    /**
     * Get the local attribute unique handle.
     *
     * @return string
     */
    public function getHandle();

    /**
     * Get the local attribute display name.
     *
     * @return string
     */
    public function getDisplayName();
}
