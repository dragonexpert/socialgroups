<?php
/**
 * Socialgroups plugin created by Mark Janssen.
 * This is not a free plugin.
 */
if(!defined("IN_MYBB"))
{
    die("Direct access not allowed.");
}

$action = "browse";
if($mybb->get_input("action"))
{
    $action = $mybb->get_input("action");
}

require_once MYBB_ROOT . "/inc/plugins/socialgroups/classes/socialgroups.php";
$socialgroups = new socialgroups();
$page->output_header("Social Group Announcements");

$baseurl = "index.php?module=socialgroups-announcements";

// Default Routes Always There
$sub_tabs['browse'] = array(
    'title'         => 'Browse',
    'link'          => $baseurl,
    'description'   => 'Browse Announcements'
);

$sub_tabs['create'] = array(
    'title'         => 'Create Announcement',
    'link'          => $baseurl . '&action=add',
    'description'   => 'Create an Announcement'
);

$table = new TABLE;

switch($action)
{
    case "browse":
        $page->output_nav_tabs($sub_tabs, 'browse');
        socialgroups_announcements_browse();
        break;
    case "edit":
        $sub_tabs['edit'] = array(
            'title'         => 'Edit Announcement',
            'link'          => $baseurl . '&action=edit&aid='.$mybb->get_input("aid", MyBB::INPUT_INT),
            'description'   => 'Edit an Announcement'
        );

        $page->output_nav_tabs($sub_tabs, 'edit');
        socialgroups_announcement_edit($mybb->input['aid']);
        break;
    case "add":
        $page->output_nav_tabs($sub_tabs, 'create');
        socialgroups_announcement_add();
        break;
    case "delete":
        $sub_tabs['delete'] = array(
            'title'         => 'Delete Category',
            'link'          => $baseurl . '&action=delete&aid='.$mybb->get_input("aid", MyBB::INPUT_INT),
            'description'   => 'Delete an Announcement'
        );

        $page->output_nav_tabs($sub_tabs, 'delete');
        socialgroups_announcement_delete($mybb->get_input("aid", MyBB::INPUT_INT));
        break;
    default:
        $page->output_nav_tabs($sub_tabs, 'browse');
        socialgroups_announcements_browse();
        break;
}

function socialgroups_announcements_browse()
{
    global $socialgroups, $baseurl, $table;
    $socialgroups->load_announcements(0, true);
    $table->construct_header("Title");
    $table->construct_header("Text");
    $table->construct_header("Manage");
    $table->construct_row();
    if(!empty($socialgroups->announcements))
    {
        foreach($socialgroups->announcements[0] as $announcement)
        {
            $table->construct_cell(htmlspecialchars($announcement['subject']));
            $table->construct_cell(htmlspecialchars($announcement['message']));
            $editlink = $baseurl . "&action=edit&aid=" . $announcement['aid'];
            $deletelink = $baseurl . "&action=delete&aid=" . $announcement['aid'];
            $table->construct_cell("<a href='" . $editlink . "'>Edit</a><br /><a href='" . $deletelink . "'>Delete</a>");
            $table->construct_row();
        }
    }
    else
    {
        $table->construct_cell("There are no global announcements.", array("colspan" => 3));
        $table->construct_row();
    }
    $table->output("Announcements");
}

function socialgroups_announcement_add()
{
    global $mybb, $db, $baseurl;
    if ($mybb->request_method == "post") {
        $new_announcement = array(
            "subject" => $db->escape_string($mybb->get_input("subject")),
            "message" => $db->escape_string($mybb->get_input("message")),
            "active" => $mybb->get_input("active", MyBB::INPUT_INT),
            "gid" => 0,
            "uid" => $mybb->user['uid'],
            "dateline" => TIME_NOW
        );
        $db->insert_query("socialgroup_announcements", $new_announcement);
        flash_message("The announcement has been added.", "success");
        admin_redirect($baseurl);
    }
    else
    {
        $form = new DefaultForm("index.php?module=socialgroups-announcements&action=add", "post");
        $form_container = new FormContainer("Add Announcement");
        $form_container->output_row("Subject", "The title of the announcement", $form->generate_text_box("subject", ""));
        $form_container->output_row("Message", "", $form->generate_text_area("message", "", array("cols" => 70, "rows" => 5)));
        $form_container->output_row("Active", "If yes, this will show.", $form->generate_yes_no_radio("active", 1));
        $form_container->end();
        $form->output_submit_wrapper(array($form->generate_submit_button("Add Announcement")));
        $form->end();
    }
}

function socialgroups_announcement_edit(int $aid=0)
{
    global $mybb, $db, $baseurl;
    if($mybb->request_method == "post")
    {
        $updated_announcement = array(
            "subject" => $db->escape_string($mybb->get_input("subject")),
            "message" => $db->escape_string($mybb->get_input("message")),
            "active" => $mybb->get_input("active", MyBB::INPUT_INT)
        );
        $db->update_query("socialgroup_announcements", $updated_announcement, "aid=$aid");
        flash_message("The announcement has been updated.", "success");
        admin_redirect($baseurl);
    }
    else
    {
        $query = $db->simple_select("socialgroup_announcements", "*", "aid=$aid");
        $announcement = $db->fetch_array($query);
        $form = new DefaultForm("index.php?module=socialgroups-announcements&action=edit&aid=$aid", "post");
        $form_container = new FormContainer("Edit Announcement");
        $form_container->output_row("Subject", "The title of the announcement", $form->generate_text_box("subject", $announcement['subject']));
        $form_container->output_row("Message", "", $form->generate_text_area("message", $announcement['message'], array("cols" => 70, "rows" => 5)));
        $form_container->output_row("Active", "If yes, this will show.", $form->generate_yes_no_radio("active", $announcement['active']));
        $form_container->end();
        $form->output_submit_wrapper(array($form->generate_submit_button("Update Announcement")));
        $form->end();
    }
}

function socialgroups_announcement_delete(int $aid=0)
{
    global $mybb, $db, $baseurl;
    if($mybb->request_method == "post")
    {
        if($mybb->input['confirm'] == 1)
        {
            $db->delete_query("socialgroup_announcements", "aid=$aid");
            flash_message("The announcement has been deleted.","success");
        }
        admin_redirect($baseurl);
    }
    else
    {
        $form = new Form($baseurl . "&action=delete&aid=$aid", "post");
        $form_container = new FormContainer("Confirm Deletion");
        $form_container->output_row("Are you sure", "This cannot be undone.", $form->generate_yes_no_radio("confirm", 0));
        $form_container->end();
        $form->output_submit_wrapper(array($form->generate_submit_button("Delete Announcement")));
        $form->end();
    }
}

$page->output_footer();
