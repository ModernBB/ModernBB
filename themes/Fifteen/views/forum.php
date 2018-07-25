<?php

// Make sure no one attempts to run this view directly.
if (!defined('FORUM'))
	exit;

?>
<div class="container main">
	<div class="row forumview">
		<div class="col-md-3">
			<div class="list-group list-group-nav">
				<a class="list-group-item" href="index.php"><span class="fas fa-fw fa-chevron-left"></span> <?php _e('Back to index', 'luna') ?></a>
			</div>
			<?php if (!$luna_user['is_guest'] && $luna_config['o_forum_subscriptions'] == '1') { ?>
			<hr />
			<div class="list-group list-group-nav">
				<?php if ($cur_forum['is_subscribed']) { ?>
					<a class="list-group-item list-group-item-success" href="misc.php?action=unsubscribe&amp;fid=<?php echo $id ?><?php echo $token_url ?>"><span class="fas fa-fw fa-star-o"></span> <?php _e('Unsubscribe', 'luna') ?></a>
				<?php } else { ?>
					<a class="list-group-item" href="misc.php?action=subscribe&amp;fid=<?php echo $id ?><?php echo $token_url ?>"><span class="fas fa-fw fa-star"></span> <?php _e('Subscribe', 'luna') ?></a>
				<?php } ?>
			</div>
			<?php } ?>
			<hr />
			<?php if ((is_subforum($id) && $id != '0')): ?>
				<h5 class="list-group-head"><?php _e('Subforums', 'luna') ?></h5>
				<div class="list-group list-group-nav">
					<?php draw_subforum_list('forum.php') ?>
				</div>
				<hr />
			<?php endif; ?>
			<div class="forum-list d-none d-md-block">
				<div class="list-group list-group-nav">
					<?php draw_forum_list('forum.php', 1, 'category.php', '') ?>
				</div>
				<hr />
			</div>
			<div class="list-group list-group-none">
				<a class="list-group-item" href="<?php echo get_read_url('forumview') ?>"><i class="fas fa-fw fa-glasses"></i> <?php _e('Mark as read', 'luna') ?></a>
				<?php if ($id != '0' && $is_admmod) { ?>
					<a class="list-group-item" href="backstage/moderate.php?fid=<?php echo $forum_id ?>&p=<?php echo $p ?>"><span class="fas fa-fw fa-eye"></span> <?php _e('Moderate forum', 'luna') ?></a>
				<?php } ?>
			</div>
			<div class="d-block d-md-none"><hr /></div>
		</div>
		<div class="col-md-9">
			<div class="title-block title-block-primary title-block-forum" style="background-color: <?php echo $cur_forum['color']; ?>;">
				<h2 class="forum-title"><?php echo $faicon.' '.luna_htmlspecialchars($cur_forum['forum_name']) ?><span class="float-right"><?php echo $comment_link ?></span></h2>
				<div class="forum-desc"><?php echo $cur_forum['forum_desc'] ?></div>
			</div>
            <?php echo $paging_links ?>
			<div class="list-group list-group-thread">
				<?php draw_threads_list(); ?>
			</div>
            <?php echo $paging_links ?>
		</div>
	</div>
</div>