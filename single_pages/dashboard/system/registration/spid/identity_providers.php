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
/* @var SPID\Entity\IdentityProvider[] $identityProviders */
/* @var array $missingDefaultIdentityProviders */

// Optional items
/* @var Concrete\Core\Error\ErrorList\ErrorList $addDialogErrors */
/* @var bool $showAddDialog */

?>
<div class="ccm-dashboard-header-buttons">
    <?php
    if (empty($missingDefaultIdentityProviders)) {
        ?><a href="#" class="spid-idp-add btn btn-primary"><?= t('New Identity Provider') ?></a><?php
    } else {
        ?>
        <div class="btn-group">
            <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <?= t('New Identity Provider') ?>
                <span class="caret"></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-right">
                <li><a href="#" class="spid-idp-add"><?= t('Custom Identity Provider') ?></a></li>
                <li class="divider"></li>
                <?php
                foreach ($missingDefaultIdentityProviders as $mdip) {
                    ?><li><a href="#" class="spid-idp-add" data-name="<?= h($mdip['name']) ?>" data-metadata-url="<?= h($mdip['metadata']) ?>"><?= h($mdip['name']) ?></a></li><?php
                }
                ?>
            </ul>
        </div>
        <?php
    }
    ?>
</div>

<?php
if (empty($identityProviders)) {
    ?>
    <div class="alert alert-info" role="alert">
        <?= t('No identity provider is currently defined.') ?><br />
        <?= t('Click the %s button to create a identity provider.', '<strong>' . t('New Identity Provider') . '</strong>') ?><br />
    </div>
    <?php
} else {
    ?>
    <div class="table-responsive">
        <table class="table" id="spid-idp-list">
            <thead>
                <tr>
                    <th class="spid-action-cell"></th>
                    <th class="spid-logo-cell"></th>
                    <th><?= t('Name') ?></th>
                    <th><?= t('Certificate Expiration') ?></th>
                    <th class="spid-sort-cell"></th>
                </tr>
            </thead>
            <tbody class="ui-sortable">
                <?php
                foreach ($identityProviders as $identityProvider) {
                    ?>
                    <tr data-idp-id="<?= $identityProvider->getIdentityProviderRecordId() ?>" class="<?= $identityProvider->isIdentityProviderEnabled() ? 'success' : 'danger' ?>">
                        <td class="spid-action-cell">
                            <a href="<?= $view->action('edit', $identityProvider->getIdentityProviderRecordId()) ?>" class="label label-primary"><?= t('edit') ?></a>
                            <?php
                            if ($identityProvider->isIdentityProviderEnabled()) {
                                ?><a href="<?= $view->action('enable_idp', $identityProvider->getIdentityProviderRecordId(), '0', $token->generate('spid-idp-enable-0-' . $identityProvider->getIdentityProviderRecordId())) ?>" class="label label-danger"><?= t('disable') ?></a><?php
                            } else {
                                ?><a href="<?= $view->action('enable_idp', $identityProvider->getIdentityProviderRecordId(), '1', $token->generate('spid-idp-enable-1-' . $identityProvider->getIdentityProviderRecordId())) ?>" class="label label-success"><?= t('enable') ?></a><?php
                            }
                            ?>
                        </td>
                        <td class="spid-logo-cell"><?= $identityProvider->getIdentityProviderIconHtml() ?></td>
                        <td><?= h($identityProvider->getIdentityProviderDisplayName()) ?></td>
                        <td><?php
                            $d = $identityProvider->getIdentityProviderX509CertificateExpiration();
                            if ($d !== null) {
                                echo h($dateHelper->formatPrettyDateTime($d, true, false));
                            }
                        ?></td>
                        <td class="spid-sort-cell"><i class="fa fa-arrows"></i></td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>
    </div>
    <script>
    $(document).ready(function() {
        var $list = $('#spid-idp-list tbody');
        $list.sortable({
            axis: 'y',
            cursor: 'move',
            handle: 'i.fa-arrows',
            helper: function(e, ui) {
                ui.children().each(function() {
                    var $me = $(this);
                    $me.width($me.width());
                });
                return ui;
            },
            revert: true,
            stop: function(e, ui) {
                $list.sortable('option', 'disabled', true);
                var order = [];
                $list.children().each(function() {
                    var $me = $(this);
                    order.push($me.attr('data-idp-id'));
                });
                $.ajax({
                    cache: false,
                    data: {
                        <?= json_encode($token::DEFAULT_TOKEN_NAME) ?>: <?= json_encode($token->generate('spid-idp-sort')) ?>,
                        order: order
                    },
                    dataType: 'json',
                    method: 'POST',
                    url: <?= json_encode($view->action('reorder_idp')) ?>
                }).fail(function (xhr, status, error) {
                    window.alert(error);
                    $list.sortable('cancel');
                }).always(function() {
                    $list.sortable('option', 'disabled', false);
                });
            }
        });
    });
    </script>
    <?php
}
?>
<div class="ccm-ui" id="spid-idp-add-dialog" style="display: none" title="<?= t('New Identity Provider') ?>">
    <?php
    if (isset($addDialogErrors) && $addDialogErrors->has()) {
        ?>
        <div class="alert alert-danger" id="spid-idp-add-dialog-errors">
            <?= $addDialogErrors ?>
        </div>
        <?php
    }
    ?>    
    <form method="POST" action="<?= $view->action('create_idp') ?>">
        <?php $token->output('spid-idp-create') ?>
        <div class="form-group">
            <?= $form->label('spid_idpcreate_name', t('Name')) ?>
            <?= $form->text('spid_idpcreate_name', '', ['required' => 'required', 'maxlength' => '255']) ?>
        </div>
        <div class="form-group">
            <?= $form->label('spid_idpcreate_metadata_kind', t('Metadata kind')) ?>
            <div class="radio">
                <label>
                    <?= $form->radio('spid_idpcreate_metadata_kind', 'url', 'url') ?>
                    <span><?= t('Enter metadata URL') ?></span>
                </label>
            </div>
            <div class="radio">
                <label>
                    <?= $form->radio('spid_idpcreate_metadata_kind', 'xml', 'url') ?>
                    <span><?= t('Enter metadata XML') ?></span>
                </label>
            </div>
        </div>
        <div class="form-group spid_idpcreate_metadata_kind">
            <?= $form->label('spid_idpcreate_metadata_url', t('Metadata URL')) ?>
            <?= $form->url('spid_idpcreate_metadata_url', '') ?>
        </div>
        <div class="form-group spid_idpcreate_metadata_kind" style="display: none">
            <?= $form->label('spid_idpcreate_metadata_xml', t('Metadata XML')) ?>
            <?= $form->textarea('spid_idpcreate_metadata_xml', '', ['style' => 'resize: vertical']) ?>
        </div>
        <div class="dialog-buttons">
            <button class="btn btn-default pull-left" onclick="jQuery.fn.dialog.closeTop()"><?= t('Cancel') ?></button>
            <button class="btn btn-primary pull-right" onclick="$('#spid-idp-add-dialog form').submit()"><?= t('Create') ?></button>
        </div>
    </form>
</div>
<script>
$(document).ready(function() {
    function updateAddDialogState() {
        var sel = $('input[name="spid_idpcreate_metadata_kind"]:checked').val();
        $('div.spid_idpcreate_metadata_kind').hide();
        $('#spid_idpcreate_metadata_' + sel).closest('div.spid_idpcreate_metadata_kind').show();
    }
    function showAddDialog() {
        updateAddDialogState();
        jQuery.fn.dialog.open({
            element: $('#spid-idp-add-dialog'),
            modal: true,
            width: Math.min(Math.max($(window).width() - 80, 250), 500),
            height: 'auto'
        });
    }
    $('input[name="spid_idpcreate_metadata_kind"]').on('change', function() {
        updateAddDialogState();
    });
    $('a.spid-idp-add').on('click', function (e) {
        e.preventDefault();
        var $a = $(this);
        $('#spid-idp-add-dialog-errors').remove();
        $('#spid_idpcreate_name').val($a.data('name') || '');
        $('input[name="spid_idpcreate_metadata_kind"][value="url"]').prop('checked', true);
        $('#spid_idpcreate_metadata_url').val($a.data('metadata-url') || '');
        showAddDialog();
    });
    <?php
    if (isset($showAddDialog) && $showAddDialog) {
        ?>
        showAddDialog();
        <?php
    }
    ?>
});
</script>
