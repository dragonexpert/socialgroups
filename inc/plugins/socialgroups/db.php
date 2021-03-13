<?php
/**
 * Socialgroups plugin created by Mark Janssen.
 * This is not a free plugin
 * This file handles all database changes.
 */

if(!defined("IN_MYBB"))
{
    die("Direct access not allowed.");
}
function socialgroups_create_tables()
{
    global $db, $cache, $mybb;
    $charset = $db->build_create_table_collation();

    $db->query("CREATE TABLE IF NOT EXISTS " . TABLE_PREFIX . "socialgroups (
    gid INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    cid INT NOT NULL DEFAULT 1,
    name TEXT,
    approved INT NOT NULL DEFAULT 1,
    description TEXT,
    logo TEXT,
    private INT NOT NULL DEFAULT 0,
    staffonly INT NOT NULL DEFAULT 0,
    inviteonly INT NOT NULL DEFAULT 0,
    jointype INT NOT NULL DEFAULT 0,
    uid INT NOT NULL DEFAULT 1,
    threads INT NOT NULL DEFAULT 0,
    posts INT NOT NULL DEFAULT 0,
    locked INT NOT NULL DEFAULT 0
    ) ENGINE = Innodb $charset");

    $db->query("CREATE TABLE IF NOT EXISTS " . TABLE_PREFIX . "socialgroup_categories (
    cid INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    disporder INT NOT NULL DEFAULT 1,
    name TEXT,
    groups INT NOT NULL DEFAULT 0,
    staffonly INT NOT NULL DEFAULT 0
    ) ENGINE = Innodb $charset");

    $db->query("CREATE TABLE IF NOT EXISTS " . TABLE_PREFIX . "socialgroup_members (
    mid INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    gid INT NOT NULL DEFAULT 1,
    uid INT NOT NULL DEFAULT 1,
    dateline BIGINT NOT NULL DEFAULT 0
    ) ENGINE = Innodb $charset");

    $db->query("CREATE TABLE IF NOT EXISTS " . TABLE_PREFIX . "socialgroup_member_permissions (
    pid INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    gid INT NOT NULL DEFAULT 1,
    postthreads INT NOT NULL DEFAULT 1,
    postreplies INT NOT NULL DEFAULT 1,
    inviteusers INT NOT NULL DEFAULT 1,
    deleteposts INT NOT NULL DEFAULT 0
    ) ENGINE = Innodb $charset");

    $db->query("CREATE TABLE IF NOT EXISTS " . TABLE_PREFIX . "socialgroup_moderators (
    mid INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    uid INT NOT NULL DEFAULT 1
    ) ENGINE = Innodb $charset");


    $db->query("CREATE TABLE IF NOT EXISTS " . TABLE_PREFIX . "socialgroup_leaders (
    lid INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    uid INT NOT NULL DEFAULT 1,
    gid INT NOT NULL DEFAULT 1
    ) ENGINE = Innodb $charset");

    $db->query("CREATE TABLE IF NOT EXISTS " . TABLE_PREFIX . "socialgroup_invites (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    gid INT NOT NULL DEFAULT 1,
    touid INT NOT NULL DEFAULT 1,
    fromuid INT NOT NULL DEFAULT 1
    ) ENGINE = Innodb $charset");

    $db->query("CREATE TABLE IF NOT EXISTS " . TABLE_PREFIX . "socialgroup_announcements (
    aid INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    gid INT NOT NULL DEFAULT 1,
    uid INT NOT NULL DEFAULT 1,
    dateline BIGINT NOT NULL DEFAULT 0,
    subject TEXT,
    message TEXT,
    active INT NOT NULL DEFAULT 1
    ) ENGINE = Innodb $charset");

    $db->query("CREATE TABLE IF NOT EXISTS " . TABLE_PREFIX . "socialgroup_threads (
    tid INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    gid INT NOT NULL DEFAULT 1,
    firstpost INT NOT NULL DEFAULT 0,
    uid INT NOT NULL DEFAULT 1,
    subject TEXT,
    dateline BIGINT NOT NULL DEFAULT 0,
    sticky INT NOT NULL DEFAULT 0,
    visible INT NOT NULL DEFAULT 1,
    closed INT NOT NULL DEFAULT 0,
    replies INT NOT NULL DEFAULT 0,
    views INT NOT NULL DEFAULT 0,
    lastposttime BIGINT NOT NULL DEFAULT 0,
    lastpostuid INT NOT NULL DEFAULT 1,
    lastpostusername TEXT
    ) ENGINE = Innodb$charset");

    $db->query("CREATE TABLE IF NOT EXISTS " . TABLE_PREFIX . "socialgroup_posts (
    pid INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tid INT NOT NULL DEFAULT 1,
    gid INT NOT NULL DEFAULT 1,
    uid INT NOT NULL DEFAULT 1,
    dateline BIGINT NOT NULL DEFAULT 0,
    lastedit BIGINT NOT NULL DEFAULT 0,
    lasteditby INT NOT NULL DEFAULT 0,
    editcount INT NOT NULL DEFAULT 0,
    reported INT NOT NULL DEFAULT 0,
    visible INT NOT NULL DEFAULT 1,
    ipaddress VARBINARY(16) NOT NULL DEFAULT '',
    message TEXT
    ) ENGINE = Innodb $charset");

    $db->query("CREATE TABLE IF NOT EXISTS " . TABLE_PREFIX . "socialgroup_reported_posts (
    rid INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    pid INT NOT NULL DEFAULT 0,
    tid INT NOT NULL DEFAULT 0,
    uid INT NOT NULL DEFAULT 0,
    dateline BIGINT NOT NULL DEFAULT 0,
    reason TEXT,
    status TINYINT(1) NOT NULL DEFAULT 0,
    handledby INT NOT NULL DEFAULT 0,
    handledate BIGINT NOT NULL DEFAULT 0
    ) ENGINE = Innodb $charset");

    $db->query("CREATE TABLE IF NOT EXISTS " . TABLE_PREFIX . "socialgroup_join_requests (
    rid INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    gid INT NOT NULL DEFAULT 0,
    uid INT NOT NULL DEFAULT 0,
    dateline BIGINT NOT NULL DEFAULT 0,
    approved INT NOT NULL DEFAULT 0
    ) ENGINE = Innodb $charset");

    // Create a category
    $new_category = array(
        "disporder" => 1,
        "name" => "General Category",
        "groups" => 0,
        "staffonly" => 0,
    );

    $cid = $db->insert_query("socialgroup_categories", $new_category);

    // Now create a group.
    $new_group = array(
        "cid" => $cid,
        "name" => "General Group",
        "approved" => 1,
        "description" => "This is a general group.",
        "logo" => "",
        "private" => 0,
        "staffonly" => 0,
        "locked" => 0,
        "uid" => (int) $mybb->user['uid']
    );

    $db->insert_query("socialgroups", $new_group);

    // Usergroup permissions
    $db->write_query("ALTER TABLE " . TABLE_PREFIX . "usergroups ADD maxsocialgroups_create INT UNSIGNED DEFAULT 5, ADD cancreatesocialgroups INT UNSIGNED DEFAULT 1, ADD socialgroups_auto_approve INT UNSIGNED DEFAULT 0");
    $db->write_query("UPDATE " . TABLE_PREFIX . "usergroups SET cancreatesocialgroups=0 WHERE isbannedgroup=1");
    $db->write_query("UPDATE " . TABLE_PREFIX . "usergroups SET maxsocialgroups_create=0 WHERE cancp=1");
    $db->write_query("UPDATE " . TABLE_PREFIX . "usergroups SET socialgroups_auto_approve=1 WHERE cancp=1 OR canmodcp=1");
    $cache->update_usergroups();
}

function socialgroups_drop_tables()
{
    global $db, $cache;
    $tables = array("socialgroups", "socialgroup_categories", "socialgroup_members", "socialgroup_member_permissions", "socialgroup_leaders",
        "socialgroup_moderators", "socialgroup_invites", "socialgroup_announcements", "socialgroup_threads", "socialgroup_posts");
    foreach($tables as $table)
    {
        if($db->table_exists($table))
        {
            $db->drop_table($table);
        }
    }
    // Now permissions
    $columns = array("cancreatesocialgroups","maxsocialgroups_create","socialgroups_auto_approve");
    foreach($columns as $column)
    {
        $db->write_query("ALTER TABLE " . TABLE_PREFIX . "usergroups DROP " . $column);
    }
    $cache->update_usergroups();
}
