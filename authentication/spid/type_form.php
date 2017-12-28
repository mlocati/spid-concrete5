<?php
defined('C5_EXECUTE') or die('Access denied.');

/* @var Concrete\Core\Url\Resolver\Manager\ResolverManager $urlResolver */
/* @var Concrete\Core\User\Group\Group[] $groups */
/* @var bool $registrationEnabled */
/* @var int|null $registrationGroupId */
?>

<div class="alert alert-info">
    <h4><?php echo t('SPID Configuration'); ?></h4>
    <ol>
        <li><a href="<?= $urlResolver->resolve(['/dashboard/system/registration/spid/configuration']) ?>" target="_blank"><?= t('Configure this service provider') ?></a></li>
        <li><a href="<?= $urlResolver->resolve(['/dashboard/system/registration/spid/attributes']) ?>" target="_blank"><?= t('Configure the attribute mapping') ?></a></li>
        <li><a href="<?= $urlResolver->resolve(['/dashboard/system/registration/spid/metadata']) ?>" target="_blank"><?= t('Enable this service provider by communicating its metadata XML') ?></a></li>
        <li><a href="<?= $urlResolver->resolve(['/dashboard/system/registration/spid/identity_providers']) ?>" target="_blank"><?= t('Configure the Identity Providers you want to enable') ?></a></li>
        <li><a href="<?= $urlResolver->resolve(['/dashboard/system/registration/authentication']) ?>" target="_blank"><?= t('Enable the SPID authentication type') ?></a></li>
    </ol>
</div>

<div class="form-group">
    <div class="input-group">
        <label>
            <input type="checkbox" name="registrationEnabled" value="1"<?= $registrationEnabled ? ' checked="checked"' : '' ?> />
            <span style="font-weight:normal"><?= t('Allow automatic registration') ?></span>
        </label>
    </div>
</div>
<div class="form-group" id="spid-registration-group">
    <label for="registrationGroupId" class="control-label"><?= t('Group to enter on registration') ?></label>
    <select name="registrationGroupId" class="form-control">
        <option value=""><?= t('None') ?></option>
        <?php
        foreach ($groups as $group) {
            ?>
            <option value="<?= $group->getGroupID() ?>"<?= $registrationGroupId && $registrationGroupId === (int) $group->getGroupID() ? ' selected="selected"' : '' ?>>
                <?= $group->getGroupDisplayName(false) ?>
            </option>
            <?php
        }
        ?>
    </select>
</div>

<script>
$(document).ready(function() {
    $('input[name="registrationEnabled"]')
        .on('change', function() {
            $('#spid-registration-group')[this.checked ? 'show' : 'hide']();
        })
        .trigger('change')
    ;
});
</script>
