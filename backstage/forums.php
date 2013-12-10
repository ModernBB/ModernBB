<?php

/**
 * Copyright (C) 2013 ModernBB
 * Based on code by FluxBB copyright (C) 2008-2012 FluxBB
 * Based on code by Rickard Andersson copyright (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 3 or higher
 */

// Tell header.php to use the admin template
define('FORUM_ADMIN_CONSOLE', 1);

define('FORUM_ROOT', '../');
require FORUM_ROOT.'include/common.php';
require FORUM_ROOT.'include/common_admin.php';

if (!$pun_user['is_admmod']) {
    header("Location: ../login.php");
}

if ($pun_user['g_id'] != FORUM_ADMIN)
	message($lang['No permission'], false, '403 Forbidden');

// Add a "default" forum
if (isset($_POST['add_forum']))
{
	$forum_name = pun_trim($_POST['new_forum']); 
	$add_to_cat = intval($_POST['add_to_cat']);
	if ($add_to_cat < 1)
		message($lang['Bad request'], false, '404 Not Found');

	$db->query('INSERT INTO '.$db->prefix.'forums (forum_name, cat_id) VALUES(\''.$db->escape($forum_name).'\', '.$add_to_cat.')') or error('Unable to create forum', __FILE__, __LINE__, $db->error());

	redirect('backstage/forums.php', $lang['Forum added redirect']);
}

// Delete a forum
else if (isset($_GET['del_forum']))
{
	$forum_id = intval($_GET['del_forum']);
	if ($forum_id < 1)
		message($lang['Bad request'], false, '404 Not Found');

	if (isset($_POST['del_forum_comply'])) // Delete a forum with all posts
	{
		@set_time_limit(0);

		// Prune all posts and topics
		prune($forum_id, 1, -1);

		// Locate any "orphaned redirect topics" and delete them
		$result = $db->query('SELECT t1.id FROM '.$db->prefix.'topics AS t1 LEFT JOIN '.$db->prefix.'topics AS t2 ON t1.moved_to=t2.id WHERE t2.id IS NULL AND t1.moved_to IS NOT NULL') or error('Unable to fetch redirect topics', __FILE__, __LINE__, $db->error());
		$num_orphans = $db->num_rows($result);

		if ($num_orphans)
		{
			for ($i = 0; $i < $num_orphans; ++$i)
				$orphans[] = $db->result($result, $i);

			$db->query('DELETE FROM '.$db->prefix.'topics WHERE id IN('.implode(',', $orphans).')') or error('Unable to delete redirect topics', __FILE__, __LINE__, $db->error());
		}

		// Delete the forum and any forum specific group permissions
		$db->query('DELETE FROM '.$db->prefix.'forums WHERE id='.$forum_id) or error('Unable to delete forum', __FILE__, __LINE__, $db->error());
		$db->query('DELETE FROM '.$db->prefix.'forum_perms WHERE forum_id='.$forum_id) or error('Unable to delete group forum permissions', __FILE__, __LINE__, $db->error());

		// Delete any subscriptions for this forum
		$db->query('DELETE FROM '.$db->prefix.'forum_subscriptions WHERE forum_id='.$forum_id) or error('Unable to delete subscriptions', __FILE__, __LINE__, $db->error());

		redirect('backstage/forums.php', $lang['Forum deleted redirect']);
	}
	else // If the user hasn't confirmed the delete
	{
		$result = $db->query('SELECT forum_name FROM '.$db->prefix.'forums WHERE id='.$forum_id) or error('Unable to fetch forum info', __FILE__, __LINE__, $db->error());
		$forum_name = pun_htmlspecialchars($db->result($result));

		$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang['Admin'], $lang['Forums']);
		define('FORUM_ACTIVE_PAGE', 'admin');
		require FORUM_ROOT.'backstage/header.php';
	generate_admin_menu('forums');

?>
<div class="content">
    <h2><?php echo $lang['Confirm delete head'] ?></h2>
    <form class="alert alert-danger" method="post" action="forums.php?del_forum=<?php echo $forum_id ?>">
        <fieldset>
            <p><?php printf($lang['Confirm delete forum info'], $forum_name) ?></p>
            <p class="warntext"><?php echo $lang['Confirm delete forum'] ?></p>
        </fieldset>
        <div>
        	<input class="btn btn-danger" type="submit" name="del_forum_comply" value="<?php echo $lang['Delete'] ?>" /><a class="btn btn-default" href="javascript:history.go(-1)"><?php echo $lang['Go back'] ?></a>
        </div>
    </form>
</div>
<?php

		require FORUM_ROOT.'backstage/footer.php';
	}
}

// Update forum positions
else if (isset($_POST['update_positions']))
{
	foreach ($_POST['position'] as $forum_id => $disp_position)
	{
		$disp_position = trim($disp_position);
		if ($disp_position == '' || preg_match('%[^0-9]%', $disp_position))
			message($lang['Must be integer message']);

		$db->query('UPDATE '.$db->prefix.'forums SET disp_position='.$disp_position.' WHERE id='.intval($forum_id)) or error('Unable to update forum', __FILE__, __LINE__, $db->error());
	}

	redirect('backstage/forums.php', $lang['Forums updated redirect']);
}

else if (isset($_GET['edit_forum']))
{
	$forum_id = intval($_GET['edit_forum']);
	if ($forum_id < 1)
		message($lang['Bad request'], false, '404 Not Found');

	// Update group permissions for $forum_id
	if (isset($_POST['save']))
	{
		// Start with the forum details
		$forum_name = pun_trim($_POST['forum_name']);
		$forum_desc = pun_linebreaks(pun_trim($_POST['forum_desc']));
		$cat_id = intval($_POST['cat_id']);
		$sort_by = intval($_POST['sort_by']);
		$redirect_url = isset($_POST['redirect_url']) ? pun_trim($_POST['redirect_url']) : null;

		if ($forum_name == '')
			message($lang['Must enter name message']);

		if ($cat_id < 1)
			message($lang['Bad request'], false, '404 Not Found');

		$forum_desc = ($forum_desc != '') ? '\''.$db->escape($forum_desc).'\'' : 'NULL';
		$redirect_url = ($redirect_url != '') ? '\''.$db->escape($redirect_url).'\'' : 'NULL';

		$db->query('UPDATE '.$db->prefix.'forums SET forum_name=\''.$db->escape($forum_name).'\', forum_desc='.$forum_desc.', redirect_url='.$redirect_url.', sort_by='.$sort_by.', cat_id='.$cat_id.' WHERE id='.$forum_id) or error('Unable to update forum', __FILE__, __LINE__, $db->error());
		
		// Now let's deal with the permissions
		if (isset($_POST['read_forum_old']))
		{
			$result = $db->query('SELECT g_id, g_read_board, g_post_replies, g_post_topics FROM '.$db->prefix.'groups WHERE g_id!='.FORUM_ADMIN) or error('Unable to fetch user group list', __FILE__, __LINE__, $db->error());
			while ($cur_group = $db->fetch_assoc($result))
			{
				$read_forum_new = ($cur_group['g_read_board'] == '1') ? isset($_POST['read_forum_new'][$cur_group['g_id']]) ? '1' : '0' : intval($_POST['read_forum_old'][$cur_group['g_id']]);
				$post_replies_new = isset($_POST['post_replies_new'][$cur_group['g_id']]) ? '1' : '0';
				$post_topics_new = isset($_POST['post_topics_new'][$cur_group['g_id']]) ? '1' : '0';

				// Check if the new settings differ from the old
				if ($read_forum_new != $_POST['read_forum_old'][$cur_group['g_id']] || $post_replies_new != $_POST['post_replies_old'][$cur_group['g_id']] || $post_topics_new != $_POST['post_topics_old'][$cur_group['g_id']])
				{
					// If the new settings are identical to the default settings for this group, delete its row in forum_perms
					if ($read_forum_new == '1' && $post_replies_new == $cur_group['g_post_replies'] && $post_topics_new == $cur_group['g_post_topics'])
						$db->query('DELETE FROM '.$db->prefix.'forum_perms WHERE group_id='.$cur_group['g_id'].' AND forum_id='.$forum_id) or error('Unable to delete group forum permissions', __FILE__, __LINE__, $db->error());
					else
					{
						// Run an UPDATE and see if it affected a row, if not, INSERT
						$db->query('UPDATE '.$db->prefix.'forum_perms SET read_forum='.$read_forum_new.', post_replies='.$post_replies_new.', post_topics='.$post_topics_new.' WHERE group_id='.$cur_group['g_id'].' AND forum_id='.$forum_id) or error('Unable to insert group forum permissions', __FILE__, __LINE__, $db->error());
						if (!$db->affected_rows())
							$db->query('INSERT INTO '.$db->prefix.'forum_perms (group_id, forum_id, read_forum, post_replies, post_topics) VALUES('.$cur_group['g_id'].', '.$forum_id.', '.$read_forum_new.', '.$post_replies_new.', '.$post_topics_new.')') or error('Unable to insert group forum permissions', __FILE__, __LINE__, $db->error());
					}
				}
			}
		}

		redirect('backstage/forums.php', $lang['Forum updated redirect']);
	}
	else if (isset($_POST['revert_perms']))
	{
		$db->query('DELETE FROM '.$db->prefix.'forum_perms WHERE forum_id='.$forum_id) or error('Unable to delete group forum permissions', __FILE__, __LINE__, $db->error());

		redirect('backstage/forums.php?edit_forum='.$forum_id, $lang['Perms reverted redirect']);
	}

	// Fetch forum info
	$result = $db->query('SELECT id, forum_name, forum_desc, redirect_url, num_topics, sort_by, cat_id FROM '.$db->prefix.'forums WHERE id='.$forum_id) or error('Unable to fetch forum info', __FILE__, __LINE__, $db->error());

	if (!$db->num_rows($result))
		message($lang['Bad request'], false, '404 Not Found');

	$cur_forum = $db->fetch_assoc($result);
	
	$cur_index = 7;
	
	$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang['Admin'], $lang['Forums']);
	define('FORUM_ACTIVE_PAGE', 'admin');
	require FORUM_ROOT.'backstage/header.php';
	generate_admin_menu('forums');

?>
<h2><?php echo $lang['Forum settings'] ?></h2>
<form id="edit_forum" class="form-horizontal" method="post" action="forums.php?edit_forum=<?php echo $forum_id ?>">
    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title"><?php echo $lang['Edit details subhead'] ?><span class="pull-right"><input class="btn btn-primary" type="submit" name="save" value="<?php echo $lang['Save changes'] ?>" tabindex="<?php echo $cur_index++ ?>" /></span></h3>
        </div>
        <div class="panel-body">
            <fieldset>
                <div class="form-group">
                    <label class="col-sm-2 control-label"><?php echo $lang['Forum name label'] ?></label>
                    <div class="col-sm-10">
                        <input type="text" class="form-control" name="forum_name" size="35" maxlength="80" value="<?php echo pun_htmlspecialchars($cur_forum['forum_name']) ?>" tabindex="1" />
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-2 control-label"><?php echo $lang['Forum description label'] ?></label>
                    <div class="col-sm-10">
                        <textarea class="form-control" name="forum_desc" rows="3" cols="80" tabindex="2"><?php echo pun_htmlspecialchars($cur_forum['forum_desc']) ?></textarea>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-2 control-label"><?php echo $lang['Category label'] ?></label>
                    <div class="col-sm-10">
						<select class="form-control" name="cat_id" tabindex="3">
<?php

	$result = $db->query('SELECT id, cat_name FROM '.$db->prefix.'categories ORDER BY disp_position') or error('Unable to fetch category list', __FILE__, __LINE__, $db->error());
	while ($cur_cat = $db->fetch_assoc($result))
	{
		$selected = ($cur_cat['id'] == $cur_forum['cat_id']) ? ' selected="selected"' : '';
		echo "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$cur_cat['id'].'"'.$selected.'>'.pun_htmlspecialchars($cur_cat['cat_name']).'</option>'."\n";
	}

?>
						</select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-2 control-label"><?php echo $lang['Sort by label'] ?></label>
					<div class="col-sm-10">
                        <select class="form-control" name="sort_by" tabindex="4">
                            <option value="0"<?php if ($cur_forum['sort_by'] == '0') echo ' selected="selected"' ?>><?php echo $lang['Last post'] ?></option>
                            <option value="1"<?php if ($cur_forum['sort_by'] == '1') echo ' selected="selected"' ?>><?php echo $lang['Topic start'] ?></option>
                            <option value="2"<?php if ($cur_forum['sort_by'] == '2') echo ' selected="selected"' ?>><?php echo $lang['Subject'] ?></option>
                        </select>
                    </div>
                </div>
				<?php if (($cur_forum['num_topics']) == '0'): ?>
                <div class="form-group">
                    <label class="col-sm-2 control-label"><?php echo $lang['Redirect label'] ?></label>
					<div class="col-sm-10">
                        <?php echo ($cur_forum['num_topics']) ? $lang['Redirect help'] : '<input type="text" class="form-control"name="redirect_url" size="45" maxlength="100" value="'.pun_htmlspecialchars($cur_forum['redirect_url']).'" tabindex="5" />'; ?>
                    </div>
                </div>
				<?php endif; ?>
            </fieldset>
        </div>
    </div>
    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title"><?php echo $lang['Group permissions subhead'] ?><span class="pull-right"><input class="btn btn-primary" type="submit" name="save" value="<?php echo $lang['Save changes'] ?>" tabindex="<?php echo $cur_index++ ?>" /></span></h3>
        </div>
        <div class="panel-body">
            <fieldset>
                <p><?php printf($lang['Group permissions info'], '<a href="groups.php">'.$lang['User groups'].'</a>') ?></p>
                <div><input class="btn btn-warning pull-right" type="submit" name="revert_perms" value="<?php echo $lang['Revert to default'] ?>" tabindex="<?php echo $cur_index++ ?>" /></div>
                <table class="table table-condensed">
                    <thead>
                        <tr>
                            <th class="atcl">&#160;</th>
                            <th><?php echo $lang['Read forum label'] ?></th>
                            <th><?php echo $lang['Post replies label'] ?></th>
                            <th><?php echo $lang['Post topics label'] ?></th>
                        </tr>
                    </thead>
                    <tbody>
<?php

	$result = $db->query('SELECT g.g_id, g.g_title, g.g_read_board, g.g_post_replies, g.g_post_topics, fp.read_forum, fp.post_replies, fp.post_topics FROM '.$db->prefix.'groups AS g LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (g.g_id=fp.group_id AND fp.forum_id='.$forum_id.') WHERE g.g_id!='.FORUM_ADMIN.' ORDER BY g.g_id') or error('Unable to fetch group forum permission list', __FILE__, __LINE__, $db->error());

	while ($cur_perm = $db->fetch_assoc($result))
	{
		$read_forum = ($cur_perm['read_forum'] != '0') ? true : false;
		$post_replies = (($cur_perm['g_post_replies'] == '0' && $cur_perm['post_replies'] == '1') || ($cur_perm['g_post_replies'] == '1' && $cur_perm['post_replies'] != '0')) ? true : false;
		$post_topics = (($cur_perm['g_post_topics'] == '0' && $cur_perm['post_topics'] == '1') || ($cur_perm['g_post_topics'] == '1' && $cur_perm['post_topics'] != '0')) ? true : false;

		// Determine if the current settings differ from the default or not
		$read_forum_def = ($cur_perm['read_forum'] == '0') ? false : true;
		$post_replies_def = (($post_replies && $cur_perm['g_post_replies'] == '0') || (!$post_replies && ($cur_perm['g_post_replies'] == '' || $cur_perm['g_post_replies'] == '1'))) ? false : true;
		$post_topics_def = (($post_topics && $cur_perm['g_post_topics'] == '0') || (!$post_topics && ($cur_perm['g_post_topics'] == '' || $cur_perm['g_post_topics'] == '1'))) ? false : true;

?>
                        <tr>
                            <th class="atcl"><?php echo pun_htmlspecialchars($cur_perm['g_title']) ?></th>
                            <td<?php if (!$read_forum_def) echo ' class="danger"'; ?>>
                                <input type="hidden" name="read_forum_old[<?php echo $cur_perm['g_id'] ?>]" value="<?php echo ($read_forum) ? '1' : '0'; ?>" />
                                <input type="checkbox" name="read_forum_new[<?php echo $cur_perm['g_id'] ?>]" value="1"<?php echo ($read_forum) ? ' checked="checked"' : ''; ?><?php echo ($cur_perm['g_read_board'] == '0') ? ' disabled="disabled"' : ''; ?> tabindex="<?php echo $cur_index++ ?>" />
                            </td>
                            <td<?php if (!$post_replies_def && $cur_forum['redirect_url'] == '') echo ' class="danger"'; ?>>
                                <input type="hidden" name="post_replies_old[<?php echo $cur_perm['g_id'] ?>]" value="<?php echo ($post_replies) ? '1' : '0'; ?>" />
                                <input type="checkbox" name="post_replies_new[<?php echo $cur_perm['g_id'] ?>]" value="1"<?php echo ($post_replies) ? ' checked="checked"' : ''; ?><?php echo ($cur_forum['redirect_url'] != '') ? ' disabled="disabled"' : ''; ?> tabindex="<?php echo $cur_index++ ?>" />
                            </td>
                            <td<?php if (!$post_topics_def && $cur_forum['redirect_url'] == '') echo ' class="danger"'; ?>>
                                <input type="hidden" name="post_topics_old[<?php echo $cur_perm['g_id'] ?>]" value="<?php echo ($post_topics) ? '1' : '0'; ?>" />
                                <input type="checkbox" name="post_topics_new[<?php echo $cur_perm['g_id'] ?>]" value="1"<?php echo ($post_topics) ? ' checked="checked"' : ''; ?><?php echo ($cur_forum['redirect_url'] != '') ? ' disabled="disabled"' : ''; ?> tabindex="<?php echo $cur_index++ ?>" />
                            </td>
                        </tr>
<?php

}

?>
                    </tbody>
                </table>
            </fieldset>
        </div>
    </div>
</form>

<?php

	require FORUM_ROOT.'backstage/footer.php';
}

$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang['Admin'], $lang['Forums']);
define('FORUM_ACTIVE_PAGE', 'admin');
require FORUM_ROOT.'backstage/header.php';
	generate_admin_menu('forums');

?>
<h2><?php echo $lang['Forums'] ?></h2>
<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title"><?php echo $lang['Add forum'] ?></h3>
    </div>
    <div class="panel-body">
        <form method="post" action="forums.php?action=adddel">
            <fieldset>
<?php

	$result = $db->query('SELECT id, cat_name FROM '.$db->prefix.'categories ORDER BY disp_position') or error('Unable to fetch category list', __FILE__, __LINE__, $db->error());
	if ($db->num_rows($result) > 0)
	{ ?>
                <select class="form-control" name="add_to_cat" tabindex="1">
    <?php
		while ($cur_cat = $db->fetch_assoc($result))
			{ ?>
                <?php echo "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$cur_cat['id'].'">'.pun_htmlspecialchars($cur_cat['cat_name']).'</option>'."\n";
			} ?>
                </select>
                <input type="text" class="form-control" name="new_forum" size="30" maxlength="80" placeholder="Forum name" required="required" />
                <input class="btn btn-primary" type="submit" name="add_forum" value="<?php echo $lang['Add forum'] ?>" tabindex="2" />
                <span class="help-block"><?php echo $lang['Add forum help'] ?></span>
            <?php }
	if (!$db->num_rows($result) > 0) {
		echo $lang['No categories exist'];
	}
?>
            </fieldset>
        </form>
    </div>
</div>
<?php

// Display all the categories and forums
$result = $db->query('SELECT c.id AS cid, c.cat_name, f.id AS fid, f.forum_name, f.disp_position FROM '.$db->prefix.'categories AS c INNER JOIN '.$db->prefix.'forums AS f ON c.id=f.cat_id ORDER BY c.disp_position, c.id, f.disp_position') or error('Unable to fetch category/forum list', __FILE__, __LINE__, $db->error());

$cur_index = 4;

if ($db->num_rows($result) > 0)
{

?>
<form id="edforum" method="post" action="forums.php?action=edit">
	<div class="panel panel-default">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo $lang['Edit forum head'] ?><span class="pull-right"><input class="btn btn-primary" type="submit" name="update_positions" value="<?php echo $lang['Update positions'] ?>" tabindex="<?php echo $cur_index++ ?>" /></span></h3>
		</div>
		<div class="panel-body">
            <fieldset>
<?php

$cur_index = 4;

$cur_category = 0;
while ($cur_forum = $db->fetch_assoc($result))
{
	if ($cur_forum['cid'] != $cur_category) // A new category since last iteration?
	{
		if ($cur_category != 0)
			echo "\t\t\t\t\t\t\t".'</tbody>'."\n\t\t\t\t\t\t\t".'</table>'."\n";

?>
                <table class="table">
                    <tbody>
                    	<tr>
                        	<th colspan="3" class="active">
								<h4><?php echo pun_htmlspecialchars($cur_forum['cat_name']) ?></h4>
                            </th>
                        </tr>
<?php

		$cur_category = $cur_forum['cid'];
	}

?>
                        <tr>
                            <td class="col-lg-2"><a class="btn btn-primary" href="forums.php?edit_forum=<?php echo $cur_forum['fid'] ?>" tabindex="<?php echo $cur_index++ ?>"><?php echo $lang['Edit link'] ?></a><a class="btn btn-primary" href="forums.php?del_forum=<?php echo $cur_forum['fid'] ?>" tabindex="<?php echo $cur_index++ ?>"><?php echo $lang['Delete link'] ?></a></td>
                            <td class="col-lg-4"><input type="text" class="form-control" name="position[<?php echo $cur_forum['fid'] ?>]" size="3" maxlength="3" value="<?php echo $cur_forum['disp_position'] ?>" tabindex="<?php echo $cur_index++ ?>" /></td>
							<td><strong><?php echo pun_htmlspecialchars($cur_forum['forum_name']) ?></strong></td>
                        </tr>
<?php

}

?>
                    </tbody>
                </table>
            </fieldset>
		</div>
	</div>
</form>
<?php
}

require FORUM_ROOT.'backstage/footer.php';
