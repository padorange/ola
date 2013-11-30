<?php
/*
 OpenLayers API for PmWiki 
 =========================
 
 version 0.3 (alpha)
 
 Copyright Statement 
 -------------------
 
 Copyright (c) 2011-2012, Pierre-Alain Dorange, with a few additions made by StefCT (2013).
 OpenLayers Map API for PmWiki.
 Adapted from :
    Google Map API for PmWiki.
    Copyright (c) 2006, Benjamin C. Wilson. All Rights Reserved.
    This copyright statement must accompany this script.

 History
 -------
 0.2 : add support for CycleMap layer in OSM (replacing old osma rendering)
 
*/

define('OLAPATH', dirname(__FILE__) . '/');

# SDV()
SDV($OlaDebug, 0);
SDV($OlaDefaults, array(
    'ctrl' => 'large,attribution',
    'view' => 'mapnik',
    'height' => '300px',
    'width' => '500px',
    'color' => '#808',
    'thickness' => 2,
    'opacity' => 0.7,
    'zoom' => 0
    )
);

# Internals.
$OlaDebugMsg = array();
$OlaHTML = '';
$OlaIEFix = '';
$OlaLines = array();
$OlaPoints = array();
$OlaPointPopulation = 0;
$OlaVersion = '0.1';

# Markup
# * (:ola-map [options]:)
# * (:ola-point lat lon [options]:)
# * (:ola-line lat lon [options]:)
Markup('ola', '_begin', '/\(:ola-(\S+)\s?(.*?):\)/ie',"olaMarkup('$1','$2');");

class OlaLine
{
  function OlaLine($id) {
    global $OlaLines, $OlaDefaults;
    $this->id = $id;
    $this->color = $OlaDefaults['color'];
    $this->opacity = $OlaDefaults['opacity'];
    $this->points = array();
    $this->thickness = $OlaDefaults['thickness'];
  }
  function addPoint($lat, $lon) {
    if ($lat && $lon) array_push($this->points, "new GLatLng($lat, $lon)");
  }
  function line($map_id='map') {
    $id = $this->id;
    $pad = str_pad('', 26 + strlen($id), ' ', STR_PAD_LEFT);
    $points = '['.join(",\n$pad", $this->points).']';
    $opacity = ($this->opacity > 1) 
        ? sprintf("%0.2f", $this->opacity / 100)
        : $this->opacity;
    $opacity = ($opacity > 1) ? 1.0 : $opacity;
    $options = join(', ', array(
                    $points,
                    $this->color(),
                    $this->thickness,
                    $opacity
                ));
    $this->overlay = "    $map_id.addOverlay($id);\n";
    return "    var $id = new GPolyline($options);\n";
  }
  function color() {
    // This method returns only web-safe colors.
    $color = preg_replace("/\\\'/",'',$this->color);
    if (0 === strpos($color, '#')) $color = substr($color, 1);
    // break into hex 3-tuple
    $cutpoint = ceil(strlen($color) / 2) - 1;
    $rgb = explode(':', wordwrap($color, $cutpoint, ':', $cutpoint), 3);

    $out = '';
    foreach($rgb as $r) {
        if (strlen($r) == 1) $r .= $r; # Expand single value colors.
        $r = (isset($r)) ? hexdec($r) : 0; # Hex to Dec.
        $r = (round($r/51) * 51); # Make web-safe
        $out .= str_pad(dechex($r), 2, '0', STR_PAD_LEFT); # Code color.
    }
    return "'#$out'";
  }
}

class OlaMap
{
    var $views_js = array(
      'mapnik' => 'new OpenLayers.Layer.OSM.Mapnik("Mapnik")',
      'cyclemap' => 'new OpenLayers.Layer.OSM.CycleMap("CycleMap")',
      'openmapquest' => 'new OpenLayers.Layer.OSM.OpenMapQuest("OpenMapQuest")'
    );
    var $views = array();
    
    var $controls_js = array(
      'permalink' => 'new OpenLayers.Control.Permalink()',
      'navigation' => 'new OpenLayers.Control.Navigation()', # allow toggle b/w map types.
      'layer' => 'new OpenLayers.Control.LayerSwitcher()', # allow map controls.
      'attribution' => 'new OpenLayers.Control.Attribution()',
      'large' => 'new OpenLayers.Control.PanZoomBar()',
      'small' => 'new OpenLayers.Control.PanZoom()',
      'overview' => 'new OpenLayers.Control.OverviewMap()',
      'scale' => 'new OpenLayers.Control.ScaleLine()'
    );
    var $controls = array();
    
    function OlaMap($id='map') {
      global $OlaDefaults;
      $this->id = $id;
      $this->height = $OlaDefaults['height'];
      $this->width = $OlaDefaults['width'];
      $this->zoom = $OlaDefaults['zoom'];
      $this->setControl((array) explode(',', $OlaDefaults['ctrl']));
      $this->setView((array) explode(',', $OlaDefaults['view']));
      $this->lat = "null";
      $this->lon = "null";
    }
    
    function setControl($controls) {
      foreach ((array) $controls as $c) {
        if ($c[0] == '-' && array_key_exists(substr($c,1), $this->controls)) {
          unset($this->controls[substr($c,1)]);
        }
        else {
          $this->controls[$c] = 1;
        }
      }
    }
    
    function getControls() {
        $ret = '';
        $id = $this->id;
        foreach (array_keys($this->controls) as $c) {
          if ($c == 'small' && array_key_exists('large', $this->controls)){
            unset($this->controls[$c]);
          }
          elseif ($c == 'large' && array_key_exists('small', $this->controls)){
            unset($this->controls[$c]);
          }
          elseif ($c[0] != '-') {
            $c = $this->controls_js[$c];
            $ret .= "    $id.addControl($c);\n";
          }
        }
        return $ret;
    }
    
    function setView($views) {
      foreach ((array) $views as $v) {
        if ($v[0] == '-' && array_key_exists(substr($v,1), $this->views)) {
          unset($this->views[substr($v,1)]);
        }
        else {
          $this->views[$v] = 1;
        }
      }
    }
    
    function getViews() {
        $ret = '';
        $id = $this->id;
        foreach (array_keys($this->views) as $v) {
          if ($v[0] != '-') {
            $a = $this->views_js[$v];
            $ret .= "    $id.addLayer($a);\n";
          }
        }
        return $ret;
    }
}

class OlaPoint
{
  var $icon = '';
  var $lat = 0.0;
  var $lon = 0.0;
  var $message = '';
  var $title = '';
  function OlaPoint($lat='', $lon='') {
    global $OlaPointPopulation;
    $this->id = $GmaPointPopulation++;
    if ($lat && $lon) { $this->lat = $lat; $this->lon = $lon; }
  }
  
  function icon() { return $this->_quote($this->icon);}

  function point($map_id='map',$markerLayer) {
    $icon = $this->icon();
    $lat = $this->lat;
    $lon = $this->lon;
    $text = $this->message();
    $title = $this->_quote($this->title);
    $olapath=OLAPATH;
    $ret="var size = new OpenLayers.Size(21,25);\n";
    $ret.="var offset = new OpenLayers.Pixel(-(size.w/2), -size.h);\n";
    $ret.="var icon = new OpenLayers.Icon('http://openlayers.org/api/img/marker.png', size, offset);\n";
    $ret.="$markerLayer.addMarker(new OpenLayers.Marker(new OpenLayers.LonLat($lon,$lat).transform($map_id.displayProjection, $map_id.projection),icon));\n";
    
    return $ret;
  }
  
  function _quote($i) { return ($i) ? $this->_jsstrip("'$i'") : 'null'; }
  function _jsstrip($i) { return $i;} # TODO: Strip Javascript.
  function message() {
    if ($this->message) {
      $m = $this->message;
      $m = preg_replace('/&lt;/','<', $m);
      $m = preg_replace('/&gt;/','>', $m);
    }
    $m = $this->_quote($m);
    return $m;
  }
  function anchor($anchor,$mapid='map') {
    if (!$anchor) { return ''; }
    $anchor = $this->_jsstrip($anchor);
    $id = $this->id;
    $link =  "<a name='{$anchor}'></a>"
            ."<a href='javascript:makeOlalink({$mapid},{$id});'>{$anchor}</a>";
    $this->message .= "<a href=\'#$anchor\'>$anchor</a>";
    return "$link";
  }
} // End Class OlaPoint

function olaBrowserFix() {
}

function olaCleanup() {
  global $HTMLHeaderFmt, $HTMLStylesFmt, $HTMLFooterFmt;
  global $OlaEnable, $OlaVersion, $OlaScript;
  global $OlaMap, $OlaPoints, $OlaLines;
  global $OlaKey, $OlaDebugMsg, $OlaDefaults;

  OlaDoIEFix();
  #--------------------------------
  # Set the HTML
  $height = $OlaMap->height;
  $width = $OlaMap->width;
  $clat = $OlaMap->lat;
  $clon = $OlaMap->lon;
  $map_id = $OlaMap->id;
  $markerLayer = 'markerLayer';
  
  $HTMLStylesFmt['olmap_api'] 
     = "div#$map_id{ height: $height; width: $width; }";
  $HTMLHeaderFmt[] 
     =  '<style type=\'text/css\'>v:* { behavior:url(#default#VML); }</style>'
//       ."\n<script src='\$FarmPubDirUrl/scripts/OpenLayers/OpenLayers.js' type='text/javascript'></script>"
       ."\n<script src='http://openlayers.org/api/OpenLayers.js' type='text/javascript'></script>"
        ."\n<script src='\$FarmPubDirUrl/scripts/OSM/OpenStreetMap.js' type='text/javascript'></script>";
  OlaDebug(print_r($OlaMap, 1));
  $controls = $OlaMap->getControls();
  $views = $OlaMap->getViews();
  $zoom = $OlaMap->zoom or 'null';
  $points = '';
  foreach ($OlaPoints as $p) 
  { 
    if ($points=='') 
    {
      $points="var $markerLayer = new OpenLayers.Layer.Markers('Markers',{ projection:$map_id.displayProjection } );\n";
      $points.="$map_id.addLayer($markerLayer);\n";
    }
    $points .= $p->point($map_id,$markerLayer);
  }
  //$lines = '';
  //$overlay = '';
  //foreach ($OlaLines as $l) { 
  //    $lines .= $l->line($map_id); 
  //    $overlay .= $l->overlay;
  //}
  $debug = ($OlaDebug) ? implode("\n", (array) $OlaDebugMsg) : '';
  $defaults = $OlaDefaults; 
  $defaults = preg_replace('/Array|\(\\n|\)/', '', print_r($defaults, 1));

  $HTMLFooterFmt[] =<<<OLASCRIPT
  $debug
  <!-- GMA Site Default Controls:
  $defaults -->
  <script type="text/javascript">
  //<![CDATA[
  // Copyright (c) 2010, Pierre-Alain Dorange.
  // OpenLayers Map API for PmWiki, $GmaVersion.
  // Adapted from :
  //    Google Map API for PmWiki.
  //    Copyright (c) 2006, Benjamin C. Wilson. All Rights Reserved.
  //    This copyright statement must accompany this script.

    var from_htmls = [];
    var htmls = [];
    var markers = [];
    var points = [];
    var to_htmls = [];
    var i = 0;
    var $map_id = new OpenLayers.Map('$map_id', 
    	{
    		maxExtent: new OpenLayers.Bounds(-20037508.34,-20037508.34,20037508.34,20037508.34),
			numZoomLevels: 19,
			maxResolution: 156543.0399,
			units: 'm',
			projection: new OpenLayers.Projection("EPSG:900913"),
			displayProjection: new OpenLayers.Projection("EPSG:4326")
    	});
$points
//$lines
$controls
$views
	
	var latlon= new OpenLayers.LonLat($clon, $clat).transform($map_id.displayProjection, $map_id.projection);
    $map_id.setCenter(latlon,$zoom);

</script>
OLASCRIPT;
}

function olaDebug($m) {
  // (null) olaDebug(string);
  //
  // Packs end-of-cycle debugging information.
  global $OlaDebug, $OlaDebugMsg;
  array_push($OlaDebugMsg, "<pre>$m</pre>");
}

function olaDefaults($opt, $def, $orig='') {
  // (mixed) olaDefaults(option, site-default);
  //
  // This function overrides site default variable.
  if (!($opt || $def || $org)) return '';
  $val = ($opt && !$orig) ? $opt : $def;
  $val = ($orig) ? $orig : ($opt) ? $opt : $def;
  return $val;
}

function olaMarkup($type, $args) {
  // (string) gmaMarkup(type, args);
  //
  // This function converts all PmWiki markup into GMA objects and attributes
  // and reconciles changes to the site-default.

  global $OlaDefaults, $OlaDebug;
  olaSpoofLocale();
  $ret = '';

  $opts = parseArgs($args);
  OlaDebug("OPTIONS:".print_r($opts,1));
  OlaDebug("CONTROLS:".print_r($controls,1));

  // This switch handles the behavior of the various GMA Types.
  switch($type) {
    // Gma Type: Line "(:gma-line lat lon [options]:)"
    case ('line'):
        global $GmaLines;
        $lid = GmaDefaults('line'.$opts['id'], 'line0');
        if (!array_key_exists($lid, $GmaLines)) {
          $GmaLines[$lid] = new GmaLine($lid);
        }
        $line = &$GmaLines[$lid];
        if ($opts['color']) $line->color = $opts['color'];
        if ($opts['thickness']) $line->thickness = $opts['thickness'];
        if ($opts['opacity']) $line->opacity = $opts['opacity'];

        $line->addPoint($opts['lat'], $opts['lon']);
        $ret .= ($GmaDebug) ?  "<pre>".Keep(print_r($line,1)) : '';
        break;

    // Ola Type: Map "(:gma-map [options]:)"
    case ('map'):
        global $OlaMap;
        global $MarkupFrame;
        // Create The Map Object and set attributes.
        $OlaMap = ($opts['id']) ? new OlaMap($opts['id']) : new OlaMap();

        //Additions by StefCT --Start---
        if ($opts['link']) {
                global $FmtPV; 
                $FmtPV['$OSMLink'] = "'".$opts['link']."'";
                preg_match('/mlat=(?P<lat>[^&#]*).*mlon=(?P<lon>[^&#]*)/', $opts['link'], $matchespoint);
                preg_match('/map=(?P<zoom>[^\/]*)\/(?P<lat>[^\/]*)\/(?P<lon>[^\/]*)/', $opts['link'], $matchesmap);
                if ($matchesmap["lat"] && $matchesmap["lon"]) {
                    $OlaMap->lat = $matchesmap['lat'];
                    $OlaMap->lon = $matchesmap['lon'];
                }
                if ($matchesmap["lat"] && $matchesmap["lon"]) {
                    $OlaMap->zoom = $matchesmap['zoom'];
                        }
                if ($matchespoint["lat"] && $matchespoint["lon"]) {
                        global $OlaPoints;
                        // Build the Point object.
                        $point = new OlaPoint($matchespoint['lat'], $matchespoint['lon']);
                        // Add the point to the Collection.
                        array_push($OlaPoints, $point);
                        $ret .= ($OlaDebug) ?  "<pre>".Keep(print_r($point,1)) : '';
                        }
                }
        //Additions by StefCT --End--

        if ($opts['ctrl']) $controls = (array) explode(',', $opts['ctrl']);
        $OlaMap->setControl($controls);
        if ($opts['view']) $views = (array) explode(',', $opts['view']);
        $OlaMap->setView($views);
        if ($opts['zoom'])   $OlaMap->zoom = $opts['zoom'];
        //if ($opts['view'])   $OlaMap->view = $opts['view'];
        if ($opts['height']) $OlaMap->height = $opts['height'];
        if ($opts['width'])  $OlaMap->width = $opts['width'];
        if ($opts['lat'] && $opts['lon']) {
            $OlaMap->lat = $opts['lat'];
            $OlaMap->lon = $opts['lon'];
        }

        // Trigger the end-of-markup addition of the Javascript,
        // and give the map's target.
        if (!$MarkupFrame[0]['posteval']['mymarkup'])
          $MarkupFrame[0]['posteval']['mymarkup'] = 'olaCleanup();';
        $ret .= Keep("<noscript id='js-warning'><p>Javascript is disabled, please enable it first to view the map!</p></noscript><div id='{$OlaMap->id}'></div>"); // added by StefCT
        $ret .= ($OlaDebug) ?  "<pre>".Keep(print_r($OlaMap,1)) : '';
        break;

    // Ola Type: Point "(:gma-point lat lon [options]:)"
    case ('point'):
        global $OlaPoints;
        // Build the Point object.
        $point = new OlaPoint($opts['lat'], $opts['lon']);
        $point->icon = olaDefaults($opts['icon'], '');
        $point->message .= olaDefaults($opts['text'], '');
        if ($opts['link']) $ret .= $point->anchor($opts['link']);
        // Add the point to the Collection.
        array_push($OlaPoints, $point);
        $ret .= ($OlaDebug) ?  "<pre>".Keep(print_r($point,1)) : '';
        break;
    // Ola Type: (Invalid)
    default:
        $ret = "GMA Error: Type Unknown ($type)";
  }
  olaSpoofLocale();
  return $ret;
}
function olaSpoofLocale() {
    // (null) gmaSpoofLocale(null);
    //
    // Provided by HelgeLarsen. This allows for non-US locales to behave as
    // normal. I moved it into a function for smoother toggle, but this may be
    // OBE.
    global $OlaHoldLocale, $OlaLocaleToggle;
    if ($OlaLocaleToggle) {
      setlocale(LC_NUMERIC,$OlapHoldLocale);
    }
    else {
      $OlapHoldLocale = setlocale(LC_NUMERIC,'0');
      setlocale(LC_NUMERIC,'en_US');
    }
    $OlaLocaleToggle = ($OlaLocalToggle) ? 0 : 1;
}


function OlaDoIEFix() {
  global $OlaIEFix;
  if (get_cfg_var('browscap')) {
   $browser=get_browser(); //If available, use PHP native function
  }
  $GmaIEFix = (preg_match('/IE/', $browser->browser))
    ? " xmlns='http://www.w3.org/1999/xhtml' xmlns:v='urn:schemas-microsoft-com:vml'"
    : '';
}

