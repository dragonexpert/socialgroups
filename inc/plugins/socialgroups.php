<?php
/**
 * Socialgroups plugin created by Mark Janssen.
 * This is not a free plugin.
 */
if(!defined("IN_MYBB"))
{
    die("Direct access not allowed.");
}
require_once "socialgroups/hooks.php";


function socialgroups_info()
{
    return array(
        "name" => "Social Groups",
        "description" => "Allows users to create their own groups.",
        "author" => "Mark Janssen",
        "version" => "1.8.0",
        "codename" => "socialgroups"
    );
}


function socialgroups_install()
{
    require_once "socialgroups/db.php";
    socialgroups_create_tables();
    require_once "socialgroups/settings.php";
    socialgroups_insert_settings();
}

function socialgroups_is_installed()
{
    global $db;
    return $db->table_exists("socialgroups");
}

function socialgroups_activate()
{
    require_once "socialgroups/templates.php";
    socialgroups_insert_templates();
}

function socialgroups_deactivate()
{
    require_once "socialgroups/templates.php";
    socialgroups_delete_templates();
}

function socialgroups_uninstall()
{
    require_once "socialgroups/db.php";
    socialgroups_drop_tables();
    require_once "socialgroups/settings.php";
    socialgroups_delete_settings();
}

function update_socialgroups()
{
    require_once "socialgroups/classes/socialgroups.php";
    $socialgroups = new socialgroups(0, false, false, false, false);
    $socialgroups->update_cache();
}
