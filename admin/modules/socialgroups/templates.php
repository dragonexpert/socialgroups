<?php
/**
 * Socialgroups plugin created by Mark Janssen.
 * This is not a free plugin.
 */

if(!defined("IN_MYBB"))
{
    die("Direct access not allowed.");
}
if($mybb->input['action'] == "download_templates")
{
    $file = "templates.json";
    if(file_exists($file))
    {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($file).'"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        flash_message("Downloaded templates successfully.", "success");
        admin_redirect("index.php?module=socialgroups-templates");
        exit;
    }
    else
    {
        flash_message("Templates file is not ready.", "error");
        admin_redirect("index.php?module=socialgroups-templates");
        exit;
    }
}
$page->output_header("Social Groups Template Tool");

$sub_tabs['main'] = array(
    "title" => "Main",
    "link" => "index.php?module=socialgroups-templates",
    "description" => "Main Menu."
);

$sub_tabs['update_templates'] = array(
    "title" => "Update Templates",
    "link" => "index.php?module=socialgroups-templates&action=update_templates",
    "description" => "Update Templates to JSON data."
);

$sub_tabs['export_templates'] = array(
    "title" => "Export Templates",
    "link" => "index.php?module=socialgroups-templates&action=export_templates",
    "description" => "Exports templates to JSON data."
);

$sub_tabs['download_templates'] = array(
    "title" => "Download Templates",
    "link" => "index.php?module=socialgroups-templates&action=download_templates",
    "description" => "Download the templates.json file."
);



if($mybb->input['action'] == "update_templates")
{
    $page->output_nav_tabs($sub_tabs, "update_templates");

    $template_json = json_decode(file_get_contents(MYBB_ROOT . "/inc/plugins/socialgroups/templates.json", true), true);

    // Perform a check in case JSON is not valid.
    if(!is_null($template_json))
    {
        foreach ($template_json as $my_template)
        {
            $update_template = array(
                "template" => $db->escape_string($my_template['template']),
                "dateline" => TIME_NOW
            );
            $title = $db->escape_string($my_template['title']);
            $db->update_query("templates", $update_template, "title='" . $title . "'");
        }
        flash_message("Templates updated.", "success");
        admin_redirect("index.php?module=socialgroups-templates");
    }
    else
    {
        flash_message(json_last_error_msg(), "error");
        admin_redirect("index.php?module=socialgroups-templates");
    }
}
if($mybb->input['action'] == "export_templates")
{
    $page->output_nav_tabs($sub_tabs, "export_templates");

    if($mybb->request_method == "post")
    {
        $tid = $mybb->get_input("tid", MyBB::INPUT_INT);
        $themequery = $db->simple_select("themes", "*", "tid=" . $tid);
        $theme = $db->fetch_array($themequery);
        $db->free_result($themequery);
        $theme['properties'] = my_unserialize($theme['properties']);
        $sid = $theme['properties']['templateset'];

        // Now we have the sid of the templates so we can retrieve them.
        $template_query = $db->simple_select("templates", "*", "sid = " . $sid . " AND title LIKE 'socialgroups_%'");
        $regular_array = array();
        while($template = $db->fetch_array($template_query))
        {
            $regular_array[$template['title']] = array(
                "title" => $template['title'],
                "template" => $template['template']
            );
        }
        $json_array = json_encode($regular_array);
        if($json_array !== false)
        {
            $fstream = fopen("templates.json", "w+", false);
            fwrite($fstream, $json_array);
            fclose($fstream);
            flash_message("Exported templates successfully.", "success");
            admin_redirect("index.php?module=socialgroups-templates");
        }
        else
        {
            // The json encode failed.
            flash_message("There was an error exporting to JSON", "error");
            admin_redirect("index.php?module=socialgroups-templates");
        }
    }
    else
    {
        // Figure out what theme they want to export the templates for.
        $query = $db->simple_select("themes", "*");
        $themearray = array();
        while($theme = $db->fetch_array($query))
        {
            $themearray[$theme['tid']] = $theme['name'];
        }
        $db->free_result($query);
        $form = new DefaultForm("index.php?module=socialgroups-templates&action=export_templates", "post");
        $form_container = new FormContainer("export_templates");
        $form_container->output_row("Theme ", "Which theme would you like to export?", $form->generate_select_box("tid", $themearray, 1), "tid");
        $form_container->end();
        $form->output_submit_wrapper(array($form->generate_submit_button("Export Templates")));
        $form->end();
    }
}

if($mybb->request_method== "post" && $mybb->get_input("action") == "mass_edit")
{
    // Do the actual updating
    $skip_keys = array("action", "module", "tid", "my_post_key", "do");
    foreach($mybb->input as $key => $value)
    {
        if(!in_array($key, $skip_keys))
        {
            $update_template['template'] = $db->escape_string($value);
            $tid = str_replace("tid", "", $key);
            $db->update_query("templates", $update_template, "tid=" . $tid);
        }
    }
    log_admin_action();
    flash_message("Templates have been updated.", "success");
    admin_redirect("index.php?module=socialgroups-templates&tid=" . $mybb->get_input("tid"));
}

if(!$mybb->input['action'])
{
    $page->output_nav_tabs($sub_tabs, "main");

    if($mybb->get_input("tid"))
    {
        $tid = $mybb->get_input("tid", MyBB::INPUT_INT);
        $themequery = $db->simple_select("themes", "*", "tid=" . $tid);
        $theme = $db->fetch_array($themequery);
        $db->free_result($themequery);
        $theme['properties'] = my_unserialize($theme['properties']);
        $sid = $theme['properties']['templateset'];

        // Now we have the sid of the templates so we can retrieve them.
        $template_query = $db->simple_select("templates", "*", "sid = " . $sid . " AND title LIKE 'socialgroups_%'");
        $form = new DefaultForm("index.php?module=socialgroups-templates&action=mass_edit&tid=" . $tid, "post");
        $form_container = new FormContainer("edit_templates");
        while($template = $db->fetch_array($template_query))
        {
            $form_container->output_row(htmlspecialchars_uni($template['title']) . " Template", "", $form->generate_text_area("tid" . $template['tid'], $template['template']), "tid" . $template['tid']);
        }
        $db->free_result($template_query);
        $form_container->end();
        $form->output_submit_wrapper(array($form->generate_submit_button("Edit Templates")));
        $form->end();
    }
    else
    {
        $query = $db->simple_select("themes", "*");
        $themearray = array();
        while ($theme = $db->fetch_array($query))
        {
            $themearray[$theme['tid']] = $theme['name'];
        }
        $db->free_result($query);
        $form = new DefaultForm("index.php?module=socialgroups-templates", "post");
        $form_container = new FormContainer("Template Set");
        $form_container->output_row("Theme ", "Which theme would you like to edit?", $form->generate_select_box("tid", $themearray, 1), "tid");
        $form_container->end();
        $form->output_submit_wrapper(array($form->generate_submit_button("Edit Templates")));
        $form->end();
    }
}
$page->output_footer();
