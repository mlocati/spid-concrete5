<?php

/* @var Concrete\Package\Spid\Controller\SinglePage\Dashboard\System\Registration\Spid\Attributes $controller */
/* @var Concrete\Core\Application\Service\Dashboard $dashboard */
/* @var Concrete\Core\Error\ErrorList\ErrorList $error */
/* @var Concrete\Core\Form\Service\Form $form */
/* @var Concrete\Core\Html\Service\Html $html */
/* @var Concrete\Core\Application\Service\UserInterface $interface */
/* @var Concrete\Core\Validation\CSRF\Token $token */
/* @var Concrete\Core\Page\View\PageView $view */

/* @var array $spidAttributes */
/* @var SPID\Attributes\LocalAttributes\LocalAttributeInterface[] $localAttributes */
/* @var array $mappedAttributes */
$localAttributeOptions = [
    '-' => tc('Attribute', '** none'),
];
foreach ($localAttributes as $localAttribute) {
    $localAttributeOptions[$localAttribute->getHandle()] = $localAttribute->getDisplayName();
}
?>
<form method="POST" action="<?= h($view->action('save')) ?>">
    <?php $token->output('spid-attributes-save') ?>

    <table class="table">
        <thead>
            <tr>
                <th><?= t('SPID Attribute') ?></th>
                <th><?= t('User Attribute') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach ($spidAttributes as $handle => $name) {
                ?>
                <tr>
                    <td><span title="<?= h($handle) ?>"><?= h($name) ?></span></td>
                    <td>
                    <?= $form->select($handle, $localAttributeOptions, isset($mappedAttributes[$handle]) ? $mappedAttributes[$handle] : '-', ['required' => 'required']) ?>
                    </td>
                </tr>
                <?php
            }
            ?>
        </tbody>
    </table>

    <div class="ccm-dashboard-form-actions-wrapper">
        <div class="ccm-dashboard-form-actions">
            <div class="pull-right">
                <button class="btn btn-primary" type="submit"><?= t('Save') ?></button>
            </div>
        </div>
    </div>

</form>
