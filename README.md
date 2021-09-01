# LN-backup-sync
Backup LN reading states, sync to/from e-reader

Setup
=====

edit config.php

add the user/pass of wlnupdates.com and webnovel.com

modify the local folder of Dropbox, including Apps\\Books\\.Moon+\\Cache subfolder for position sync from Moon+Reader

Description
===========

In bold, end-user endpoints.

config.php settings of the application

.cookies.txt cookies for the HTTP requests (internal files)

\*/\*.json, \*/\*.htm result of HTTP requests (cache for debug)

wlnupdates.php class to support wlnupdates.com

webnovel.php class to support webnovel.com

royalroad.php class to support royalroad.com

**retr.php** retrieve wlnupdates data (& possibly others in the future)

**dropbox.php** List new position sync and update wlnupdates and webnovel

**dropbox2.php** List new novels and add them to wlnupdates and webnovel

**webnovel_news.php** List novels from webnovel with new chapters (depends on retr)

**webnovel_data.php** List data (counts) from webnovel

**update_list.php** move qidian's novels into QIDIAN or QIDIAN original list on wlnupdates, update details on wln from data of wn

[wlnupdates/\_books.json](wlnupdates/_books.json) library json data from wlnupdates

[watches2.htm](watches2.htm) raw table of wlnupdates data

[webnovel/\_books.json](webnovel/_books.json) library json data from webnovel (merged, reordered)

webnovel/\_books2.json) library json data from webnovel (merged, order kept)

[webnovel/\_order.json](webnovel/_order.json) library json data of the order from webnovel

[webnovel/\_history.json](webnovel/_history.json) history json data of the order from webnovel

[webnovel/\_collection.json](webnovel/_collection.json) collection (reading lists) json data of the order from webnovel

[library.htm](library.htm) raw table of webnovel data (sorted)

[library2.htm](library2.htm) raw table of webnovel data (identical to website, sorted by last add/update)

[webnovel_data.htm](webnovel_data.htm) table of webnovel data counts (include lists, history*2, collection list, all collections)

[royalroad/\_books.json](royalroad/_books.json) library json data from royalroad

[wn\_diff.json](wn_diff.json) json data of number of privilege chapters for watched series (= the difference between webnovel reading position and wlnupdate reading position)

[correspondances.json](correspondances.json) data of correspondances between webnovel and wlnupdate (= novel id)

