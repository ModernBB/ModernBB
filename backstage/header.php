<?php

/*
 * Copyright (C) 2013-2020 Luna
 * Based on code by FluxBB copyright (C) 2008-2012 FluxBB
 * Based on code by Rickard Andersson copyright (C) 2002-2008 PunBB
 * Licensed under GPLv2 (http://getluna.org/license.php)
 */

// Make sure no one attempts to run this script "directly"
if (!defined('FORUM'))
	exit;

// Send the Content-type header in case the web server is setup to send something else
header('Content-type: text/html; charset=utf-8');

// Define $p if it's not set to avoid a PHP notice
$p = isset($p) ? $p : null;

$hour = date("g", time());
if ($luna_user['adapt_time'] == 1 || (($luna_user['adapt_time'] == 2) && (($hour <= 7) || ($hour >= 19))))
	$body_classes = 'night';
else
	$body_classes = 'normal';
    
if (__('Direction of language', 'luna') == 'rtl')
    $body_classes .= ' rtl';
else
    $body_classes .= ' ltr';

if (file_exists('../img/header.png') || file_exists('../img/header.jpg'))
    $body_classes .= ' bkg';

if (file_exists('../img/header.png'))
    $body_classes .= ' bkg-png';
elseif (file_exists('../img/header.jpg'))
    $body_classes .= ' bkg-jpg';

// Check for new notifications
$noticount = $db->query('SELECT COUNT(id) FROM '.$db->prefix.'notifications WHERE viewed = 0 AND user_id = '.$luna_user['id']) or error('Unable to count notifications', __FILE__, __LINE__, $db->error());
$num_notifications = $db->result($noticount);

if ($luna_config['o_notification_flyout'] == 1) {
	if ($num_notifications == '0') {
		$notificon = '<span class="far fa-fw fa-circle"></span>';
		$ind_notification[] = '<li><a href="../notifications.php">'.__( 'No new notifications', 'luna' ).'</a></li>';
	} else {
		$notificon = $num_notifications.' <span class="fas fa-fw fa-circle"></span>';
		
		$notification_result = $db->query('SELECT * FROM '.$db->prefix.'notifications WHERE user_id = '.$luna_user['id'].' AND viewed = 0 ORDER BY time DESC LIMIT 10') or error ('Unable to load notifications', __FILE__, __LINE__, $db->error());
		while ($cur_notifi = $db->fetch_assoc($notification_result)) {
			$notifitime = format_time($cur_notifi['time'], false, null, $luna_config['o_time_format'], true, true);
			$ind_notification[] = '<li class="overflow"><a href="../notifications.php?notification='.$cur_notifi['id'].'"><span class="timestamp">'.$notifitime.'</span> <span class="fas fa-fw '.$cur_notifi['icon'].'"></span> '.$cur_notifi['message'].'</a></li>';
		}
	}

	$notifications = implode('<li class="divider"></li>', $ind_notification);
	$notification_menu_item = '
					<li class="dropdown">
					<a href="#" class="dropdown-toggle" data-toggle="dropdown"><span class="'.(($num_notifications != 0)? ' flash' : '').'">'.$notificon.'<span class="visible-xs-inline"> '.__( 'Notifications', 'luna' ).'</span></span></a>
					<ul class="dropdown-menu notification-menu">
						<li role="presentation" class="dropdown-header">'.__( 'Notifications', 'luna' ).'</li>
						<li class="divider"></li>
						'.$notifications.'
						<li class="divider"></li>
                        <li class="dropdown-footer hidden-xs"><a class="pull-right" href="../notifications.php">'.__('More', 'luna').' <i class="fas fa-fw fa-arrow-right"></i></a></li>
                        <li class="dropdown-footer hidden-lg hidden-md hidden-sm"><a href="../notifications.php">'.__('More', 'luna').' <i class="fas fa-fw fa-arrow-right"></i></a></li>
					</ul>
				</li>';
} else {
	if ($num_notifications == '0')
		$notificon = '<span class="far fa-fw fa-circle"></span>';
	else
		$notificon = $num_notifications.' <span class="fas fa-fw fa-circle"></span>';

	$notification_menu_item = '<li><a href="../notifications.php" class="'.(($num_notifications != 0)? ' flash' : '').'">'.$notificon.'<span class="visible-xs-inline"> '.__( 'Notifications', 'luna' ).'</span></a></li>';
}

?>
<!DOCTYPE html>
<html class="<?php echo $body_classes ?> backstage">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
		<link rel="stylesheet" href="../vendor/css/bootstrap.min.css">
		<link rel="stylesheet" href="../vendor/css/all.min.css">
		<link rel="stylesheet" type="text/css" href="css/style.css" />
		<link rel="stylesheet" type="text/css" href="css/accents/<?php echo $luna_user['accent'] ?>.css" />
        <?php
            if (__('Direction of language', 'luna') == 'rtl')
                echo '<link rel="stylesheet" type="text/css" href="../vendor/css/bidirect.css" />';
          ?>
        <link rel="icon" href="../img/favicon.png" />
		<meta name="ROBOTS" content="NOINDEX, FOLLOW" />
		<title><?php _e('Backstage', 'luna') ?></title>
	</head>
	<body>
        <nav class="navbar navbar-default" role="navigation">
            <div class="container">
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                        <span class="sr-only">Toggle navigation</span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>
                    <a class="navbar-brand" href="../index.php"><i class="fas fa-fw fa-angle-left hidden-xs"></i><span class="visible-xs-inline"><?php echo $luna_config['o_board_title'] ?></span></a>
                </div>
                <div class="navbar-collapse collapse">
                    <ul class="nav navbar-nav">
                        <li class="<?php if (LUNA_SECTION == 'backstage') echo 'active'; ?>"><a href="index.php"><i class="fas fa-fw fa-tachometer-alt"></i> <?php _e('Backstage', 'luna') ?></a></li>
                        <?php if ($is_admin) { ?>
                            <li class="<?php if (LUNA_SECTION == 'content') echo 'active'; ?>"><a href="board.php"><i class="fas fa-fw fa-clone"></i> <?php _e('Content', 'luna') ?></a></li>
                        <?php } else { ?>
                            <li class="<?php if (LUNA_SECTION == 'content') echo 'active'; ?>"><a href="reports.php"><i class="fas fa-fw fa-clone"></i> <?php _e('Content', 'luna') ?></a></li>
                        <?php } ?>
                        <li class="<?php if (LUNA_SECTION == 'users') echo 'active'; ?>"><a href="users.php"><i class="fas fa-fw fa-users"></i> <?php _e('Users', 'luna') ?></a></li>
                        <?php if ($is_admin) { ?><li class="<?php if (LUNA_SECTION == 'settings') echo 'active'; ?>"><a href="settings.php"><i class="fas fa-fw fa-cog"></i> <?php _e('Settings', 'luna') ?></a></li><?php } ?>
                        <?php if ($is_admin) { ?><li class="<?php if (LUNA_SECTION == 'maintenance') echo 'active'; ?>"><a href="maintenance.php"><i class="fas fa-fw fa-coffee"></i> <?php _e('Maintenance', 'luna') ?></a></li>	<?php } ?>
        <?php

            // See if there are any plugins
            $plugins = forum_list_plugins($is_admin);

            // Did we find any plugins?
            if (!empty($plugins))
            {
        ?>
                        <li class="dropdown<?php if (SECTION == ' extensions') echo 'active'; ?>">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                                <i class="fas fa-fw fa-cogs"></i> <?php _e('Extensions', 'luna') ?> <i class="fas fa-fw fa-angle-down"></i>
                            </a>
                            <ul class="dropdown-menu">
        <?php
                foreach ($plugins as $plugin_name => $plugin_entry)
                    echo "\t\t\t\t\t".'<li><a href="loader.php?plugin='.$plugin_name.'">'.str_replace('_', ' ', $plugin_entry).'</a></li>'."\n";
        ?>
                            </ul>
                        </li>
        <?php } ?>
                    </ul>
        <?php
        $logout_url = '../login.php?action=out&amp;id='.$luna_user['id'].'&amp;csrf_token='.luna_csrf_token();
        ?>
                    <ul class="nav navbar-nav navbar-right">
                        <?php echo $notification_menu_item ?>
                        <li class="dropdown usermenu">
                            <a href="../profile.php?id=<?php echo $luna_user['id'] ?>" class="dropdown-toggle dropdown-user" data-toggle="dropdown">
                                <?php echo draw_user_avatar($luna_user['id'], true, 'avatar'); ?><span class="hidden-lg hidden-md hidden-sm"> <?php echo luna_htmlspecialchars($luna_user['username']); ?></span>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a href="../profile.php?id=<?php echo $luna_user['id'] ?>"><i class="fas fa-fw fa-user"></i> <?php _e('Profile', 'luna') ?></a></li>
                                <li><a href="../inbox.php"><i class="fas fa-fw fa-paper-plane"></i> <?php _e('Inbox', 'luna') ?></a></li>
                                <li><a href="../settings.php?id=<?php echo $luna_user['id'] ?>"><i class="fas fa-fw fa-cogs"></i> <?php _e('Settings', 'luna') ?></a></li>
                                <li class="divider"></li>
                                <li><a href="../help.php"><i class="fas fa-fw fa-info-circle"></i> <?php _e('Help', 'luna') ?></a></li>
                                <li><a href="http://getluna.org"><i class="fas fa-fw fa-support"></i> <?php _e('Support', 'luna') ?></a></li>
                                <li class="divider"></li>
                                <li><a href="<?php echo $logout_url; ?>"><i class="fas fa-fw fa-sign-out-alt"></i> <?php _e('Logout', 'luna') ?></a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        <div class="jumbotron jumboheader">
            <div class="container">
                <ul class="nav nav-tabs nav-main" role="tablist">
                <?php if (LUNA_SECTION == 'backstage') { ?>
                    <li<?php if(LUNA_PAGE == 'index') echo ' class="active"' ?>><a href="index.php"><i class="fas fa-fw fa-tachometer-alt"></i> <?php _e('Backstage', 'luna') ?></a></li>
                    <li<?php if(LUNA_PAGE == 'update') echo ' class="active"' ?>><a href="update.php"><i class="fas fa-fw fa-arrow-circle-up"></i> <?php _e('Update', 'luna') ?></a></li>
                    <li<?php if(LUNA_PAGE == 'about') echo ' class="active"' ?>><a href="about.php"><i class="fas fa-fw fa-moon"></i> <?php _e('About', 'luna') ?></a></li>
                <?php } if (LUNA_SECTION == 'content') { ?>
                    <li<?php if(LUNA_PAGE == 'board') echo ' class="active"' ?>><a href="board.php"><i class="fas fa-fw fa-list"></i> <?php _e('Board', 'luna') ?></a></li>
                    <li<?php if(LUNA_PAGE == 'reports') echo ' class="active"' ?>><a href="reports.php"><i class="fas fa-fw fa-flag"></i> <?php _e('Reports', 'luna') ?></a></li>
                    <li<?php if(LUNA_PAGE == 'censoring') echo ' class="active"' ?>><a href="censoring.php"><i class="fas fa-fw fa-eye-slash"></i> <?php _e('Censoring', 'luna') ?></a></li>
                <?php } if (LUNA_SECTION == 'users') { ?>
                    <li<?php if(LUNA_PAGE == 'users') echo ' class="active"' ?>><a href="users.php"><i class="fas fa-fw fa-search"></i> <?php _e('Search', 'luna') ?></a></li>
                    <li<?php if(LUNA_PAGE == 'ranks') echo ' class="active"' ?>><a href="ranks.php"><i class="fas fa-fw fa-trophy"></i> <?php _e('Ranks', 'luna') ?></a></li>
                    <li<?php if(LUNA_PAGE == 'groups') echo ' class="active"' ?>><a href="groups.php"><i class="fas fa-fw fa-users"></i> <?php _e('Groups', 'luna') ?></a></li>
                    <li<?php if(LUNA_PAGE == 'bans') echo ' class="active"' ?>><a href="bans.php"><i class="fas fa-fw fa-ban"></i> <?php _e('Bans', 'luna') ?></a></li>
                <?php } if (LUNA_SECTION == 'settings') { ?>
                    <li<?php if(LUNA_PAGE == 'settings') echo ' class="active"' ?>><a href="settings.php"><i class="fas fa-fw fa-cogs"></i> <?php _e('Settings', 'luna') ?></a></li>
                    <li<?php if(LUNA_PAGE == 'features') echo ' class="active"' ?>><a href="features.php"><i class="fas fa-fw fa-sliders-h"></i> <?php _e('Features', 'luna') ?></a></li>
                    <li<?php if(LUNA_PAGE == 'appearance') echo ' class="active"' ?>><a href="appearance.php"><i class="fas fa-fw fa-paint-brush"></i> <?php _e('Theme', 'luna') ?></a></li>
                    <li<?php if(LUNA_PAGE == 'menu') echo ' class="active"' ?>><a href="menu.php"><i class="fas fa-fw fa-bars"></i> <?php _e('Menu', 'luna') ?></a></li>
                <?php } if (LUNA_SECTION == 'maintenance') { ?>
                    <li<?php if(LUNA_PAGE == 'maintenance') echo ' class="active"' ?>><a href="maintenance.php"><i class="fas fa-fw fa-coffee"></i> <?php _e('Maintenance', 'luna') ?></a></li>
                    <li<?php if(LUNA_PAGE == 'prune') echo ' class="active"' ?>><a href="prune.php"><i class="fas fa-fw fa-recycle"></i> <?php _e('Prune', 'luna') ?></a></li>
                <?php } ?>
                </ul>
            </div>
        </div>
        <div class="content">
            <div class="container main">
                <?php if ($luna_config['o_maintenance'] == '1') { ?>
                    <div class="row"><div class="col-xs-12"><div class="alert alert-danger"><i class="fas fa-fw fa-exclamation-triangle"></i> <?php _e('Luna is currently set in Maintenance Mode. Do not log off.', 'luna') ?></div></div></div>
                <?php } ?>
<?php
if (isset($required_fields)) {
	// Output JavaScript to validate form (make sure required fields are filled out)

?>
<script type="text/javascript">
/* <![CDATA[ */
function process_form(the_form) {
	var required_fields = {
<?php
	// Output a JavaScript object with localised field names
	$tpl_temp = count($required_fields);
	foreach ($required_fields as $elem_orig => $elem_trans) {
		echo "\t\t\"".$elem_orig.'": "'.addslashes(str_replace('&#160;', ' ', $elem_trans));
		if (--$tpl_temp) echo "\",\n";
		else echo "\"\n\t};\n";
	}
?>
	if (document.all || document.getElementById) {
		for (var i = 0; i < the_form.length; ++i) {
			var elem = the_form.elements[i];
			if (elem.name && required_fields[elem.name] && !elem.value && elem.type && (/^(?:text(?:area)?|password|file)$/i.test(elem.type))) {
				alert('"' + required_fields[elem.name] + '" <?php _e('is a required field in this form.', 'luna') ?>');
				elem.focus();
				return false;
			}
		}
	}
	return true;
}
/* ]]> */
</script>
<?php

}

if (isset($page_head))
	echo implode("\n", $page_head);
