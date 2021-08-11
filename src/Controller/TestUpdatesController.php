<?php
namespace Drupal\test_updates\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ExtensionList;
use Drupal\Core\Render\Element\Page;

class TestUpdatesController extends ControllerBase {
  /*
  * The requested function name.
  */
  public $functionName = "";

  /*
  * The type of function (install, update, post_update).
  */
  public $functionType = "";

  /*
   * The module name.
   */
  public $moduleName = "";

  function __construct() {
    $this->functionName = $this->getFunctionName();
    $this->functionType = $this->getFunctionType();
    $this->moduleName = $this->getModuleName();
  }

  public function page() {
    $this->messenger()->addWarning($this->t('Before you run an update, be sure to backup the DB so you can revert and run it again!'));

    $ran = FALSE;
    if ($this->functionName && $this->functionType && $this->moduleName) {
      $ran = $this->runFunction();
    }

    $results = "";
    if ($ran) {
      $results .= "<strong>Module:</strong> " . $this->moduleName . "<br>";
      $results .= "<strong>Function:</strong> " . $this->functionName . "<br>";
      $results .= "<strong>Type:</strong> " . $this->functionType . "<br>";

      $results .= "<hr>";
    }

    $markup = <<<'MARKUP'
<p>This is a simple page that will run whatever function in a specified module's <em>.install</em> file.
define the module and function like so:</p>

<code>
/test-update?fn=test_updates_update_9001
</code>

<p>The parameter <strong>fn</strong> is the hook_update you want to run. in the above example <code>test_updates_update_9001</code> will run <code>test_updates_update_9001();</code></p>

<p>Here's a recommended testing flow:</p>
<ul>
<li>Build things until just before you want to run the update(s)</li>
<li>Back up the database</li>
<li>Turn on xDebug and set breakpoints</li>
<li>Run your update(s) by going to this page with the query parameters</li>
<li>Evaluate the result</li>
<li>To run again, restore the database from the backup you took earlier and reload this page</li>
</ul>
MARKUP;

    return ['#markup' => $results . $markup];
  }

  /*
   * Get the function name that was requested.
   */
  private function getFunctionName(): string {
    return trim(\Drupal::request()->query->get('fn'));
  }

  /*
   * Get the type of function requested (install, update, post_update).
   */
  private function getFunctionType(): string {
    // Check if it's a HOOK_post_update_NAME().
    if (stripos($this->functionName, '_post_update_') !== FALSE) {
      return "post_update";
    }

    // Check if it's a HOOK_install().
    $length = strlen($this->functionName);
    if ($length > 0 && substr($this->functionName, $length - 8) == '_install') {
      return "install";
    }

    // Check if it's a HOOK_update_N().
    if (stripos($this->functionName, '_update_') !== FALSE) {
      return "update";
    }

    return "";
  }

  /*
   * Get the module name from the function name.
   */
  private function getModuleName(): string {
    switch ($this->functionType) {
      case 'install':
        // Example foo_bar_install, remove 8 from the end gets foo_bar.
        return substr($this->functionName, 0, -8);
      case 'post_update':
        // Example foo_bar_post_update_monkey_robot. Find the position of
        // _post_update_ and get the substr to that index.
        return substr($this->functionName, 0, stripos($this->functionName, '_post_update_'));
      case 'update':
        // Example foo_bar_update_9001. Find the postion of _update_ and get
        // the substr to that index.
        return substr($this->functionName, 0, stripos($this->functionName, '_update_'));
    }
    return "";
  }

  /*
   * Runs the requested function.
   */
  private function runFunction(): bool {
    // Make sure the module exists and get the path.
    // @todo Make this extension type agnostic (module, profile, theme, etc).
    if (\Drupal::moduleHandler()->moduleExists($this->moduleName)) {
      $module_path = drupal_get_path('module', $this->moduleName);
    } else {
      $this->messenger()->addError($this->t('The module %m was not found.', ['%m' => $this->moduleName]));
      return FALSE;
    }

    // Try to include the appropriate file.
    if ($this->functionType == 'post_update') {
      if (file_exists($module_path . '/' . $this->moduleName . '.post_update.php')) {
        $file = $module_path . '/' . $this->moduleName . '.post_update.php';
      } else {
        $this->messenger()->addError($this->t('%f was not found.', ['%f' => $this->moduleName . '.post_update.php']));
        return FALSE;
      }
    }
    // It must be an install or update found in the .install file.
    else {
      // Make sure the module has a .install file, and get that path.
      if (file_exists($module_path . '/' . $this->moduleName . '.install')) {
        $file = $module_path . '/' . $this->moduleName . '.install';
      } else {
        $this->messenger()->addError($this->t('%m does not exist.', ['%m' => $this->moduleName . '.install']));
        return FALSE;
      }
    }

    // Load up the install file so we can run stuff from it.
    include_once($file);

    if (function_exists($this->functionName)) {
      \Drupal::logger('test_updates')->notice($this->t('Running %f', ['%f' => $this->functionName]));
      call_user_func($this->functionName);
      \Drupal::logger('test_updates')->notice($this->t('Finished Running %f', ['%f' => $this->functionName]));
      $this->messenger()->addMessage($this->t('We ran %f in %m. Hopefully that worked.', ['%f' => $this->functionName, '%m' => $this->moduleName]));
    } else {
      $this->messenger()->addError($this->t('%f was not found.', ['%f' => $this->functionName]));
      return FALSE;
    }

    return TRUE;
  }
}
