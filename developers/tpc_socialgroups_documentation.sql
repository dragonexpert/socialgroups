-- phpMyAdmin SQL Dump
-- version 5.1.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Aug 02, 2021 at 01:45 PM
-- Server version: 5.7.32-35-log
-- PHP Version: 7.4.21

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `teamdime_kk`
--

-- --------------------------------------------------------

--
-- Table structure for table `tpc_socialgroups_documentation`
--

CREATE TABLE `tpc_socialgroups_documentation` (
  `hookid` int(11) NOT NULL,
  `file_name` text NOT NULL,
  `hook_name` text NOT NULL,
  `hook_argument` text NOT NULL,
  `purpose` text NOT NULL,
  `documentation_type` varchar(10) NOT NULL DEFAULT 'hook',
  `last_updated` int(10) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `tpc_socialgroups_documentation`
--

INSERT INTO `tpc_socialgroups_documentation` (`hookid`, `file_name`, `hook_name`, `hook_argument`, `purpose`, `documentation_type`, `last_updated`) VALUES
(1, 'groupthread.php', 'groupthread_start', '', 'Add additional actions for groupthread once it has been determined the user is allowed to view the thread.', 'hook', 1616803908),
(2, 'groupthread.php', 'groupthread_post_post', '', 'This lets you do any interactions with a post.  Globalize $post if you want to add fields to it.', 'hook', 1616804118),
(3, 'groupthread.php', 'groupthread_quickreply', '', 'Prevent the reply box from appearing. Add more features to the reply box.', 'hook', 1616804106),
(4, 'groupthread.php', 'groupthread_end', '', 'Add additional content and features to groupthread.php', 'hook', 1616804329),
(5, 'groupcp.php', 'groupcp_manage_permissions_commit', '$new_permissions', 'Add additional permissions for group members', 'hook', 1616804491),
(6, 'groupcp.php', 'groupcp_manage_permissions', '', 'Add more form fields to give more control over group members', 'hook', 1616804528),
(7, 'groupcp.php', 'groupcp_announcement', '', 'Add more fields to $annoucement.  Change $parser_options to do things like enable HTML.', 'hook', 1616804574),
(8, 'showgroup.php', 'showgroup_inline_moderation', '', 'Handle any custom moderator actions on showgroup.php', 'hook', 1616804639),
(9, 'showgroup.php', 'showgroup_start', '', 'Perform more preliminary operations on a group.', 'hook', 1616804687),
(10, 'showgroup.php', 'showgroup_announcement', '', 'Add more fields to $announcement.  Change $parser_options.', 'hook', 1616804717),
(11, 'showgroup.php', 'showgroup_thread', '', 'Perform operations on a specific thread.  Globalize $thread to add, edit, or remove data.', 'hook', 1616804800),
(12, 'showgroup.php', 'showgroup_end', '', 'Add additional content on a showgroup.php', 'hook', 1616804834),
(13, 'admin/modules/socialgroups/category.php', 'admin_socialgroups_category_action', '', 'Add custom actions to category management', 'hook', 1616805294),
(14, 'admin/modules/socialgroups/category.php', 'admin_socialgroups_category_do_edit', '$updated_category', 'Alter additional fields for a category before the update query is ran.', 'hook', 1617023066),
(15, 'admin/modules/socialgroups/category.php', 'admin_socialgroups_category_edit', '', 'Add more fields to a category.  Must globalize $form, and $form_container.', 'hook', 1616805419),
(16, 'admin/modules/socialgroups/category.php', 'admin_socialgroups_category_delete_start', '', 'Perform any action before the category is deleted. Could be used to backup a category.  If you globalize $category, you obtain all information about the category from the socialgroup_categories table.', 'hook', 1616805556),
(17, 'admin/modules/socialgroups/category.php', 'admin_socialgroups_category_do_add', '$new_category', 'Add more fields to the $new_category array before insertion.', 'hook', 1617023031),
(18, 'admin/modules/socialgroups/category.php', 'admin_socialgroups_category_add', '', 'Add additional form fields when adding a category.  Must globalize $form and $form_container.', 'hook', 1616805667),
(19, 'admin/modules/socialgroups/category.php', 'admin_socialgroups_category_merge', '', 'Perform any operations on a category before a category merge takes place.', 'hook', 1616805740),
(20, 'admin/modules/socialgroups/groups.php', 'admin_socialgroups_group_action', '', 'Custom actions for the admin group page.', 'hook', 1616806930),
(21, 'admin/modules/socialgroups/module_meta.php', 'admin_socialgroups_menu', '$sub_menu', 'Add a module under Socialgroups tab.', 'hook', 1616807000),
(22, 'admin/modules/socialgroups/module_meta.php', 'admin_socialgroups_action_handler', '$actions', 'Add an action handler for a module in the Socialgroups tab.', 'hook', 1616807038),
(23, 'admin/modules/socialgroups/module_meta.php', 'admin_socialgroups_permissions', '$admin_permissions', 'Add an admin permission for a Socialgroups module.', 'hook', 1616807083),
(24, 'admin/modules/socialgroups/restore.php', 'socialgroups_admin_restore_custom_action', '', 'Add a custom action handler for managing soft deleted content.  Would be a good use case if creating a plugin to backup deleted categories and groups.', 'hook', 1616807189),
(25, 'inc/plugins/socialgroups/classes/socialgroups.php', 'class_socialgroups_constructor', '', 'Add any additional constructor routines.  Note that classes are already automatically loaded by this point so you can call their methods.', 'hook', 1616807337),
(26, 'inc/plugins/socialgroups/classes/socialgroups.php', 'class_socialgroups_error', '', 'Add any custom error handling.', 'hook', 1616807368),
(27, 'inc/plugins/socialgroups/classes/socialgroups.php', 'class_socialgroups_breadcrumb_link', '', 'Create SEO links for any files that are not part of Socialgroups core.  Note that you can globalize the $find array so it strips out the same thing.', 'hook', 1616807467),
(28, 'inc/plugins/socialgroups/classes/socialgroups.php', 'class_socialgroups_load_group', '$groups', 'Cache and return additional information about a group.', 'hook', 1616807528),
(29, 'inc/plugins/socialgroups/classes/socialgroups.php', 'class_socialgroups_load_group_no_cache', '$group', 'This is reached when a group cannot be found in the cache.  Add additional information or attempt to load a group with this.', 'hook', 1616807671),
(30, 'inc/plugins/socialgroups/classes/socialgroups.php', 'class_socialgroups_load_permissions', '$this', 'Alter default permissions for a group.', 'hook', 1616807746),
(31, 'inc/plugins/socialgroups/classes/socialgroups.php', 'class_socialgroups_list_groups', '$this', 'Add any additional fields to the array returned and cached.', 'hook', 1616807820),
(32, 'inc/plugins/socialgroups/classes/socialgroups.php', 'class_socialgroups_render_group', '$subkey', 'Handle custom fields a group has.', 'hook', 1616807868),
(33, 'inc/plugins/socialgroups/classes/socialgroups.php', 'class_socialgroups_get_viewable_categories', '$category', 'Add, edit, or delete information about a category that will be returned.', 'hook', 1616807931),
(34, 'inc/plugins/socialgroups/classes/socialgroups.php', 'class_socialgroups_destructor', '', 'Perform any shutdown tasks when the main class gets destroyed or the script reaches the end of execution.', 'hook', 1616807980),
(35, 'inc/plugins/socialgroups/classes/socialgroupsdatahandler.php', 'class_socialgroupsdatahandler_save_fields', '', 'Globalize $fieldtypes in order to force a parameter about a group to be read as a specific data type.  Valid types are int, text, and bin with text being default.', 'hook', 1616808114),
(36, 'inc/plugins/socialgroups/classes/socialgroupsdatahandler.php', 'class_socialgroupsdatahandler_update_group', '', 'This is called after a group is updated, but before the cache is updated in case you want to perform any operations on the socialgroups table.', 'hook', 1616808162),
(37, 'inc/plugins/socialgroups/classes/socialgroupsdatahandler.php', 'class_socialgroupsdatahandler_insert_group', '', 'This is called after a group is inserted in case you want to perform any additional operations such as taking away points if you are using Newpoints.', 'hook', 1616808228),
(38, 'inc/plugins/socialgroups/classes/socialgroupsdatahandler.php', 'socialgroupsdatahandler_delete_group', '', 'This is called before a group gets deleted.  Globalize $data in order to get information about the group that will be deleted.  This is mostly used to backup a group.  It is also used if you are creating additional tables that involve socialgroups and use a specific group.', 'hook', 1616808316),
(39, 'inc/plugins/socialgroups/classes/socialgroupsreports.php', 'class_socialgroups_reports_can_report', '$this', 'Make any extra checks regarding if a user is allowed to report a post.', 'hook', 1616808364),
(40, 'inc/plugins/socialgroups/classes/socialgroupsreports.php', 'class_socialgroups_reports_report_post', '$reportinfo', 'Useful if you want to add any additional fields to the socialgroup_reported_posts table.  Can also be used to do any actions against a post when it is reported such as unapproving it.', 'hook', 1617020327),
(41, 'inc/plugins/socialgroups/classes/socialgroupsreports.php', 'class_socialgroups_reports_handle_report', '$updated_report', 'Perform any additional tasks when a report is handled such as giving your moderator points.', 'hook', 1616808493),
(42, 'inc/plugins/socialgroups/classes/socialgroupsthreadhandler.php', 'class_socialgroupsthreadhandler_insert_thread', '', 'Perform any operations before the thread is inserted.  Globalize $new_thread if you want to add extra fields.', 'hook', 1616808719),
(43, 'inc/plugins/socialgroups/classes/socialgroupsthreadhandler.php', 'class_socialgroupsthreadhandler_insert_post', '', 'Perform operations before a new post is inserted.  Globalize $new_post to add any additional fields.', 'hook', 1616808778),
(44, 'inc/plugins/socialgroups/classes/socialgroupsthreadhandler.php', 'class_socialgroupsthreadhandler_update_post', '', 'Perform any operations before a post is updated.  Globalize $updated_post if you want to handle any fields.', 'hook', 1616808840),
(45, 'inc/plugins/socialgroups/classes/socialgroupsthreadhandler.php', 'class_socialgroupsthreadhandler_delete_post', '', 'Called before a post is deleted.  Globalize $thread to get any information about the thread or post.', 'hook', 1616808940),
(46, 'inc/plugins/socialgroups/classes/socialgroupsthreadhandler.php', 'class_socialgroupsthreadhandler_restore_thread', '', 'Perform any additional operations after a thread is restored before the cache is updated.', 'hook', 1616808980),
(47, 'inc/plugins/socialgroups/classes/socialgroupsthreadhandler.php', 'class_socialgroupsthreadhandler_restore_post', '', 'Perform any additional operations before the cache is updated.', 'hook', 1616809008),
(48, 'inc/plugins/socialgroups/classes/socialgroupsthreadhandler.php', 'class_socialgroupsthreadhandler_delete_thread', '', 'Perform any operations before a thread is deleted.  Globalize $thread if you want information about the thread.', 'hook', 1616809049),
(49, 'inc/plugins/socialgroups/classes/socialgroupsthreadhandler.php', 'class_socialgroupsthreadhandler_load_thread', '$thread', 'Handle any additional fields for a thread before it is cached and returned.', 'hook', 1616809130),
(50, 'inc/plugins/socialgroups/classes/socialgroupsthreadhandler.php', 'class_socialgroupsthreadhandler_load_post', '$post', 'Handle any extra fields before a post is cached and returned.', 'hook', 1616809171),
(51, 'inc/plugins/socialgroups/classes/socialgroupsuserhandler.php', 'class_socialgroupsuserhandler_can_join', '$status', 'Determine if a user can join a group.  Return either true or false.  Useful if you want it to cost points to join a group.', 'hook', 1616809234),
(52, 'inc/plugins/socialgroups/classes/socialgroupsuserhandler.php', 'class_socialgroupsuserhandler_join_group', '', 'Perform any additional operations after a member joins a group such as taking away points.', 'hook', 1616809275),
(53, 'inc/plugins/socialgroups/classes/socialgroupsuserhandler.php', 'class_socialgroupsuserhandler_remove_member', '', 'Called after a member is removed from a group.  Perform additional operations on a member.', 'hook', 1616809366),
(54, 'inc/plugins/socialgroups/classes/socialgroupsuserhandler.php', 'class_socialgroupsuserhandler_add_leader', '', 'Perform additional operations after a user has been added as a leader.  Globalize $lid if you need the id to query the socialgroup_leaders table.', 'hook', 1616809519),
(55, 'inc/plugins/socialgroups/classes/socialgroupsuserhandler.php', 'class_socialgroupsuserhandler_remove_leader', '', 'Called after a leader of a group is deleted.  Perform additional operations on the leader.  Globalize $gorupinfo if you need information about the group.', 'hook', 1616809625),
(56, 'groups.php', 'groups_start', '', 'Perform any custom actions on the page.', 'hook', 1617021458),
(57, 'groups.php', 'groups_end', '', 'Perform any additional operations before output is sent.', 'hook', 1617021480);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tpc_socialgroups_documentation`
--
ALTER TABLE `tpc_socialgroups_documentation`
  ADD PRIMARY KEY (`hookid`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tpc_socialgroups_documentation`
--
ALTER TABLE `tpc_socialgroups_documentation`
  MODIFY `hookid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
