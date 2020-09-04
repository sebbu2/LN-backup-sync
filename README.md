# LN-backup-sync
Backup LN reading states, sync to/from e-reader

Setup
=====

edit config.php and add the user/pass of wlnupdates.com and webnovel.com

edit dropbox.php and modify the local folder of Dropbox, including Apps\Books\\.Moon+\Cache subfolder for position sync from Moon+Reader

Description
===========

In bold, end-user endpoints.

config.php settings of the application

.cookies.txt cookies for the HTTP requests (internal files)

\*/\*.json, \*/\*.htm result of HTTP requests (cache for debug)

wlnupdates.php class to support wlnupdates.com

webnovel.php class to support webnovel.com

**retr.php** retrieve wlnupdates data (& possibly others in the future)

**dropbox.php** List new position sync and update wlnupdates and webnovel

**dropbox2.php** List new novels and add them to wlnupdates and webnovel

**webnovel_news.php** List novels from webnovel with new chapters (depends on retr)

watches.inc.php php data from wlnupdates

watches2.htm raw table of wlnupdates data

webnovel/\_books.json json data from webnovel (merged)

library.htm raw table of webnovel data

