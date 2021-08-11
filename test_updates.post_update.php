<?php

/**
 * @file
 * Post update functions for the test_updates module.
 */

/**
 * This is an example post_update hook to play with.
 */
function test_updates_post_update_example_update() {
  \Drupal::messenger()->addMessage('test_updates_post_update_example_update() was run. Huzza!');
}
