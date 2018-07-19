<?php

// Make sure no one attempts to run this view directly.
if (!defined('FORUM'))
	exit;

?>

<div class="main container">
	<div class="row">
		<div class="col-12">
			<form method="post" action="delete.php?id=<?php echo $id ?>&action=soft">
				<div class="title-block title-block-danger">
					<h2>
						<i class="fas fa-fw fa-eye-slash"></i> <?php draw_delete_title(); ?>
						<span class="float-right">
							<button type="submit" class="btn btn-light btn-light-danger" name="soft_delete"><span class="fas fa-fw fa-eye-slash"></span> <?php _e('Hide', 'luna') ?></button>
						</span>
					</h2>
				</div>
				<div class="tab-content tab-content-danger">
					<p><?php echo ($is_thread_comment) ? '<strong>'.__('This is the first comment in the thread, the whole thread will be hidden.', 'luna').'</strong> ' : '' ?><?php _e('The comment you have chosen to hide is set out below for you to review before proceeding. Deleting this comment is not permanent. If you want to delete a comment permanently, please use delete instead.', 'luna') ?></p>
					<hr />
					<?php echo $cur_comment['message'] ?>
				</div>
			</form>
		</div>
	</div>
</div>