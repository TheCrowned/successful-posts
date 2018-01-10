# Successful Posts
A small, work-in-progress WordPress plugin that provides site successful articles list and tries to find indicators of success among articles features.

## How to use
We assume you have a webserver with a working installation of WordPress on which you can install the plugin. In case you don't, there is a test website here
http://calendariostefano.gdn/wp-admin/admin.php?page=successful-posts_stats
to which you can access with the following credentials:

user: `successful-posts-test`

pass: `successful-posts-test-password`

An example data set is provided in the repository (in the data directory), although the application has been test with far bigger data sets. Those can't be made public since they come from live websites whose owners shared their articles information with me with the agreement that I would not have made them public.

To change data set, you need to enter its directory name into the variable `$sp_data_dir_name` found in line 34 of successful-posts.php.

## Data files details
Data files should contain a serialized array of _WP_Post_ object, each having a populated _sp_metric_ property. sp_metric should be an array containing (at least for now) one or two values. In our examples, sp_metric contains pageviews and page adsense revenue (taken from Google Analytics). Just look at one of the txt files to understand the structure.

Data files name should be in the form

`websiteName__timeEnd-timeStart__n.txt`

where 

- _websiteName_ is an identifier for the website the data comes from;
- _timeEnd-timeStart_ are the unix timestamp which identify the time range that the Analytics data are about;
- _n_ is the file number (data from one month could be split in several files to reduce file dimensions).

An example is `Stefano Ottolenghi&#039;s Wordpress__1453028153-1450436153__0.txt`

which would contain the first (_0_-th) chunk of data coming from the website _Stefano Ottolenghi&#039;s Wordpress_ about the time range _2016/01/17 - 2015/12/18_.
