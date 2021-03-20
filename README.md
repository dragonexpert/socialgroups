# socialgroups
An extensive plugin for the MyBB Forum Software.

# installation
I cannot make any guarantees the Github code is going to work flawlessly for installs. You are able to create an account for testing purpose here: https://teamdimensional.net/testforums/index.php

1) Upload all PHP files and "inc" and "admin" folders to your forum root directory.  The directory "templates" is strictly for use on Github to make it easier to track template changes.  The screenshots directory is for storing screenshots of the plugin.
2) Activate from the Admin CP
3) Configure Settings
4) Adjust Templates as desired.  You can add Group Posts: {$post['socialgroups_posts']}<br />
Group Threads: {$post['socialgroups_threads']} to your postbit_author_user template if you want to show how many group posts and group threads a user has in posts.
5) Create one or more group categories.
6) Create groups if desired.
7) If you enable SEO Urls for Social Groups, add the Rewrite Rules from the htaccess.txt file to your .htaccess file.  

Please keep in mind this is a beta release.  There may be bugs and there are features that are not yet implemented.
