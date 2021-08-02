<?php
/**
 * Socialgroups plugin created by Mark Janssen.
 */
if(!defined("IN_MYBB"))
{
    die("Direct access not allowed.");
}

if(!$db->table_exists("socialgroups_documentation"))
{
    flash_message("You must import the documentation to use this module.  Please see developer documentation for more details.", "error");
    admin_redirect("index.php?module=socialgroups");
}

$action = "browse";
if($mybb->get_input("action"))
{
    $action = $mybb->get_input("action");
}

$page->output_header("Social Group Hook Documentation");

$baseurl = "index.php?module=socialgroups-hooks";

// Default Routes Always There
$sub_tabs['browse'] = array(
    'title'         => 'Browse',
    'link'          => $baseurl,
    'description'   => 'Browse Documentation'
);

$sub_tabs['create'] = array(
    'title'         => 'Create Documentation',
    'link'          => $baseurl . '&action=add',
    'description'   => 'Document a plugin hook'
);

$file_name_array = array("groups" => "groups.php", "showgroup" => "showgroup.php", "groupthread" => "groupthread.php", "groupcp" => "groupcp.php",
    "class_socialgroups" => "inc/plugins/socialgroups/classes/socialgroups.php", "class_socialgroupsdatahandler" => "inc/plugins/socialgroups/classes/socialgroupsdatahandler.php",
    "class_socialgroupsreports" => "inc/plugins/socialgroups/classes/socialgroupsreports.php", "class_socialgroupsthreadhandler" => "inc/plugins/socialgroups/classes/socialgroupsthreadhandler.php",
    "class_socialgroupsuserhandler" => "inc/plugins/socialgroups/classes/socialgroupsuserhandler.php", "admin_announcements" => "admin/modules/socialgroups/announcements.php",
    "admin_category" => "admin/modules/socialgroups/category.php", "admin_groups" => "admin/modules/socialgroups/groups.php",
    "admin_leaders" => "admin/modules/socialgroups/leaders.php", "admin_moderators" => "admin/modules/socialgroups/moderators.php",
    "admin_module_meta" => "admin/modules/socialgroups/module_meta.php", "admin_restore" => "admin/modules/socialgroups/restore.php",
    "admin_templates" => "admin/modules/socialgroups/templates.php");

if($action == "add")
{
    if($mybb->request_method == "post")
    {
        $new_hook = array(
            "file_name" => $db->escape_string($mybb->get_input("file_name")),
            "hook_name" => $db->escape_string($mybb->get_input("hook_name")),
            "hook_argument" => $db->escape_string($mybb->get_input("hook_argument")),
            "purpose" => $db->escape_string($mybb->get_input("purpose")),
            "documentation_type" => "hook",
            "last_updated" => time()
        );
        $new_hook['file_name'] = $file_name_array[$new_hook['file_name']];
        $db->insert_query("socialgroups_documentation", $new_hook);
        flash_message("The hook has been documented", "success");
        admin_redirect($baseurl . "&action=add");
    }
    $page->output_nav_tabs($sub_tabs, "add");
    $form = new DefaultForm($baseurl . "&action=add", "post");
    $form_container = new FormContainer("Add Plugin Hook Documentation");

    $form_container->output_row("Hook Name", "The name of the hook", $form->generate_text_box("hook_name", ""), "hook_name");
    $form_container->output_row("Hook Argument", "An argument passed to the hook", $form->generate_text_box("hook_argument", ""), "hook_argument");
    $form_container->output_row("File Name", "The name of the file", $form->generate_select_box("file_name", $file_name_array, ""), "file_name");
    $form_container->output_row("Hook Purpose", "The use for the hook", $form->generate_text_area("purpose", "", array("cols" => 70, "rows" => 10)), "purpose");
    $form_container->end();
    $form->output_submit_wrapper(array($form->generate_submit_button("Add Hook Documentation")));
    $form->end();

}

if($action == "browse")
{
    $page->output_nav_tabs($sub_tabs, "browse");
    $query = $db->simple_select("socialgroups_documentation", "*", "documentation_type='hook'", array("order_by" => "file_name"));
    $table = new TABLE;
    $rows = $file_hooks = 0;
    $current_file = "";
    if($db->num_rows($query) == 0)
    {
        $table->construct_cell("There is no documentation for plugin hooks", array("colspan" => 6));
        $table->construct_row();
    }
    else
    {
        $table->construct_cell("There are currently " . $db->num_rows($query) . " hooks documented.", array("colspan" => 6));
        $table->construct_row();
    }
    $table->output("Plugin Hook Documentation");
    unset($table);

    $table = new TABLE;
    $table->construct_header("File Name");
    $table->construct_header("Hook Name");
    $table->construct_header("Arguments");
    $table->construct_header("Purpose");
    $table->construct_header("Last Updated");
    $table->construct_header("Manage Hook");
    $table->construct_row();
    while($documentation = $db->fetch_array($query))
    {
        if($current_file != $documentation['file_name'])
        {
            if($rows != 0)
            {
                $table->construct_cell("There are currently " . $file_hooks . " hooks in this file.", array("colspan" => 6));
                $table->construct_row();
                $table->output($current_file . " Hooks");
                unset($table);

                $table = new TABLE;
                $table->construct_header("File Name");
                $table->construct_header("Hook Name");
                $table->construct_header("Arguments");
                $table->construct_header("Purpose");
                $table->construct_header("Last Updated");
                $table->construct_header("Manage Hook");
                $table->construct_row();
            }
            $file_hooks = 0;
            $current_file = $documentation['file_name'];
        }
        ++$rows;
        ++$file_hooks;
        $edit_link = $baseurl . "&action=edit&hookid=" . $documentation['hookid'];
        $delete_link = $baseurl . "&action=delete&hookid=" . $documentation['hookid'];
        $table->construct_cell($documentation['file_name']);
        $table->construct_cell($documentation['hook_name']);
        $table->construct_cell($documentation['hook_argument']);
        $table->construct_cell(nl2br($documentation['purpose']));
        $table->construct_cell(my_date("relative", $documentation['last_updated']));
        $table->construct_cell("<a href='" . $edit_link . "'>Edit</a><br /><a href='" . $delete_link . "'>Delete</a>");
        $table->construct_row();
    }
    $table->output($documentation['file_name'] . " Hooks");
}

if($action == "edit" && $mybb->get_input("hookid"))
{
    $hookid = $mybb->get_input("hookid", MyBB::INPUT_INT);
    $query = $db->simple_select("socialgroups_documentation", "*", "hookid=" . $hookid);
    $hook = $db->fetch_array($query);
    if(!isset($hook['hookid']))
    {
        flash_message("Invalid Hook ID", "error");
        admin_redirect($baseurl);
    }
    if($mybb->request_method == "post")
    {
        $updated_hook = array(
            "file_name" => $db->escape_string($mybb->get_input("file_name")),
            "hook_name" => $db->escape_string($mybb->get_input("hook_name")),
            "hook_argument" => $db->escape_string($mybb->get_input("hook_argument")),
            "purpose" => $db->escape_string($mybb->get_input("purpose")),
            "documentation_type" => "hook",
            "last_updated" => time()
        );
        $updated_hook['file_name'] = $file_name_array[$updated_hook['file_name']];
        $db->update_query("socialgroups_documentation", $updated_hook, "hookid=" . $hookid);
        flash_message("The hook has been documented", "success");
        admin_redirect($baseurl);
    }
    $sub_tabs['edit'] = array(
        'title'         => "Edit Hook Documentation",
        'link'          => $baseurl . "&action=edit&hookid=" .$mybb->get_input("hookid", MyBB::INPUT_INT),
        'description'   => "Edit a Hook Documentation"
    );

    $form = new DefaultForm($baseurl . "&action=edit&hookid=" . $hookid, "post");
    $form_container = new FormContainer("Edit Hook Documentation");

    $page->output_nav_tabs($sub_tabs, "edit");


    $form_container->output_row("Hook Name", "The name of the hook", $form->generate_text_box("hook_name", $hook['hook_name']), "hook_name");
    $form_container->output_row("Hook Argument", "An argument passed to the hook", $form->generate_text_box("hook_argument", $hook['hook_argument']), "hook_argument");
    $form_container->output_row("File Name", "The name of the file", $form->generate_select_box("file_name", $file_name_array, array_search($hook['file_name'], $file_name_array)), "file_name");
    $form_container->output_row("Hook Purpose", "The use for the hook", $form->generate_text_area("purpose", $hook['purpose'], array("cols" => 70, "rows" => 10)), "purpose");
    $form_container->end();
    $form->output_submit_wrapper(array($form->generate_submit_button("Add Hook Documentation")));
    $form->end();
}

if($action == "delete" && $mybb->get_input("hookid"))
{
    $hookid = $mybb->get_input("hookid", MyBB::INPUT_INT);
    $sub_tabs['delete'] = array(
        'title'         => "Delete Hook Documentation",
        'link'          => $baseurl . "&action=delete&hookid=" .$mybb->get_input("hookid", MyBB::INPUT_INT),
        'description'   => "Delete Hook Documentation"
    );
    $page->output_nav_tabs($sub_tabs, "delete");
    if($mybb->request_method == "post")
    {
        if($mybb->input['confirm'] == 1)
        {
            $db->delete_query("socialgroups_documentation", "hookid=" . $hookid);
            flash_message("The documentation has been deleted.","success");
        }
        admin_redirect($baseurl);
    }
    else
    {
        $form = new Form($baseurl . "&action=delete&hookid=" . $hookid, "post");
        $form_container = new FormContainer("Confirm Deletion");
        $form_container->output_row("Are you sure", "This cannot be undone.", $form->generate_yes_no_radio("confirm", 0));
        $form_container->end();
        $form->output_submit_wrapper(array($form->generate_submit_button("Delete Documentation")));
        $form->end();
    }
}
$page->output_footer();
