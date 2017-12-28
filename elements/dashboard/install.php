<?php

use Concrete\Core\Support\Facade\Application;
use Concrete\Core\Url\Resolver\Manager\ResolverManagerInterface;

defined('C5_EXECUTE') or die('Access Denied.');

$app = Application::getFacadeApplication();

$greenIcon = '<i class="fa fa-check" style="color: green"></i>';
$redIcon = '<i class="fa fa-exclamation-circle" style="color: red"></i>';
$checks = [];
$someRed = false;

foreach ([
    'openssl',
    'curl',
    'dom',
    'zlib',
] as $extension) {
    $ok = extension_loaded($extension);
    if (!$ok) {
        $someRed = true;
    }
    $checks[] = [
        $ok ? $greenIcon : $redIcon,
        t('PHP extension %s installed', '<code>' . $extension . '</code>'),
    ];
}
$site = $app->make('site')->getSite();
if (is_object($site)) {
    $canonicalUrl = $site->getSiteCanonicalURL();
    $ok = $canonicalUrl && preg_match('/^https:\/\//i', $canonicalUrl);
    if (!$ok) {
        $someRed = true;
    }
    $checks[] = [
        $ok ? $greenIcon : $redIcon,
        $ok ? t('The canonical URL must set and use the HTTPS protocol') :
        sprintf(
            '<a href="%1$s" target="_blank">%2$s</a>',
            $app->make(ResolverManagerInterface::class)->resolve(['/dashboard/system/seo/urls']),
            t('The canonical URL must set and use the HTTPS protocol')
        ),
    ];
}

?>
<div class="ccm-dashboard-header-buttons">
    <a href="#" class="btn btn-primary" onclick="window.location.reload(); return false"><?= t('Repeat checks'); ?></a>
</div>
<table class="table table-striped">
    <col>
    <col width="100%">
    <tbody>
        <?php
        foreach ($checks as list($icon, $text)) {
            ?>
            <tr>
                <td><?= $icon ?></td>
                <td><?= $text ?></td>
            </tr>
            <?php
        }
        ?>
    </tbody>
</table>
<?php
if ($someRed) {
    ?>
    <input type="checkbox" required="required" style="display: none" />
    <script>
    $(document).ready(function() {
        $('.ccm-dashboard-form-actions input[type="submit"]').attr('disabled', 'disabled');
    });
    </script>
    <?php
}
