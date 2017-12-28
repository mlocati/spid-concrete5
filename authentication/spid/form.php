<?php
/* @var Concrete\Core\Html\Service\Html $html */
/* @var string[] $locales */
/* @var array $params */
/* @var Concrete\Core\Authentication\AuthenticationType $this */
/* @var string $uNameLabel */
/* @var Concrete\Core\Validation\CSRF\Token $valt */

/* @var Concrete\Core\Url\Resolver\Manager\ResolverManagerInterface $urlResolver */
/* @var SPID\Entity\IdentityProvider[] $identityProviders */

if (empty($identityProviders)) {
    ?>
    <div class="alert alert-danger">
        <?= t('No identity provider has been defined.') ?>
    </div>
    <?php
} else {
    ?>
    <ul class="spid-login">
        <?php
        foreach ($identityProviders as $identityProvider) {
            ?>
            <li class="spid-login spid-login-identityprovider"><a href="<?= h($urlResolver->resolve(['/spid/', $identityProvider->getIdentityProviderRecordId()])) ?>">
                <span class="spid-sr-only"><?= h($identityProvider->getIdentityProviderDisplayName()) ?></span>
                <?= $identityProvider->getIdentityProviderIconHtml() ?>
            </a></li>
            <?php
        }
        ?>
        <li class="spid-login spid-login-support"><a target="_blank" href="https://www.spid.gov.it"><?= t('More info') ?></a></li>
        <li class="spid-login spid-login-support"><a target="_blank" href="https://www.spid.gov.it/richiedi-spid"><?= t('Don\'t you have SPID?') ?></a></li>
        <li class="spid-login spid-login-support"><a target="_blank" href="https://www.spid.gov.it/serve-aiuto"><?= t('Do you need help?') ?></a></li>
    </ul>
    <?php
}
