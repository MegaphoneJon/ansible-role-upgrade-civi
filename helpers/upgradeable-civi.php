#!/usr/bin/php
<?php
eval(`cv --level=full php:boot`);

/**
 * We want to inject our own URL so we can see extensions that aren't approved for automated distribution.
 */
class myCRM_Extension_System extends CRM_Extension_System {

  private $browser;

  public function getBrowser() {
    if ($this->browser === NULL) {
      $url = CRM_Utils_System::evalUrl('https://civicrm.org/extdir/ver={ver}|cms={uf}|ready=');
      $this->browser = new CRM_Extension_Browser($url, '');
    }
    return $this->browser;
  }

}

$ces = new myCRM_Extension_System();
$ces->getBrowser();
CRM_Extension_System::setSingleton($ces);
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
