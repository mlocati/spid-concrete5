<?php
use Concrete\Core\Support\Facade\Application;
use Concrete\Core\Url\Resolver\Manager\ResolverManagerInterface;

defined('C5_EXECUTE') or die('Access Denied.');

$url = Application::getFacadeApplication()->make(ResolverManagerInterface::class);
?>
<p><?= t('SPID Authentication has been installed, but it is not yet active.'); ?></p>
<p><?= t('In order to activate it, you have to:') ?></p>
<ol>
    <li><a href="<?= $url->resolve(['/dashboard/system/registration/spid/configuration']) ?>" target="_blank"><?= t('Configure this service provider') ?></a></li>
    <li><a href="<?= $url->resolve(['/dashboard/system/registration/spid/attributes']) ?>" target="_blank"><?= t('Configure the attribute mapping') ?></a></li>
    <li><a href="<?= $url->resolve(['/dashboard/system/registration/spid/metadata']) ?>" target="_blank"><?= t('Enable this service provider by communicating its metadata XML') ?></a></li>
    <li><a href="<?= $url->resolve(['/dashboard/system/registration/spid/identity_providers']) ?>" target="_blank"><?= t('Configure the Identity Providers you want to enable') ?></a></li>
    <li><a href="<?= $url->resolve(['/dashboard/system/registration/authentication']) ?>" target="_blank"><?= t('Enable the SPID authentication type') ?></a></li>
</ol>
