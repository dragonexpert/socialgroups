<?php
/**
 * Socialgroups plugin created by Mark Janssen.
 * This is not a free plugin.
 */

if(!defined("IN_MYBB"))
{
    die("Direct access not allowed.");
}
$mybb->input['action'] = $mybb->get_input("action");
if(!$mybb->input['action'])
{
    $mybb->input['action'] = "browse";
}

require_once MYBB_ROOT . "/inc/plugins/socialgroups/classes/socialgroups.php";
$socialgroups = new socialgroups();
$page->output_header("Social Groups");
$baseurl = "index.php?module=socialgroups-groups";
$sub_tabs = array(
    "browse" => array(
        "title" => "Browse",
        "link" => $baseurl),
    "create" => array(
        "title" => "Create",
        "link" => $baseurl . "&action=add"
    )
);

$page->output_nav_tabs($sub_tabs);
$table = new TABLE;

switch($mybb->input['action'])
{
    case "browse":
        socialgroups_group_browse();
        break;
    case "edit":
        $gid = (int)$mybb->input['gid'];
        socialgroups_group_edit($gid);
        break;
    case "add":
        socialgroups_group_add();
        break;
    case "delete":
        socialgroups_group_delete($mybb->input['gid']);
        break;
    default:
        $plugins->run_hooks("admin_socialgroups_group_action");
        socialgroups_group_browse();
        break;
}

function socialgroups_group_browse()
{
    global $mybb, $db, $baseurl, $table, $socialgroups;
    $cid = 0;
    if($mybb->input['cid'])
    {
        $cid = (int) $mybb->input['cid'];
    }
    $currentpage = 1;
    if($mybb->input['page'])
    {
        $currentpage = (int) $mybb->input['page'];
    }
    $sortfield = $db->escape_string($mybb->get_input("sort"));
    $keywords = $mybb->get_input("keywords"); // This is sanitized in the function
    $socialgroups->list_groups($cid, $sortfield, $keywords, 50, $currentpage);

    // Cache group leaders here so we avoid querying with every loop.
    $query = $db->query("SELECT l.*, u.username, u.usergroup, u.displaygroup
                        FROM " . TABLE_PREFIX . "socialgroup_leaders l
                        LEFT JOIN " . TABLE_PREFIX . "users u ON(l.uid=u.uid)");

    $leaders = array();
    while($leader = $db->fetch_array($query))
    {
        $leaders[$leader['gid']] = array(
            "formattedname" => format_name($leader['username'], $leader['usergroup'], $leader['displaygroup'])
        );
    }

    $currentcid = 0;

    // Load the categories
    $query = $db->simple_select("socialgroup_categories", "*");
    while($category = $db->fetch_array($query))
    {
        $categories[$category['cid']] = $category;
    }

    foreach($socialgroups->group_list as $mainkey => $value)
    {
        foreach($socialgroups->group_list[$mainkey] as $subkey)
        {
            if($subkey['cid'] != $currentcid)
            {
                $table->construct_header("Name / Description");
                $table->construct_header("Threads");
                $table->construct_header("Posts");
                $table->construct_header("Creator");
                $table->construct_header("Leaders");
                $table->construct_header("Manage");
                $table->construct_row();
                $currentcid = $subkey['cid'];
            }
            $table->construct_cell(stripcslashes(htmlspecialchars_uni($subkey['name'])) . "<br />" . nl2br(stripcslashes(htmlspecialchars_uni($subkey['description']))));
            $table->construct_cell(number_format($subkey['threads']));
            $table->construct_cell(number_format($subkey['posts']));
            $table->construct_cell($subkey['profilelink']);
            $html = "";
            foreach($leaders[$subkey['gid']] as $groupleaders => $value)
            {
                $html .= $value . "<br />";
            }
            $table->construct_cell($html);
            $editlink = $baseurl . "&action=edit&gid=" . $subkey['gid'];
            $deletelink = $baseurl . "&action=delete&gid=" . $subkey['gid'];
            $table->construct_cell("<a href='$editlink'>Edit</a><br /><a href='$deletelink'>Delete</a>");
            $table->construct_row();
        }
        $table->output(stripcslashes(htmlspecialchars_uni($categories[$currentcid]['name'])));
    }
}

function socialgroups_group_edit($gid)
{
    global $db, $mybb, $baseurl, $table, $socialgroups;
    $groupquery = $db->query("SELECT g.*, u.username FROM " . TABLE_PREFIX . "socialgroups g
        LEFT JOIN ".TABLE_PREFIX."users u ON(g.uid=u.uid) WHERE g.gid=$gid");
    $groupinfo = $db->fetch_array($groupquery);
    if(!$groupinfo['gid'])
    {
        flash_message("Invalid Group.", "error");
        admin_redirect($baseurl);
    }
    if($mybb->request_method == "post")
    {
        $updated_group = array(
            "name" => $mybb->get_input('name'),
            "description" => $mybb->get_input('description'),
            "cid" => (int) $mybb->input['cid'],
            "approved" => 1,
            "private" => (int) $mybb->input['private'],
            "staffonly" => (int) $mybb->input['staffonly'],
            "inviteonly" => (int) $mybb->input['inviteonly'],
        );
        $leadername = $db->escape_string($mybb->input['leader']);
        // Verify if the username exists
        $query = $db->simple_select("users", "uid", "username='$leadername'");
        $leader = $db->fetch_array($query);
        $updated_group['uid'] = $leader['uid'];
        if(!$leader['uid'])
        {
            $updated_group['uid'] = $mybb->user['uid'];
        }
        // We need to check if the leader is changing.  If so, remove from group leaders.
        if($groupinfo['uid'] != $updated_group['uid'])
        {
            $db->delete_query("socialgroup_leaders", "gid=$gid AND uid=" . $groupinfo['uid']);
            $socialgroups->socialgroupsuserhandler->add_leader($gid, $updated_group['uid']);
        }
        $socialgroups->socialgroupsdatahandler->save_group($updated_group, "update", "gid=$gid");
        flash_message("Group Updated.", "success");
        admin_redirect($baseurl);
    }
    else
    {
        $form = new DefaultForm("index.php?module=socialgroups-groups&action=edit&gid=$gid", "post");
        $form_container = new FormContainer("Create Group");
        $form_container->output_row("Group Name", "", $form->generate_text_box("name", $groupinfo['name']));
        $form_container->output_row("Description", "",$form->generate_text_box("description", $groupinfo['description']));
        //Create the category list
        $query = $db->simple_select("socialgroup_categories", "cid,name");
        while($category = $db->fetch_array($query))
        {
            $categories[$category['cid']] = $category['name'];
        }
        $form_container->output_row("Category", "", $form->generate_select_box("cid", $categories, array($groupinfo['cid'])));
        $form_container->output_row("Creator", "", $form->generate_text_box("leader", $groupinfo['username']));
        $form_container->output_row("Private", "If yes, nonmembers will not be able to view topics.", $form->generate_select_box("private", array("0" => "No", "1" => "Yes"), array($groupinfo['private'])));
        $form_container->output_row("Staff Only", "If yes, only staff can see this group.", $form->generate_select_box("staffonly", array("0" => "No", "1" => "Yes"), array($groupinfo['staffonly'])));
        $form_container->end();
        $form->output_submit_wrapper(array($form->generate_submit_button("Update Group")));
        $form->end();
    }
}

function socialgroups_group_add()
{
    global $db, $mybb, $baseurl, $socialgroups;
    if($mybb->request_method == "post")
    {
        $new_group = array(
            "name" => $mybb->get_input('name'),
            "description" => $mybb->get_input('description'),
            "cid" => (int) $mybb->input['cid'],
            "approved" => 1,
            "private" => (int) $mybb->input['private'],
            "staffonly" => (int) $mybb->input['staffonly'],
            "inviteonly" => (int) $mybb->input['inviteonly'],
        );
        $leadername = $db->escape_string($mybb->input['leader']);
        // Verify if the username exists
        $query = $db->simple_select("users", "uid", "username='$leadername'");
        $leader = $db->fetch_array($query);
        $new_group['uid'] = $leader['uid'];
        if(!$leader['uid'])
        {
            $new_group['uid'] = $mybb->user['uid'];
        }
        $socialgroups->socialgroupsdatahandler->save_group($new_group, "insert");
        flash_message("Group created.", "success");
        admin_redirect($baseurl);
    }
    else
    {
        $form = new DefaultForm("index.php?module=socialgroups-groups&action=add", "post");
        $form_container = new FormContainer("Create Group");
        $form_container->output_row("Group Name", "", $form->generate_text_box("name", ""));
        $form_container->output_row("Description", "",$form->generate_text_box("description", ""));
        //Create the category list
        $query = $db->simple_select("socialgroup_categories", "cid,name");
        $categories = array();
        while($category = $db->fetch_array($query))
        {
            $categories[$category['cid']] = $category['name'];
        }
        if(empty($categories))
        {
            flash_message("Must create category first.", "error");
            admin_redirect($baseurl);
        }
        $form_container->output_row("Category", "", $form->generate_select_box("cid", $categories));
        $form_container->output_row("Creator", "", $form->generate_text_box("leader", $mybb->user['username']));
        $form_container->output_row("Private", "If yes, nonmembers will not be able to view topics.", $form->generate_select_box("private", array("0" => "No", "1" => "Yes")));
        $form_container->output_row("Staff Only", "If yes, only staff can see this group.", $form->generate_select_box("private", array("0" => "No", "1" => "Yes")));
        $form_container->end();
        $form->output_submit_wrapper(array($form->generate_submit_button("Add Group")));
        $form->end();
    }
}

function socialgroups_group_delete($gid)
{
    global $mybb, $db, $baseurl;
    $gid = (int) $gid;
    if($mybb->request_method=="post")
    {
        if ($mybb->input['confirm'] == 1)
        {
            // Delete the group
            $db->delete_query("socialgroups", "gid=$gid");
            $db->delete_query("socialgroup_members", "gid=$gid");
            $db->delete_query("socialgroup_member_permissions", "gid=$gid");
            $db->delete_query("socialgroup_leaders", "gid=$gid");
            $db->delete_query("socialgroup_invites", "gid=$gid");
            $db->delete_query("socialgroup_announcements", "gid=$gid");
            $db->delete_query("socialgroup_threads", "gid=$gid");
            $db->delete_query("socialgroup_posts", "gid=$gid");
            flash_message("The group has been deleted.", "success");
            admin_redirect($baseurl);
        }
        else
        {
            admin_redirect($baseurl);
        }
    }
    $form = new DefaultForm("index.php?module=socialgroups-groups&action=delete&gid=$gid", "post");
    $form_container = new FormContainer("Confirm Delete");
    $form_container->output_row("Are you sure?", "This action cannot be undone.", $form->generate_yes_no_radio("confirm", 0));
    $form_container->end();
    $form->output_submit_wrapper(array($form->generate_submit_button("Confirm Choice")));
    $form->end();
}

$page->output_footer();