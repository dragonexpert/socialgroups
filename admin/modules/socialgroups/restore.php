<?php
/**
 * Socialgroups Plugin by Mark Janssen
 */
if(!defined("IN_MYBB"))
{
    die("Direct access not allowed.");
}

$action = "threads";
if($mybb->get_input("action"))
{
    $action = $mybb->get_input("action");
}
require_once MYBB_ROOT . "/inc/plugins/socialgroups/classes/socialgroups.php";
$socialgroups = new socialgroups();
$page->output_header("Social Group Restore Threads");

$baseurl = "index.php?module=socialgroups-restore";
$tid = 0;
if($mybb->get_input("tid"))
{
    $tid = $mybb->get_input("tid", MyBB::INPUT_INT);
}
$pid = 0;
if($mybb->get_input("pid"))
{
    $pid = $mybb->get_input("pid", MyBB::INPUT_INT);
}


// Default Routes Always There
$sub_tabs['threads'] = array(
    'title'         => $lang->socialgroups_restore_threads,
    'link'          => $baseurl,
    'description'   => $lang->socialgroups_restore_threads_description
);

$sub_tabs['posts'] = array(
    'title'         => $lang->socialgroups_restore_posts,
    'link'          => $baseurl . '&action=posts',
    'description'   => $lang->socialgroups_restore_posts_description
);

$page->output_nav_tabs($sub_tabs, $action);

if($action == "threads")
{
    require_once MYBB_ROOT . "inc/class_parser.php";
    $parser = new PostParser();
    $parser_options = array(
        "allow_mycode" => 1,
        "allow_smilies"=> 1,
        "filter_badwords" => 1,
        "allow_html" => 0
    );
    $query = $db->query("SELECT t.*, p.message FROM " . TABLE_PREFIX . "socialgroup_threads t
    LEFT JOIN " . TABLE_PREFIX . "socialgroup_posts p ON(t.firstpost=p.pid)
    WHERE t.visible=-1
    ORDER BY t.dateline ASC");
    $table = new TABLE;
    $table->construct_header("Subject");
    $table->construct_header("Message");
    $table->construct_header("Manage", array("colspan" => 2));
    $table->construct_row();
    if($db->num_rows($query) == 0)
    {
        $table->construct_cell($lang->socialgroups_no_deleted_threads, array("colspan" => 4));
        $table->construct_row();
    }
    while($thread = $db->fetch_array($query))
    {

        $restore_link = "<a href='" . $baseurl . "&amp;action=restore_thread&amp;tid=" . $thread['tid'] . "'>" . $lang->socialgroups_restore_thread . "</a>";
        $delete_link = "<a href='" . $baseurl . "&amp;action=delete_thread&amp;tid=" . $thread['tid'] . "'>" . $lang->socialgroups_permanent_delete_thread . "</a>";
        $parsed_message = $parser->parse_message($thread['message'], $parser_options);
        $parsed_subject = $parser->parse_message($thread['subject'], $parser_options);
        $table->construct_cell($parsed_subject);
        $table->construct_cell($parsed_message);
        $table->construct_cell($restore_link);
        $table->construct_cell($delete_link);
        $table->construct_row();
    }
    $db->free_result($query);
    $table->output($lang->socialgroups_socialgroups_thread_management);
}
else if($action == "delete_thread" && $mybb->get_input("tid"))
{
    // Get the thread info before deletion
    $query = $db->simple_select("socialgroup_threads", "*", "tid=" . $tid);
    $thread = $db->fetch_array($query);
    if(!isset($thread['tid']))
    {
        flash_message($lang->socialgroups_invalid_thread, "error");
        admin_redirect($baseurl);
    }
    $socialgroups->socialgroupsthreadhandler->delete_thread($tid, $thread['gid'], 1);
    $data = array(
        "conid" => $tid,
        "gid" => $thread['gid'],
        "action" => "Permanently Deleted Thread: " . $db->escape_string($thread['subject'])
    );
    log_moderator_action($data, "Permanently Deleted Thread: " . $db->escape_string($thread['subject']));
    flash_message($lang->socialgroups_thread_deleted_permanently, "success");
    admin_redirect($baseurl);
}
else if($action == "restore_thread" && $tid)
{
    $query = $db->simple_select("socialgroup_threads", "*", "tid=" . $tid);
    $thread = $db->fetch_array($query);
    if(!isset($thread['tid']))
    {
        flash_message($lang->socialgroups_invalid_thread, "error");
        admin_redirect($baseurl);
    }
    $socialgroups->socialgroupsthreadhandler->restore_thread($thread['tid'], false);
    flash_message($lang->socialgroups_thread_restored, "success");
    admin_redirect($baseurl);
}
else if($action == "posts")
{
    require_once MYBB_ROOT . "inc/class_parser.php";
    $parser = new PostParser();
    $parser_options = array(
        "allow_mycode" => 1,
        "allow_smilies"=> 1,
        "filter_badwords" => 1,
        "allow_html" => 0
    );
    $query = $db->query("SELECT p.*, t.subject FROM " . TABLE_PREFIX . "socialgroup_posts p
    LEFT JOIN " . TABLE_PREFIX . "socialgroup_threads t ON(p.pid=t.firstpost)
    WHERE p.visible=-1
    ORDER BY p.dateline ASC");
    $table = new TABLE;
    $table->construct_header("Subject");
    $table->construct_header("Message");
    $table->construct_header("Manage", array("colspan" => 2));
    $table->construct_row();
    while($thread = $db->fetch_array($query))
    {

        $restore_link = "<a href='" . $baseurl . "&amp;action=restore_post&amp;pid=" . $thread['pid'] . "'>" . $lang->socialgroups_restore_post . "</a>";
        $delete_link = "<a href='" . $baseurl . "&amp;action=delete_post&amp;pid=" . $thread['pid'] . "'>" . $lang->socialgroups_permanent_delete_post . "</a>";
        $parsed_message = $parser->parse_message($thread['message'], $parser_options);
        $parsed_subject = $parser->parse_message($thread['subject'], $parser_options);
        $table->construct_cell($parsed_subject);
        $table->construct_cell($parsed_message);
        $table->construct_cell($restore_link);
        $table->construct_cell($delete_link);
        $table->construct_row();
    }
    if($db->num_rows($query) == 0)
    {
        $table->construct_cell($lang->socialgroups_no_deleted_posts, array("colspan" => 4));
        $table->construct_row();
    }
    $db->free_result($query);
    $table->output($lang->socialgroups_socialgroups_post_management);
}
else if($action == "delete_post" && $pid)
{
    $query = $db->simple_select("socialgroup_posts", "*", "pid=" . $pid);
    $post = $db->fetch_array($query);
    if(!isset($post['pid']))
    {
        flash_message($lang->socialgroups_invalid_post, "error");
        admin_redirect($baseurl . "&action=posts");
    }
    $socialgroups->socialgroupsthreadhandler->delete_post($pid, $post['gid'], 1);
    flash_message($lang->socialgroups_post_deleted_permanently, "success");
    admin_redirect($baseurl . "&action=posts");
}
else if($action == "restore_post" && $pid)
{
    $query = $db->simple_select("socialgroup_posts", "*", "pid=" . $pid);
    $post = $db->fetch_array($query);
    if(!isset($post['pid']))
    {
        flash_message($lang->socialgroups_invalid_post, "error");
        admin_redirect($baseurl . "&action=posts");
    }
    $socialgroups->socialgroupsthreadhandler->restore_post($pid, false);
    flash_message($lang->socialgroups_post_restored, "success");
    admin_redirect($baseurl . "&action=posts");
}
else
{
    $plugins->run_hooks("socialgroups_admin_restore_custom_action");
}
$page->output_footer();