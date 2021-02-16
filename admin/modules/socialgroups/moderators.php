<?php
/**
 * Socialgroups plugin created by Mark Janssen.
 * This is not a free plugin.
 */

$baseurl = "index.php?module=socialgroups-moderators";
require_once MYBB_ROOT . "/inc/plugins/socialgroups/classes/socialgroups.php";
$socialgroups = new socialgroups();
$table = new TABLE;
$page->output_header("Social Group Moderators");
$sub_tabs = array(
    "browse" => array(
        "title" => "Browse",
        "link" => $baseurl),
    "create" => array(
        "title" => "Add Moderator",
        "link" => $baseurl . "&action=add"
    )
);

$page->output_nav_tabs($sub_tabs);

switch($mybb->input['action'])
{
    case "browse":
        socialgroups_moderators_browse();
        break;
    case "add":
        socialgroups_moderators_add();
        break;
    case "delete":
        socialgroups_moderators_delete($mybb->input['mid']);
        break;
    default:
        socialgroups_moderators_browse();
}

function socialgroups_moderators_browse()
{
    global $db, $table, $baseurl;
    $query = $db->query("SELECT m.*, u.username, u.usergroup, u.displaygroup FROM " . TABLE_PREFIX . "socialgroup_moderators m
    LEFT JOIN " . TABLE_PREFIX . "users u on (m.uid=u.uid) ORDER BY u.username ASC");
    $table->construct_header("Username");
    $table->construct_header("Manage");
    $table->construct_row();
    while($moderator = $db->fetch_array($query))
    {
        $table->construct_cell(format_name($moderator['username'], $moderator['usergroup'],$moderator['displaygroup']));
        $deletelink = $baseurl . "&action=delete&mid=" . $moderator['mid'];
        $edituserlink = "index.php?module=user-users&action=edit&uid=" . $moderator['uid'];
        $table->construct_cell("<a href='" . $edituserlink . "'>Edit User</a><br /><a href='" . $deletelink . "'>Delete</a>");
        $table->construct_row();
    }
    $table->output("Social Group User Moderators");
    $table->construct_header("Usergroup");
    $table->construct_header("Edit Group");
    $table->construct_row();
    $query = $db->simple_select("usergroups", "title, gid", "issupermod=1");
    while($moderatorgroup = $db->fetch_array($query))
    {
        $table->construct_cell(format_name($moderatorgroup['title'], $moderatorgroup['gid'], $moderatorgroup['gid']));
        $editgrouplink = "index.php?module=user-groups&action=edit&gid=" . $moderatorgroup['gid'];
        $table->construct_cell("<a href='" . $editgrouplink . "'>Edit Usergroup</a>");
        $table->construct_row();
    }
    $table->output("Social Group Usergroup Moderators");
}

function socialgroups_moderators_add()
{
    global $mybb, $db, $baseurl, $cache;
    if($mybb->request_method == "post")
    {
        // First we need to get a userid from username
        $options = array("fields" => "uid", "username", "usergroup");
        $user = get_user_by_username($mybb->input['username'], $options);
        if(!$user['uid'])
        {
            flash_message("Invalid User.", "error");
            admin_redirect($baseurl);
        }
        // Load the $cache and make sure the user isn't in a banned group.
        $usergroups = $cache->read("usergroups");
        if($usergroups[$user['usergroup']]['isbannedgroup'] == 1)
        {
            flash_message("The user specified is banned.", "error");
            admin_redirect($baseurl);
        }
        // Are they already a moderator?
        $query = $db->simple_select("socialgroup_moderators", "mid, uid", "uid=" . $user['uid']);
        if($db->num_rows($query) == 1)
        {
            flash_message("The user is already a social groups moderator.", "error");
            admin_redirect($baseurl);
        }
        // We are good to go.
        $new_moderator = array(
            "uid" => $user['uid']
        );
        $db->insert_query("socialgroup_moderators", $new_moderator);
        flash_message("The user has been added as a moderator successfully.", "success");
        admin_redirect($baseurl);
    }
    else
    {
        $form = new DefaultForm("index.php?module=socialgroups-moderators&action=add", "post");
        $form_container = new FormContainer("Add Moderator");
        $form_container->output_row("Username", "", $form->generate_text_box("username"));
        $form_container->end();
        $form->output_submit_wrapper(array($form->generate_submit_button("Add Moderator")));
        $form->end();
    }
}

function socialgroups_moderators_delete($mid)
{
    global $mybb, $db, $baseurl;
    $mid = (int) $mid;
    if($mybb->request_method == "post")
    {
        if($mybb->input['confirm'] == 1)
        {
            $db->delete_query("socialgroup_moderators", "mid=$mid");
            flash_message("The moderator has been deleted.","success");
        }
        admin_redirect($baseurl);
    }
    else
    {
        $form = new Form($baseurl . "&action=delete&mid=$mid", "post");
        $form_container = new FormContainer("Confirm Deletion");
        $form_container->output_row("Are you sure?", "", $form->generate_yes_no_radio("confirm", 0));
        $form_container->end();
        $form->output_submit_wrapper(array($form->generate_submit_button("Delete Moderator")));
        $form->end();
    }
}

$page->output_footer();