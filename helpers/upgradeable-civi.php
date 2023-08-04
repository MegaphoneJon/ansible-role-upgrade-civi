#!/usr/bin/php
<?php
eval(`cv --level=cms-full php:boot`);

$e = new CRM_Admin_Page_Extensions();
$localExtensionList = $e->formatLocalExtensionRows();
$remoteExtensionList = $e->formatRemoteExtensionRows($localExtensionList);
$upgradeableExtensions = [];
$i = 0;
foreach ($remoteExtensionList as $e) {
  if ($e['upgradelink'] ?? FALSE) {
    $upgradeableExtensions[$i]['key'] = $e['file'];
    $upgradeableExtensions[$i]['name'] = $e['name'];
    $upgradeableExtensions[$i]['version'] = $e['version'];
    $i++;
  }
}
print_r(json_encode($upgradeableExtensions));
