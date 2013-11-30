OpenLayers API for PmWiki (OLA)
===============================
http://www.pmwiki.org/wiki/Cookbook/OpenLayersAPI

Copyright Statement 
-------------------
Licence : GPL
Copyright (c) 2011-2012, Pierre-Alain Dorange
adapted from Google Map API from Benjamin C. Wilson (2006).

Abstract
--------

OLA allow simple embed of OpenLayers library functionality into pmWiki.
The main function is to embed OpenStreetMap map.

OLA for pmWiki can display OpenStreetMap into wiki pages and add markers.

OLA is actually a alpha version. Future realize may include more options,
and will make more efficient use of OpenLayers, including using other maps 
sources and drawing functions : lines, polygons...

More about pmwiki : http://www.pmwiki.org/

Installation
------------
 
1. Install the Scripts. 
The first step in the installation is to acquire and
install the software. To place the files in their proper location, extract
the files in the parent directory to both the /pub and /cookbook
directories.  Otherwise, copy the contents of the two directories as
appropriate.  Specifically, this software should have the recipe file in the
/cookbook directory, the javascript in the /pub/scripts directory.

Note : OLA make use of OpenLayers library (javascript) using the online
version available at : http://openlayers.org/api/OpenLayers.js
If you need to do not rely on OpenLayers servers or need a special
version you can include your own OpenLayers in scripts folder.
 
2. Configure the Site. 
After the code is installed, you will need to configure
the site. This is typically done in the local/config.php file. 
 
You will need to include the recipe (e.g.
"include_once("$FarmD/cookbook/OpenLayersAPI/ola.php");").

Using the OpenLayers/OSM API for PmWiki
---------------------------------------
 
Using this recipe is fairly straight forward. There are two types of map
tools available: point and map. The map tool must be used to generate
the map. Either the point or the line must also be used, and these types may
be mixed. We will take each map tool in turn, beginning with the map tool.
 
Using the Map Tool. 
The map tool generates the map itself. The map is located
where the directive is placed on the wiki page. Specifically, the map tool
returns an empty DIV statement which the Google software uses to embed the
map. The map tool provides several options which help control the use of the
map. Each of these options may be configured by the site administrator, or
set by the editor for a specific map. 
These options include:

   * 'mapid' (default 'map'): The mapid option allows the editor to determine 
     the CSS name for the map. This allows multiple maps on a single page.
   * 'view' (default 'mapnik'): The view obtion allows the editor to set the
     initial display of the map. There are three displays available: 'mapnik',
     'cyclemap' and 'openmapquest'.
     user can specify multiple views, final can switch using the layer controler
     if this controller is activated
   * 'height' (default 300px): The height option the editor to set the height
     of the map. This value responds to CSS values (e.g. pixels, em, pt,
     percent, etc.)
   * 'width' (default 500px): The width option the editor to set the width of
     the map. This value responds to CSS values (e.g. pixels, em, pt,
     percent, etc.)
   * 'ctrl' (default small): The ctrl option allows the editor to select which map
     controls are available to the visitor. There are 7 controls: maptype,
     large, small, and overview. As each map control may be set by the site
     administrator, the editor may unset a default value. The negative sign
     is used to unset a default value (e.g. if the default is to show the
     overview, then '-overview' will turn off this map control. This variable
     is an array and values are set in serial (e.g.
     "ctrl=small,-overview,permalink")
     * The layer control allows the visitor to change the map type between
       mapnik, osma and openmapquest. If set by default, -maptype will turn
       this control off.
     * The large and small controls allow the editor to decide whether the
       zoom and pan controls are large or small. Setting the opposite will
       deselect the default size.
     * The overview control allows the visitor to see a small window in the
       lower right hand corner. This window displays a smaller-scale map of
       the area in the main window.
     * The permalink control allows visitor to get an url specific for the current view
     * The attribution control display openstreetmap attribution over the map
     * the scale control display the current scale of the view

Using the Point Tool. 
The point tool places a marker at the designated spot.
The location is determined by latitude and longitude settings. Additionally,
the point can contain a link and/or text. (Coming soon) Finally, the marker
look can be determined using the icon option. The options include:
   * lat (default 0): The lat option sets the point's latitude.
   * lon (default 0): The lon option sets the point's latitude.
   * mapid (default 'map'): This option assigns a point to a specific map.
     This is used in conjunction with the map 'mapid' option to place a
     point on a specific map.
   * text (no default): This option creates HTML text in an information
     window. Clicking this point's marker causes the information window to
     open. The text should be quoted.
   * icon (not active): This option allows the editor to use any of a
     number of icons to distinguish points on the map. This includes letters,
     numbers, colors and shapes.

