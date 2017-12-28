<?php

/* @var Concrete\Package\Spid\Controller\SinglePage\Dashboard\System\Registration\Spid\Metadata $controller */
/* @var Concrete\Core\Form\Service\Form $form */
/* @var Concrete\Core\Html\Service\Html $html */
/* @var Concrete\Core\Validation\CSRF\Token $token */
/* @var Concrete\Core\Page\View\PageView $view */

/* @var Exception $metadataError */

?>
<div class="ccm-system-errors alert alert-danger">
    <p><?= h(t('The configuration is not valid:')) ?></p>
    <p><?= nl2br(h($metadataError->getMessage()))?></p>
</div>
<?php
