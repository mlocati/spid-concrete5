<?php

/* @var Concrete\Package\Spid\Controller\SinglePage\Dashboard\System\Registration\Spid\Configuration $controller */
/* @var Concrete\Core\Application\Service\Dashboard $dashboard */
/* @var Concrete\Core\Error\ErrorList\ErrorList $error */
/* @var Concrete\Core\Form\Service\Form $form */
/* @var Concrete\Core\Html\Service\Html $html */
/* @var Concrete\Core\Application\Service\UserInterface $interface */
/* @var Concrete\Core\Validation\CSRF\Token $token */
/* @var Concrete\Core\Page\View\PageView $view */

/* @var string $entityId */
/* @var string $signingPrivateKey */
/* @var string $signingX509certificate */
/* @var array $authenticationLevels */
/* @var string $authenticationLevel */
/* @var bool $checkSignatures */
/* @var bool $wantMessagesSigned */
/* @var bool $logMessages */
/* @var string $signingPrivateKey */
/* @var string $signingX509certificate */

?>
<form method="POST" action="<?= h($view->action('save')) ?>">
    <?php $token->output('spid-configuration-save') ?>

    <div class="form-group">
        <?= $form->label('entityId', t('Entity ID')) ?>
        <?= $form->text('entityId', $entityId, ['required' => 'required']) ?>
    </div>

    <div class="form-group">
        <?= $form->label('signingPrivateKey', t('Private key')) ?>
        <?= $form->textarea('signingPrivateKey', $signingPrivateKey, ['required' => 'required', 'rows' => '7', 'style' => 'resize: vertical; font-family: monospace']) ?>
    </div>

    <div class="form-group">
        <?= $form->label('signingX509certificate', t('X.509 Certificate')) ?>
        <?= $form->textarea('signingX509certificate', $signingX509certificate, ['required' => 'required', 'rows' => '7', 'style' => 'resize: vertical; font-family: monospace']) ?>
    </div>

    <div class="form-group">
        <?= $form->label('authenticationLevel', t('Authentication Level') . ' <a href="https://help.infocert.it/risposte/cosa-sono-i-livelli-di-sicurezza-spid/" target="_blank"><i class="fa fa-info-circle"></i></a>') ?>
        <?php
        foreach ($authenticationLevels as $authenticationLevelOption => list($authenticationLevelName, $authenticationLevelDescription)) {
            ?>
            <div class="radio">
                <label>
                    <?= $form->radio('authenticationLevel', $authenticationLevelOption, $authenticationLevel, ['required' => 'required']) ?>
                    <?= h($authenticationLevelName) ?>
                    <div class="text-muted"><?= h($authenticationLevelDescription) ?></div>
                </label>
            </div>
            <?php
        }
        ?>
    </div>

    <div class="form-group">
        <?= $form->label('', t('Options')) ?>
        <div class="checkbox">
            <label>
                <?= $form->checkbox('checkSignatures', 'yes', $checkSignatures) ?>
                <?= t('validate message signatures') ?>
                <div class="text-muted"><?= t('This option should be checked in production environments.') ?></div>
            </label>
        </div>
        <div class="checkbox">
            <label>
                <?= $form->checkbox('wantMessagesSigned', 'yes', $wantMessagesSigned) ?>
                <?= t('messages from identity providers must be signed') ?>
                <div class="text-muted"><?= t('This option should be checked in production environments.') ?></div>
            </label>
        </div>
        <div class="checkbox">
            <label>
                <?= $form->checkbox('logMessages', 'yes', $logMessages) ?>
                <?= t('log sent/received messages') ?>
            </label>
        </div>
    </div>

    <div class="ccm-dashboard-form-actions-wrapper">
        <div class="ccm-dashboard-form-actions">
            <div class="pull-right">
                <button class="btn btn-primary" type="submit"><?= t('Save') ?></button>
            </div>
        </div>
    </div>

</form>
