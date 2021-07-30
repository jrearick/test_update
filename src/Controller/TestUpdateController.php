<?php
namespace Drupal\test_update\Controller;

use Drupal\Core\Controller\ControllerBase;

class TestUpdateController extends ControllerBase {
  public function page() {
    $this->messenger()->addWarning($this->t('Before you run an update, be sure to backup the DB so you can revert and run it again!'));

    $function = \Drupal::request()->query->get('fn');
    $module = strstr($function, '_update_', TRUE);

    // If we didn't ask for a hook_update(), try to get the hook_install().
    if (!$module) {
      $module = strstr($function, '_install', TRUE);
    }

    $results = "";
    if ($module && $function) {
      $results .= "<strong>Module:</strong> " . $module . "<br>";
      $results .= "<strong>Function:</strong> " . $function . "<br>";

      $error = FALSE;

      // Make sure the module exists and get the path.
      if (\Drupal::moduleHandler()->moduleExists($module)) {
        $module_path = drupal_get_path('module', $module);
      } else {
        $this->messenger()->addError($this->t('The module %m was not found.', ['%m' => $module]));
        $error = TRUE;
      }

      // Make sure the module has an .install file, and get that path.
      if (!$error && file_exists($module_path . '/' . $module . '.install')) {
        $module_install = $module_path . '/' . $module . '.install';
      } else {
        $this->messenger()->addError($this->t('%m does not exist.', ['%m' => $module . '.install']));
        $error = TRUE;
      }

      if (!$error) {
        // Load up the install file so we can run stuff from it.
        include_once($module_install);

        if (function_exists($function)) {
          \Drupal::logger('test_update')->notice($this->t('Running %f', ['%f' => $function]));
          call_user_func($function);
          \Drupal::logger('test_update')->notice($this->t('Finished Running %f', ['%f' => $function]));
          $this->messenger()->addStatus($this->t('We ran %f in %m. Hopefully that worked.', ['%f' => $function, '%m' => $module]));
        } else {
          $this->messenger()->addError($this->t('%f was not found.', ['%f' => $function]));
          $error = TRUE;
        }
      }
      $results .= "<hr>";
    }

    $markup = <<<'MARKUP'
<p>This is a simple page that will run whatever function in a specified module's <em>.install</em> file.
define the module and function like so:</p>

<code>
/test-update?fn=my_module_update_8901
</code>

<p>The parameter <strong>fn</strong> is the hook_update you want to run. in the above example <code>my_module_update_8901</code> will run <code>my_module_update_8901();</code></p>

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
}
