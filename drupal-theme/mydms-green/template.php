<?php

function phptemplate_menu_local_tasks() {
  $output = '';

  if ($primary = _menu_primary_local_tasks()) {
    $output .= "<ul class=\"tabs primary\">\n". $primary ."</ul>\n";
  }
	/* This has been handled separately by adding in a custom variable
	 * (see the _phptemplate_variables function below).
	 */
  /*
	if ($secondary = _menu_secondary_local_tasks()) {
    $output .= "<ul class=\"tabs secondary\">\n". $secondary ."</ul>\n";
  }
	*/
  return $output;
}


/**
 * Override or insert PHPTemplate variables into the templates.
 * From the Garland Theme. Allows me to separate primary tabs from secondary tabs.
 */
function _phptemplate_variables($hook, $vars) {
  if ($hook == 'page') {

    if ($secondary = _menu_secondary_local_tasks()) {
      $output .= "<ul class=\"tabs secondary\">\n". $secondary ."</ul>\n";
      $vars['tabs2'] = $output;
    }

    // Hook into color.module
    if (module_exists('color')) {
      _color_page_alter($vars);
    }
    return $vars;
  }
  return array();
}


/**
 * Returns the rendered HTML of the primary local tasks.
 */
function _menu_primary_local_tasks() {
  $local_tasks = menu_get_local_tasks();
  $pid = menu_get_active_nontask_item();
  $output = '';
  $first=true;

  if (count($local_tasks[$pid]['children'])) {
    foreach ($local_tasks[$pid]['children'] as $mid) {
      $output .= theme('menu_local_task', $mid, menu_in_active_trail($mid), TRUE, $first);
      $first=false;
    }
  }

  return $output;
}

/**
 * Returns the rendered HTML of the secondary local tasks.
 */
function _menu_secondary_local_tasks() {
  $local_tasks = menu_get_local_tasks();
  $pid = menu_get_active_nontask_item();
  $output = '';
  $first=true;

  if (count($local_tasks[$pid]['children'])) {
    foreach ($local_tasks[$pid]['children'] as $mid) {
      if (menu_in_active_trail($mid) && count($local_tasks[$mid]['children']) > 1) {
        foreach ($local_tasks[$mid]['children'] as $cid) {
          $output .= theme('menu_local_task', $cid, menu_in_active_trail($cid), FALSE, $first);
					$first=false;
        }
      }
    }
  }

  return $output;
}


/**
 * Generate the HTML representing a given menu item ID as a tab.
 *
 * @param $mid
 *   The menu ID to render.
 * @param $active
 *   Whether this tab or a subtab is the active menu item.
 * @param $primary
 *   Whether this tab is a primary tab or a subtab.
 *
 * @ingroup themeable
 */
function phptemplate_menu_local_task($mid, $active, $primary, $first) {
  if ($active) {
    return '<li class="active"'.($first ? ' id="first"' : '').'>'. menu_item_link($mid) ."</li>\n";
  }
  else {
    return '<li'.($first ? ' id="first"' : '').'>'. menu_item_link($mid) ."</li>\n";
  }
}


function _user_login_bar() {
  global $user;                                                               
  $output = '';

  if (!$user->uid) {                                                          
    $output .= drupal_get_form('user_login_block');                           
  }                                                                           
  else {                                                                      
    $output .= t('<p class="user-info">Signed in as !user.</p>', array('!user' => theme('username', $user)));
 
    $output .= theme('item_list', array(
      l(t('Your account'), 'user/'.$user->uid, array('title' => t('Edit your account'))),
      l(t('Sign out'), 'logout')));
  }
   
  $output = '<div id="user-bar">'.$output.'</div>';
     
  return $output;
}


?>
