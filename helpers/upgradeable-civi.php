#!/usr/bin/php
<?php
eval(`cv php:boot`);
// We force a reload of the classloader to get PSR-4 declarations in extensions D8+ doesn't do this in php:boot).
\CRM_Core_Config::singleton(TRUE, TRUE);

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

class myCRM_Admin_Page_Extensions extends CRM_Admin_Page_Extensions {
  /**
   * Get the list of local extensions and format them as a table with
   * status and action data.
   *
   * @return array
   */
  public function formatLocalExtensionRows() {
    $mapper = CRM_Extension_System::singleton()->getMapper();
    $manager = CRM_Extension_System::singleton()->getManager();

    // array($pseudo_id => extended_CRM_Extension_Info)
    $localExtensionRows = [];
    $keys = array_keys($manager->getStatuses());
    sort($keys);
    $hiddenExtensions = $mapper->getKeysByTag('mgmt:hidden');
    $requiredExtensions = $mapper->getKeysByTag('mgmt:required');
    foreach ($keys as $key) {
      if (in_array($key, $hiddenExtensions)) {
        continue;
      }
      try {
        $obj = $mapper->keyToInfo($key);
      }
      catch (CRM_Extension_Exception $ex) {
        CRM_Core_Session::setStatus(ts('Failed to read extension (%1). Please refresh the extension list.', [1 => $key]));
        continue;
      }

      $mapper = CRM_Extension_System::singleton()->getMapper();

      $row = self::createExtendedInfo($obj);
      $row['id'] = $obj->key;

      $localExtensionRows[$row['id']] = $row;
    }
    return $localExtensionRows;
  }

  /**
   * Get the list of remote extensions and format them as a table with
   * status and action data.
   *
   * @param array $localExtensionRows
   * @return array
   */
  public function findExtensionsNeedingUpgrade($localExtensionRows) {
    try {
      $remoteExtensions = CRM_Extension_System::singleton()->getBrowser()->getExtensions();
    }
    catch (CRM_Extension_Exception $e) {
      $remoteExtensions = [];
      CRM_Core_Session::setStatus($e->getMessage(), ts('Extension download error'), 'error');
    }

    // build list of available downloads
    $upgradeableExtensions = [];
    $compat = CRM_Extension_System::getCompatibilityInfo();

    foreach ($remoteExtensions as $info) {
      if (!empty($compat[$info->key]['obsolete'])) {
        continue;
      }
      if (isset($localExtensionRows[$info->key])) {
        if (array_key_exists('version', $localExtensionRows[$info->key])) {
          if (version_compare($localExtensionRows[$info->key]['version'], $info->version, '<')) {
            // We got one.
            $upgradeableExtensions[] = [
              'key' => $info->key,
              'name' => $info->name,
              'version' => $info->version,
            ];
          }
        }
      }
    }

    return $upgradeableExtensions;
  }

}

$ces = new myCRM_Extension_System();
$ces->getBrowser();
CRM_Extension_System::setSingleton($ces);
$e = new myCRM_Admin_Page_Extensions();
$localExtensionList = $e->formatLocalExtensionRows();
$upgradeableExtensions = $e->findExtensionsNeedingUpgrade($localExtensionList);
print_r(json_encode($upgradeableExtensions));
