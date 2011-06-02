// Copyright (c) 2010, Pierre-Alain Dorange.
// OpenLayers Map API for PmWiki.

var linked = Array();

var miniIcon = new GIcon();
miniIcon.shadow = 'http://labs.google.com/ridefinder/images/mm_20_shadow.png';
miniIcon.iconSize = new GSize(12, 20);
miniIcon.shadowSize = new GSize(22, 20);
miniIcon.iconAnchor = new GPoint(6, 20);
miniIcon.infoWindowAnchor = new GPoint(5, 1);

var stdIcon = new GIcon();
stdIcon.iconSize = new GSize(32, 32);
stdIcon.iconAnchor = new GPoint(9, 34);
stdIcon.shadowSize = new GSize(59, 32);
stdIcon.infoWindowAnchor = new GPoint(9, 2);
stdIcon.infoShadowAnchor = new GPoint(18, 25);

var markerIcon = new GIcon();
markerIcon.shadow = "$shadow";
markerIcon.iconSize = new GSize(20, 34);
markerIcon.shadowSize = new GSize(37, 34);
markerIcon.iconAnchor = new GPoint(9, 34);
markerIcon.infoWindowAnchor = new GPoint(9, 2);
markerIcon.infoShadowAnchor = new GPoint(18, 25);

function setOlaMapCenter(map, zoom,lat,lon) {
  map.setCenter(new GLatLng(clat,clon), zoom );
}
// This function picks up the click and opens the corresponding info window
function makeMarkerIcon(ba,ov) {
  var label = {'url':overlay[ov], 'anchor':new GLatLng(4,4), 'size':new GSize(12,12)};
  var icon = new GIcon(G_DEFAULT_ICON, background[ba], label);
  return icon;
}
function addOlaPoint(map,lat,lon,name,msg,icon) {
  var point = new GLatLng(lat,lon); 
  var marker = new GMarker(point);
  // TODO: Put Iconic stuff here.
  // http://www.econym.demon.co.uk/googlemaps/examples/label.htm
  //icon = makeMarkerIcon(icon);
  //var marker = new GMarker(point,icon);
  bounds.extend(point);

  // The info window version with the 'to here' form open
  if (msg) {
    name = (name) ? '<b>'+name+'</b>\n' : '';
    to_htmls[i] = msg + '<br>Directions: <b>To here</b> -'
                + '<a href="javascript:fromhere(' + i + ')">From here</a>' 
                + '<br>Start address:<form action="http://maps.google.com/maps" method="get" target="_blank">' 
                + '<input type="text" size=40 maxlength=80 name="saddr" id="saddr" value="" /><br>' 
                + '<input value="Get Directions" type="submit">' 
                + '<input type="hidden" name="daddr" value="'
                + point.lat() + ',' + point.lng() + '"/>';
    // The info window version with the 'to here' form open
    from_htmls[i] = msg + '<br>Directions: <a href="javascript:tohere(' + i + ')">To here</a> - <b>From here</b>' 
                + '<br>Start address:<form action="http://maps.google.com/maps" method="get" target="_blank">' 
                + '<input type="text" size=40 maxlength=80 name="daddr" id="saddr" value="" /><br>' 
                + '<input value="Get Directions" TYPE="submit">' 
                + '<input type="hidden" name="daddr" value="'
                + point.lat() + ',' + point.lng() + '"/>';
    // The inactive version of the direction info
    msg = name + msg + '<br />Directions: <a href="javascript:tohere('+i+')">To here</a> - <a href="javascript:fromhere('+i+')">From here</a>';
    GEvent.addListener(marker, 'click', function() { marker.openInfoWindowHtml(msg); map.panTo(point); });
  }
  points[i] = point;
  markers[i] = marker;
  htmls[i] = msg;
  i++;
  return marker;
}
function tohere(k) { markers[k].openInfoWindowHtml(to_htmls[k]); }
function fromhere(k) { markers[k].openInfoWindowHtml(from_htmls[k]); }
function makeGmalink(map, k) { 
    map.panTo(points[k]);
    markers[k].openInfoWindowHtml(htmls[k]); 
}
function doGmaOverlay(map) { for (k in markers) map.addOverlay(markers[k]); }
