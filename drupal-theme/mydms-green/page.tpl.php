<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php print $language ?>" lang="<?php print $language ?>">
<head>
  <title><?php print $head_title ?></title>
  <?php print $head ?>
  <?php print $styles ?>
  <?php print $scripts ?>
</head>

<?php
/*
 * Initialise variables
 */
$feedIconPlaced = false;

if ($sidebar_left && $sidebar_right) {
	$mydmsBodyClass="sidebar-both";
}
else if ($sidebar_left) {
	$mydmsBodyClass="sidebar-left";
}
else if ($sidebar_right) {
	$mydmsBodyClass="sidebar-right";
}
else {
	$mydmsBodyClass="sidebar-none";
}
?>

<body <?php print ($mydmsBodyClass ? "class='".$mydmsBodyClass."'" : "") ?>>

<div class="globalBox">
<div class="globalTR"><?php /* Should have the configurable site logo here somehow. */ ?></div>
<?php

print $search_box;

// Primary Links at the top of the page.
if (isset($primary_links)) {
	print theme('links', $primary_links, array('class' => 'globalNav'));
}
// Secondary Links -- need to be properly configured, uncertain of how to
// present them.
if (isset($secondary_links)) {
	print theme('links', $secondary_links, array('class' => 'globalNav secondary-links'));
}
?>
<?php // Name of the site placed at the top left of the global heading area.
?>
<?php if ($site_name) { ?>
	<div class="siteName"><a href="<?php print $base_path ?>" title="<?php print t('Home') ?>"><?php print $site_name ?></a></div>
<?php } ?>

<?php /* User login status. */ ?>
<span class="absSpacerNorm"></span>
<div id="signatory"><?php print _user_login_bar() ?>
</div>
<div style="clear: both; height: 0px;">&nbsp;</div>
</div><?php /* EO Global Header */ ?>


<?php /* Page Title */ ?>
<div class="headingContainer">
<?php
if ($tabs) {
	print $tabs;
}
else {
?>
	<br/>
<?php
}
/* This is the result of a very nice modification copied from Garland.
 * Allows the primary and secondary tabs to be rendered separately. See
 * "template.php" for the implimentation details.
 */
if ($tabs2) {
	print $tabs2;
}
?>
<span class="absSpacerTitle"></span>
<div class="mainHeading"><?php print ($title ? $title : $site_name) ?></div>
<div style="clear: both; height: 0px;"></div>
</div>

<?php
/* Breadcrumbs */
if ($breadcrumb) {
	print $breadcrumb; 
}
else {
?>
<div class="breadcrumb"></div>
<?php
}

/* This refers to the header block in the page layout.
 * Blocks are used to contain modules, e.g. the login form.
 */
if ($header) {
?>
<div class="contentContainerFullWide">
<div class="contentCentre">
<div class="content-l"><div class="content-r"><div class="content-br"><div class="content-bl">
<?php print $header; ?>
<span class="clear"></span>
</div></div></div></div>
</div>
</div>
<?php
}

/*
 * Left-hand side-bar
 */
if ($sidebar_left) { ?>
<div class="contentContainerLeft">
<div class="contentCentre">
<div class="content-l"><div class="content-r"><div class="content-br"><div class="content-bl">
	<?php print $sidebar_left;
	if (!$feedIconPlaced && $feed_icons) {
	?>
		<div id="feedIcon"><?php print $feed_icons ?></div>
	<?php
		$feedIconPlaced=true;
	}
?>
	</div></div></div></div>
</div>
</div>
<?php
}

/*
 * Right-hand side-bar
 */
if ($sidebar_right) {
?>
<div class="contentContainerRight">
<div class="contentCentre">
<div class="content-l"><div class="content-r"><div class="content-br"><div class="content-bl">
	<?php print $sidebar_right;
	if (!$feedIconPlaced && $feed_icons) {
	?>
		<div id="feedIcon"><?php print $feed_icons ?></div>
	<?php
		$feedIconPlaced=true;
	}
	?>
</div></div></div></div>
</div>
</div>
<?php
}

/*
 * Main content container
 */
?>
<div class="contentContainer">
<div class="contentCentre">
<div class="content-l"><div class="content-r"><div class="content-br"><div class="content-bl">
<?php
if ($mission) {
	print '<div id="mission">'. $mission .'</div>';
}
if ($help) {
	print $help;
}
if ($messages) {
	print $messages;
}
print $content;
?>
<span class="clear"></span>
<?php
if (!$feedIconPlaced && $feed_icons) {
?>
	<div id="feedIcon"><?php print $feed_icons ?></div>
<?php
	$feedIconPlaced=true;
}
?>
</div></div></div></div>
</div>
</div>

<?php /* Footer */ ?>
<div class="contentContainer" id="footer"><?php print $footer_message ?></div>
<?php print $closure ?>
</body>
</html>
