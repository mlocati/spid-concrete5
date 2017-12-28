<?php

namespace SPID;

class AuthenticationLevel
{
    /**
     * SPID Authentication level: first level.
     *
     * @var string
     */
    const LEVEL_1 = 'https://www.spid.gov.it/SpidL1';

    /**
     * SPID Authentication level: second level.
     *
     * @var string
     */
    const LEVEL_2 = 'https://www.spid.gov.it/SpidL2';

    /**
     * SPID Authentication level: third level.
     *
     * @var string
     */
    const LEVEL_3 = 'https://www.spid.gov.it/SpidL3';

    /**
     * Default SPID authentication level.
     *
     * @var string
     */
    const DEFAULT_LEVEL = self::LEVEL_1;

    /**
     * Get all the SPID authentication levels.
     *
     * @return array array keys are the level IDs, array values are the level names and descriptions
     */
    public function getLevels()
    {
        return [
            static::LEVEL_1 => [t('Level 1'), t('Users will be able to login using their username and password.')],
            static::LEVEL_2 => [t('Level 2'), t('Users will be able to login using their username, password and a one-time password.')],
            static::LEVEL_3 => [t('Level 3'), t('Users will be able to login using a card reader.')],
        ];
    }
}
