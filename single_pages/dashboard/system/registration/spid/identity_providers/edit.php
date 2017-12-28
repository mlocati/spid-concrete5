<?php

/* @var Concrete\Package\Spid\Controller\SinglePage\Dashboard\System\Registration\Spid\IdentityProviders $controller */
/* @var Concrete\Core\Application\Service\Dashboard $dashboard */
/* @var Concrete\Core\Error\ErrorList\ErrorList $error */
/* @var Concrete\Core\Form\Service\Form $form */
/* @var Concrete\Core\Html\Service\Html $html */
/* @var Concrete\Core\Application\Service\UserInterface $interface */
/* @var Concrete\Core\Validation\CSRF\Token $token */
/* @var Concrete\Core\Page\View\PageView $view */

/* @var Concrete\Core\Localization\Service\Date $dateHelper */
/* @var SPID\Attributes\SpidAttributes $spidAttributes */

/* @var SPID\Entity\IdentityProvider $identityProvider */
?>

<form method="POST" action="<?= $view->action('save', $identityProvider->getIdentityProviderRecordId()) ?>">
    <?php $token->output('spid-idp-edit-' . $identityProvider->getIdentityProviderRecordId()) ?>
    <fieldset>
        <legend><?= t('Basics') ?></legend>
        <div class="form-group">
            <?= $form->label('name', t('Identity provider name')) ?>
            <?= $form->text('name', $identityProvider->getIdentityProviderName(), ['required' => 'required', 'maxlength' => '255']) ?>
        </div>
        <?php
        if ($identityProvider->getIdentityProviderMetadataUrl() !== '') {
            ?>
            <div class="form-group">
                <?= $form->label('metadataUrl', t('Metadata URL')) ?>
                <?= $form->url('metadataUrl', $identityProvider->getIdentityProviderMetadataUrl(), ['required' => 'required']) ?>
            </div>
            <?php
        } else {
            ?>
            <div class="form-group">
                <?= $form->label('metadataXml', t('Metadata XML')) ?>
                <?= $form->textarea('metadataXml', '', ['style' => 'resize: vertical;']) ?>
            </div>
            <?php
        }
        ?>
    </fieldset>

    <fieldset>
        <legend><?= t('Details') ?></legend>
        <div class="form-group">
            <?= $form->label('', t('Certificate expiration')) ?><br />
            <?php
            if ($identityProvider->getIdentityProviderX509CertificateExpiration() !== null) {
                echo $dateHelper->formatPrettyDateTime($identityProvider->getIdentityProviderX509CertificateExpiration(), true);
            }
            ?>
        </div>
        <div class="form-group">
            <?= $form->label('', t('Entity ID')) ?><br />
            <code><?= h($identityProvider->getIdentityProviderEntityId()) ?></code>
        </div>
        <div class="form-group">
            <?= $form->label('Id', t('Login URLs')) ?>
            <ul class="list-unstyled">
                <?php
                foreach ($identityProvider->getIdentityProviderLoginUrls() as $kind => $url) {
                    ?>
                    <li>
                        <code><?= h($url) ?></code>
                        <span class="label label-default"><?= h($kind) ?></span>
                    </li>
                    <?php
                }
                ?>
            </ul>
        </div>
        <div class="form-group">
            <?= $form->label('Id', t('Logout URLs')) ?>
            <ul class="list-unstyled">
                <?php
                foreach ($identityProvider->getIdentityProviderLogoutUrls() as $kind => $url) {
                    ?>
                    <li>
                        <code><?= h($url) ?></code>
                        <span class="label label-default"><?= h($kind) ?></span>
                    </li>
                    <?php
                }
                ?>
            </ul>
        </div>
        <div class="form-group">
            <?= $form->label('', t('Options')) ?>
            <ul class="list-unstyled">
                <li>
                    <?= t('Authorization requests must be signed?') ?>
                    <?= $identityProvider->requireSignedAuthorizationRequests() ? '<span class="label label-success"><i class="fa fa-check" aria-hidden="true"></i></span>' : '<span class="label label-danger"><i class="fa fa-times" aria-hidden="true"></i></span>' ?>
                </li>
            </ul>
        </div>
        <div class="form-group">
            <?= $form->label('', t('Supported attributes')) ?>
            <ul class="list-unstyled">
                <?php
                foreach ($identityProvider->getIdentityProviderSupportedAttributes() as $attributeHandle) {
                    ?>
                    <li>
                        <?= h($spidAttributes->getAttributeName($attributeHandle))?> (<code><?= h($attributeHandle) ?></code>)
                    </li>
                    <?php
                }
                ?>
            </ul>
        </div>
    </fieldset>

    <div class="ccm-dashboard-form-actions-wrapper">
        <div class="ccm-dashboard-form-actions">
            <a href="<?= URL::to('/dashboard/system/registration/spid/identity_providers') ?>" class="btn btn-default"><?= t('Cancel') ?></a>
            <div class="pull-right">
                <button type="button" class="btn btn-danger" id="spid-delete-identityprovider-button"><?= t('Delete') ?></button>
                <button class="btn btn-primary" type="submit"><?= t('Save') ?></button>
            </div>
        </div>
    </div>
</form>

<div class="ccm-ui" id="spid-delete-identityprovider-dialog" style="display: none" title="<?= t('Delete') ?>">
    <form method="POST" action="<?= $this->action('delete', $identityProvider->getIdentityProviderRecordId()) ?>">
        <?php $token->output('spid-idp-delete-' . $identityProvider->getIdentityProviderRecordId()) ?>
        <p><?= t('Are you sure you want to delete this identity provider? This cannot be undone.') ?></p>
        <div class="dialog-buttons">
            <button class="btn btn-default pull-left" onclick="jQuery.fn.dialog.closeTop()"><?= t('Cancel') ?></button>
            <button class="btn btn-danger pull-right" onclick="$('#spid-delete-identityprovider-dialog form').submit()"><?= t('Delete identity provider') ?></button>
        </div>
    </form>
</div>
<script>
$(document).ready(function() {
    $('#spid-delete-identityprovider-button').on('click', function (e) {
        e.preventDefault();
        jQuery.fn.dialog.open({
            element: $('#spid-delete-identityprovider-dialog'),
            modal: true,
            width: 320,
            height: 'auto'
        });
    });
});
</script>
