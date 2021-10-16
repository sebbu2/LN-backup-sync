# LN-backup-sync
Backup LN reading states, sync to/from e-reader

{:toc}

Setup
=====

edit config.php

add the user/pass of the website sources

modify the local folder of cloud sources, including path, for syncing (might be replaced with user/pass or token)

Description
===========

Allows you to open your ebooks at the first unread chapter (when filenames contains chapters range), while syncing the list of books and positions between websites/softwares, and keeping a local copy of the data.

*italic is ToBeDone, some might require an additional app*

**bold is refused feature**

Features :

* Retrieve list of books from library (website)

* Retrieve list of books from other library : favorites, history, read later, collections, etc... (website)

* Search book (website)

* Add books to library (website)

* Get information about book (website)

* Get list of chapters from book (website)

* Gets and sets reading position (website, software, cloud)

* Updates information about a book from the other sources (website)

* *Keep track of conflicting information from different sources* (website)

  * **Retrieve chapter from sources** (website)

  * **Gamification or social interactions (includes voting & rating) from sources** (website)

Supported cloud hosting :

* Dropbox

  * *Google Drive*

Supported websites :

* RoyalRoad

* WebNovel

* WLNUpdates

  * *GoodReads*

  * *NovelUpdates*

Supported softwares :

* Moon+Reader

  * *BookFusion*

  * *Calibre Viewer*

  * *Cool Reader*

  * *EBookDroid*

  * *eReader Prestigio*

  * *FBReader*

  * *Foxit Reader*

  * *Lithium*

  * *MuPDF*

  * *Neat Reader*

  * *ReadEra*

  * *STDU Viewer*

# Files

## Config

config.php settings of the application

## Classes & functions

footer.php HTML footer of the pages

functions.inc.php helper functions

header.php HTML header of the pages

royalroad.php class to support royalroad.com

tables.inc.php automatic table generator

webnovel.php class to support webnovel.com

wlnupdates.php class to support wlnupdates.com

## Third-party

CJSON.php from yii framework v1.X, helper to lazy parse json (including badly formatted one, or js object instead of json). v2.X is strict.

## Endpoints

**correspondances.php** List correspondances between royalroad, webnovel and wlnupdates

**dropbox.php** List new position sync and update wlnupdates and webnovel

**dropbox2.php** List new novels and add them to wlnupdates and webnovel

**dropbox.inc.php** Gets all the positions from cloud source

**news.php** kust biveks with new chapters (depends on retr, position and correspondances)

**position.php** List positions from the novels in the ebook reader

**retr.php** retrieve wlnupdates data (& possibly others in the future)

**update_list.php** move qidian's novels into QIDIAN or QIDIAN original list on wlnupdates, update details on wlnupdates from data of webnovel

**webnovel_data.php** List data (counts) from webnovel

**webnovel_news.php** List novels from webnovel with new chapters (depends on retr)

## Data files

.cookies.txt cookies for the HTTP requests (internal files)

\*/\*.json, \*/\*.htm result of HTTP requests (cache for debug)

### Site-specific

[royalroad/\_books.json](royalroad/_books.json) library data of the order from royalroad

[royalroad/\_order.json](royalroad/_order.json) library data from royalroad

[royalroad.htm](royalroad.htm) raw table of royalroad data

[webnovel/\_books.json](webnovel/_books.json) library data from webnovel (merged, reordered)

[webnovel/\_books2.json](webnovel/_books2.json) library data from webnovel (merged, order kept)

[webnovel/\_order.json](webnovel/_order.json) library data of the order from webnovel

[webnovel/\_history.json](webnovel/_history.json) history data from webnovel

[webnovel/\_collection.json](webnovel/_collection.json) collection (reading lists) data from webnovel

[webnovel/\_subname.json](webnovel/_subname.json) map data of novel to subname

[webnovel/\_subnames.json](webnovel/_subnames.json) map data of subname to novel(s)

[wlnupdates/\_books.json](wlnupdates/_books.json) library json data from wlnupdates

[wlnupdates/\_order.json](wlnupdates/_order.json) library json data of the order from wlnupdates

[wlnupdates/\_list.json](wlnupdates/\_list.json) library json data of the list the novel is from on wlnupdates

### Listings

[wlnupdates.htm](wlnupdates.htm) raw table of wlnupdates data

[webnovel.htm](webnovel.htm) raw table of webnovel data (sorted)

webnovel2.htm raw table of webnovel data (identical to website, sorted by last add/update)

[webnovel\_data.htm](webnovel_data.htm) table of webnovel data counts (include lists, history\*2, collection list, all collections)

[news.htm](news.htm) table of novels with positions and new updates

### General

[correspondances.json](correspondances.json) data of correspondances between royalroad, webnovel and wlnupdates (= novel id)

[correspondances\_rr.json](correspondances_rr.json) data of the correspondance from royalroad to webnovel and wlnupdates

[correspondances\_wln.json](correspondances_wln.json) data of the correspondance from wlnupdates to royalroad and webnovel

[correspondances\_wn.json](correspondances_wn.json) data of the correspondance from webnovel to royalroad and wlnupdates

[names.json](names.json) data of the correspondance from the name to royalroad, wlnupdates and webnovel

[pos.json](pos.json) data of the \(last\) position of each novel

[pos\_dev1.json](pos_dev1.json) data of the \(last\) position of each novel that I was the last to update

[pos\_dev9.json](pos_dev9.json) data of the \(last\) position of each novel that the software was the last to update

[pos\_old.json](pos_old.json) data of the \(previous\) position of each novel, to be deleted/remplaced on next update

[pos\_others.json](pos_others.json) data of the position from unrecognized novels

[wn\_diff.json](wn_diff.json) data of number of privilege chapters for watched series (= the difference between webnovel reading position and wlnupdates reading position)
