<?php
/**
 * Socialgroups plugin created by Mark Janssen.
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
$page->output_header("Social Group Categories");
$baseurl = "index.php?module=socialgroups-category";
$sub_tabs['browse'] = array(
    "title" => "Browse",
    "link" => $baseurl,
    "description" => "Browse categories."
);

$sub_tabs['create'] = array(
    "title" => "Create",
    "link" => $baseurl . "&action=add",
    "description" => "Create categories."
);

$table = new TABLE;

switch($action)
{
    case "browse":
        $page->output_nav_tabs($sub_tabs, "browse");
        socialgroups_category_browse();
        break;
    case "edit":
        $sub_tabs['edit'] = array(
            "title" => "Edit",
            "link" => $baseurl . "&action=edit&cid=" . $mybb->get_input("cid"),
            "description" => "Editing category."
        );
        $page->output_nav_tabs($sub_tabs, "edit");
        socialgroups_category_edit($mybb->get_input("cid", MyBB::INPUT_INT));
        break;
    case "add":
        $page->output_nav_tabs($sub_tabs, "create");
        socialgroups_category_add();
        break;
    case "delete":
        $sub_tabs['delete'] = array(
            "title" => "Delete",
            "link" => $baseurl . "&action=delete&cid=" . $mybb->get_input("cid"),
            "description" => "Delete category."
        );
        $page->output_nav_tabs($sub_tabs, "delete");
        socialgroups_category_delete($mybb->get_input("cid", MyBB::INPUT_INT));
        break;
    case "merge":
        $sub_tabs['merge'] = array(
            "title" => "Merge",
            "link" => $baseurl . "&action=merge&cid=" . $mybb->get_input("cid"),
            "description" => "Merge category."
        );
        $page->output_nav_tabs($sub_tabs, "merge");
        socialgroups_category_merge($mybb->get_input("cid", MyBB::INPUT_INT));
        break;
    default:
        $plugins->run_hooks("admin_socialgroups_category_action");
        socialgroups_category_browse();
        break;
}

function socialgroups_category_browse()
{
    global $lang, $mybb, $db, $baseurl, $table;
    $table->construct_header("Name");
    $table->construct_header("Display Order");
    $table->construct_header("Groups");
    $table->construct_header("Staff Only");
    $table->construct_header("Manage", array("colspan"=> 2));
    $table->construct_row();
    $query = $db->simple_select("socialgroup_categories", "*", "", array("order_by" => "name", "order_dir" => "ASC"));
    while($category = $db->fetch_array($query))
    {
        $table->construct_cell(htmlspecialchars_uni($category['name']));
        $table->construct_cell($category['disporder']);
        $table->construct_cell($category['groups']);
        $yes = "No";
        if($category['staffonly'] == 1)
        {
            $yes = "Yes";
        }
        $table->construct_cell($yes);
        $table->construct_cell("<a href=\"" . $baseurl . "&action=edit&cid=" . $category['cid'] . "\">Edit</a><br />
        <a href=\"" . $baseurl . "&action=merge&cid=" . $category['cid'] . "\">Merge</a>");
        $table->construct_cell("<a href=\"" . $baseurl . "&action=delete&cid=" . $category['cid'] . "\">Delete</a>");
        $table->construct_row();
    }
    if($db->num_rows($query) == 0)
    {
        $table->construct_cell("There are no categories.", array("colspan" => 6));
    }
    $db->free_result($query);
    $table->output("Categories");
}

function socialgroups_category_edit(int $cid=0)
{
    global $mybb, $lang, $db, $baseurl, $plugins, $socialgroups;
    if($mybb->request_method == "post") // ACP automatically calls the post check
    {
        $updated_category = array(
            "name" => $db->escape_string($mybb->get_input("name")),
            "disporder" => $mybb->get_input("disporder", MyBB::INPUT_INT),
            "staffonly" => $mybb->get_input("staffonly", MyBB::INPUT_INT)
        );
        $updated_category = $plugins->run_hooks("admin_socialgroups_category_do_edit", $updated_category);
        $db->update_query("socialgroup_categories", $updated_category, "cid=$cid");
        $socialgroups->update_socialgroups_category_cache();
        log_admin_action(array("action" => "category_edit", "cid" => $cid, "name" => $updated_category['name']));
        flash_message("Category " . $updated_category['name'] . "has been updated.", "success");
        admin_redirect($baseurl);
    }
    $form = new Form("index.php?module=socialgroups-category&action=edit&cid=$cid", "post");
    $form_container = new FormContainer("Edit Category");
    $categoryquery = $db->simple_select("socialgroup_categories", "*", "cid=$cid");
    $category = $db->fetch_array($categoryquery);
    if(!isset($category['cid']))
    {
        flash_message("Invalid Category.", "error");
        admin_redirect($baseurl);
    }
    $form_container->output_row("Category Name", "Enter the name of the category.", $form->generate_text_box("name", htmlspecialchars_uni($category['name'])), "name");
    $form_container->output_row("Display Order", "The lower display order gets shown first.", $form->generate_numeric_field("disporder", $category['disporder']), "disporder");
    $form_container->output_row("Staff Only", "If yes, only staff can see this category", $form->generate_select_box("staffonly", array("1" => "Yes", "0" => "No"), array($category['staffonly'])), "staffonly");
    $plugins->run_hooks("admin_socialgroups_category_edit");
    $form_container->end();
    $form->output_submit_wrapper(array($form->generate_submit_button("Edit Category")));
    $form->end();
}

function socialgroups_category_delete(int $cid=0)
{
    global $mybb, $db, $plugins, $baseurl, $socialgroups;
    $query = $db->simple_select("socialgroup_categories", "*", "cid=$cid");
    if($db->num_rows($query) == 0)
    {
        flash_message("Invalid Category.", "error");
        admin_redirect($baseurl);
    }
    $category = $db->fetch_array($query);
    $plugins->run_hooks("admin_socialgroups_category_delete_start");
    if($mybb->request_method == "post")
    {
        if($mybb->get_input("confirm", MyBB::INPUT_INT) == 1)
        {
            // Delete all groups in the category
            $query = $db->simple_select("socialgroups", "gid", "cid=$cid");
            while($group = $db->fetch_array($query))
            {
                $socialgroups->socialgroupsdatahandler->delete_group($group['gid']);
            }
            $db->delete_query("socialgroup_categories", "cid=" . $cid);
            $socialgroups->update_socialgroups_category_cache();
            flash_message("The category " . stripcslashes($category['name']) . " has been deleted.", "success");
            admin_redirect($baseurl);
        }
        else if($mybb->get_input("confirm", MyBB::INPUT_INT) == 0)
        {
            admin_redirect($baseurl);
        }
        else
        {
            // This is an exception handler
            $form = new Form($baseurl . "&action=delete&cid=$cid", "post");
            $form_container = new FormContainer("Confirm");
            $form_container->output_row("Delete " . htmlspecialchars_uni($category['name']) . "?", "This action cannot be undone.  All groups in the category are deleted along with threads and posts.", $form->generate_yes_no_radio("confirm", 0), "confirm");
            $form_container->end();
            $form->output_submit_wrapper(array($form->generate_submit_button("Delete Category")));
            $form->end();
        }
    }
    else
    {
        $form = new Form($baseurl . "&action=delete&cid=$cid", "post");
        $form_container = new FormContainer("Delete Category");
        $form_container->output_row("Delete " . htmlspecialchars_uni($category['name']) . "?", "This action cannot be undone. All groups in the category are deleted along with threads and posts.", $form->generate_yes_no_radio("confirm", 0), "confirm");
        $form_container->end();
        $form->output_submit_wrapper(array($form->generate_submit_button("Delete Category")));
        $form->end();
    }
}

function socialgroups_category_add()
{
    global $mybb, $db, $plugins, $socialgroups, $baseurl;
    if($mybb->request_method == "post")
    {
        $new_category = array(
            "name" => $db->escape_string($mybb->get_input("name")),
            "disporder" => $mybb->get_input("disporder", MyBB::INPUT_INT),
            "staffonly" => $mybb->get_input("staffonly", MyBB::INPUT_INT)
        );
        $new_category = $plugins->run_hooks("admin_socialgroups_category_do_add", $new_category);
        $cid = $db->insert_query("socialgroup_categories", $new_category);
        $socialgroups->update_socialgroups_category_cache();
        log_admin_action(array("action" => "category_add", "cid" => $cid, "name" => $new_category['name']));
        flash_message("Category " . $new_category['name'] . "has been created.", "success");
        admin_redirect($baseurl);
    }
    else
    {
        $form = new Form($baseurl . "&action=add", "post");
        $form_container = new FormContainer("Create Category");
        $form_container->output_row("Category Name", "Enter the name of the category.", $form->generate_text_box("name", htmlspecialchars_uni($mybb->get_input('name'))));
        $form_container->output_row("Display Order", "The lower display order gets shown first.", $form->generate_numeric_field("disporder", $mybb->get_input('disporder')), "disporder");
        $form_container->output_row("Staff Only", "If yes, only staff can see this category", $form->generate_select_box("staffonly", array("1" => "Yes", "0" => "No"), array(0)), "staffonly");
        $plugins->run_hooks("admin_socialgroups_category_add");
        $form_container->end();
        $form->output_submit_wrapper(array($form->generate_submit_button("Create Category")));
        $form->end();
    }
}

function socialgroups_category_merge(int $oldcid=0)
{
    global $mybb, $db, $plugins, $baseurl, $socialgroups;
    $query = $db->simple_select("socialgroup_categories", "*", "cid=$oldcid");
    $category = $db->fetch_array($query);
    if(!isset($category['cid']))
    {
        flash_message("Invalid Category.", "error");
        admin_redirect($baseurl);
    }
    if($mybb->request_method == "post")
    {
        $newcid = $mybb->get_input("newcid", MyBB::INPUT_INT);
        $plugins->run_hooks("admin_socialgroups_category_merge");
        $newname = $db->escape_string($mybb->get_input("name"));
        if(!$newname)
        {
            $query = $db->simple_select("socialgroup_categories", "*", "cid=$newcid");
            $categoryinfo = $db->fetch_array($query);
            $newname = $categoryinfo['name'];
        }
        $db->write_query("UPDATE " . TABLE_PREFIX . "socialgroups SET cid=$newcid WHERE cid=$oldcid");
        $countquery = $db->simple_select("socialgroups", "COUNT(gid) as total", "cid=$newcid");
        $groupcount = $db->fetch_field($countquery, "total");
        $db->write_query("UPDATE " . TABLE_PREFIX . "socialgroup_categories SET groups=$groupcount, name='$newname' WHERE cid=$newcid");
        $db->delete_query("socialgroup_categories", "cid=$oldcid");
        $socialgroups->update_socialgroups_category_cache();
        flash_message("The categories have been merged.", "success");
        admin_redirect($baseurl);
    }
    else
    {
        $form = new DefaultForm($baseurl . "&action=merge&cid=$oldcid", "post");
        $form_container = new FormContainer("Merge Categories");
        $categoryquery = $db->simple_select("socialgroup_categories", "*", "cid!=$oldcid");
        $categoryarray = array();
        while($categoryinfo = $db->fetch_array($categoryquery))
        {
            $categoryarray[$categoryinfo['cid']] = htmlspecialchars_uni($categoryinfo['name']);
        }
        $form_container->output_row("Merge with which category?", "This is the category that will be kept.", $form->generate_select_box("newcid", $categoryarray, array($mybb->get_input("newcid"))));
        $form_container->output_row("Name", "The name of the merged categories.  Leave blank to use the name from selection.", $form->generate_text_box("name", ""));
        $form_container->end();
        $form->output_submit_wrapper(array($form->generate_submit_button("Merge Categories")));
        $form->end();
    }
}

$page->output_footer();