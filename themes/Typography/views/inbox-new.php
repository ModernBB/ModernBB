<?php

// Make sure no one attempts to run this view directly.
if (!defined('FORUM'))
	exit;

?>
<div class="main profile container">
	<div class="jumbotron default">
		<h2><?php echo $user['username'] ?></h2>
	</div>
	<div class="row">
		<div class="col-md-3 col-12 sidebar">
			<div class="container-avatar d-none d-md-block">
				<img src="<?php echo get_avatar( $user['id'] ) ?>" alt="Avatar" class="avatar">
			</div>
			<?php load_me_nav('inbox'); ?>
		</div>
		<div class="col-xs-12 col-sm-9">
<?php
// If there are errors, we display them
if (!empty($errors)) {
?>
            <div class="title-block title-block-danger">
                <h2><i class="fas fa-fw fa-exclamation-triangle"></i> <?php _e('Comment errors', 'luna') ?></h2>
            </div>
            <div class="tab-content tab-content-danger">
			<?php
				foreach ($errors as $cur_error)
					echo $cur_error;
			?>
			</div>
<?php

} elseif (isset($_POST['preview'])) {
	require_once LUNA_ROOT.'include/parser.php';
	$preview_message = parse_message($p_message);

?>
            <div class="title-block title-block-primary">
                <h2><i class="fas fa-fw fa-eye"></i> <?php _e('Comment preview', 'luna') ?></h2>
            </div>
            <div class="tab-content">
                <p><?php echo $preview_message ?></p>
            </div>
<?php

}

$cur_index = 1;

?>
			<form class="form-horizontal" method="post" id="comment" action="new_inbox.php" onsubmit="return process_form(this)">
				<div class="title-block title-block-primary">
					<h2><i class="fas fa-fw fa-paper-plane"></i> <?php _e('Inbox', 'luna') ?></h2>
				</div>
				<div class="new-inbox">
					<input type="hidden" name="form_sent" value="1" />
					<input type="hidden" name="form_user" value="<?php echo luna_htmlspecialchars($luna_user['username']) ?>" />
					<?php echo (($r != '0') ? '<input type="hidden" name="reply" value="'.$r.'" />' : '') ?>
					<?php echo (($q != '0') ? '<input type="hidden" name="quote" value="1" />' : '') ?>
					<?php echo (($tid != '0') ? '<input type="hidden" name="tid" value="'.$tid.'" />' : '') ?>
					<input type="hidden" name="p_username" value="<?php echo luna_htmlspecialchars($p_destinataire) ?>" />
					<input type="hidden" name="req_subject" value="<?php echo luna_htmlspecialchars($p_subject) ?>" />
					<?php if ($r != '1') { ?>
					<div class="form-group">
						<input class="form-control" type="text" name="p_username" placeholder="<?php _e('Receivers', 'luna') ?>" id="p_username" size="30" value="<?php echo luna_htmlspecialchars($p_destinataire) ?>" tabindex="<?php echo $cur_index++ ?>" autofocus />
					</div>
					<div class="form-group">
						<input class="form-control" type="text" name="req_subject" placeholder="<?php _e('Subject', 'luna') ?>" value="<?php echo ($p_subject != '' ? luna_htmlspecialchars($p_subject) : ''); ?>" tabindex="<?php echo $cur_index++ ?>" />
					</div>
					<?php } ?>
					<?php draw_editor('10'); ?>
				</div>
			</form>
		</div>
	</div>
</div>