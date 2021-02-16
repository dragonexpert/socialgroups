<?php
/**
 * Socialgroups plugin created by Mark Janssen.
 * This is not a free plugin.
 */
define("IN_MYBB", 1);
define("THIS_SCRIPT", "creategroup.php");
$templatelist = "socialgroups_create_group_page";
require_once "global.php";
require_once "inc/plugins/socialgroups/classes/socialgroups.php";
$socialgroups = new socialgroups();
add_breadcrumb("Social Groups", "groups.php");
add_breadcrumb("Create Group", "creategroup.php");
if(!$mybb->usergroup['cancreatesocialgroups'] || $mybb->user['uid'] == 0 || $mybb->usergroup['isbannedgroup'])
{
    error_no_permission();
}
// Do we have a limit on group creation?
if($mybb->usergroup['maxsocialgroups_create'] != 0)
{
    $query = $db->simple_select("socialgroups", "COUNT(gid) as total", "uid=" . $mybb->user['uid']);
    $total = $db->fetch_field($query, "total");
    if($total >= $mybb->usergroup['maxsocialgroups_create'])
    {
        error("You have reached the limit for groups you can create.");
    }
}
if($mybb->request_method=="post" && verify_post_check($mybb->input['my_post_key']))
{
    $new_group = array(
        "name" => $db->escape_string($mybb->get_input('name')),
        "description" => $db->escape_string($mybb->get_input('description')),
        "logo" => $db->escape_string($mybb->get_input('logo')),
        "cid" => (int) $mybb->input['cid'],
        "approved" => $mybb->usergroup['socialgroups_auto_approve'],
        "private" => (int) $mybb->input['private'],
        "staffonly" => (int) $mybb->input['staffonly'],
        "inviteonly" => (int) $mybb->input['inviteonly'],
        "uid" => (int) $mybb->user['uid']
    );
    $socialgroups->socialgroupsdatahandler->save_group($new_group, "insert");
    if($new_group['approved'] == 0)
    {
        $approvaltext = " and will appear once it has been approved";
    }
    redirect("groups.php", "The group has been created" . $approvaltext . ".");
}
// Get the category list.
$viewablecategories = $socialgroups->get_viewable_categories();
foreach($viewablecategories as $cid => $name)
{
    eval("\$categoryselect .=\"".$templates->get("socialgroups_category_select")."\";");
}
if($mybb->usergroup['canmodcp'])
{
    eval("\$staffonly =\"".$templates->get("socialgroups_staffonly")."\";");
}
eval("\$creategrouppage =\"".$templates->get("socialgroups_create_group_page")."\";");
output_page($creategrouppage);