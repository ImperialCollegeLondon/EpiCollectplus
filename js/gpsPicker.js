(function( $ ) {
  $.fn.gpsPicker = function(cnf) {
	  
	  if(typeof cnf != "object") cnf = {};
	  if(!cnf.mapType) cnf.mapType = "google";
	  
	  eleService = null;
	  coder = null;
	  
	  if(cnf.mapType == "google")
	  {
		  eleService = new google.maps.ElevationService();
		  coder = new google.maps.Geocoder();
	  }
	  
	  
	  this.append("<div id=\"" + this.attr("id") + "_map\" class=\"ecplus-map\" style=\"height: " +this.height()+ "px;\"></div>");
	  this.append("<div class=\"ecplus-map-controls\"  style=\"display:inline-block;\">" +
	  		"<fieldset><label for=\"search\">Search for an address </label><input  type=text id=\"geocodeCtrl\" /><a href=\"javascript:$('#" + this.attr("id") + "').geocode($('#geocodeCtrl', $('#" + this.attr("id") + "')[0]).val())\">Search</a></fieldset>" +
	  		"<fieldset>" +
	  		"<label for=\"latitude\">Latitude (Decimal Degrees)</label><input type=\"number\" id=\"latitude\"/>" +
	  		"<label for=\"longitude\">Longitude (Decimal Degrees)</label><input type=\"number\" id=\"longitude\"/>" +
	  		"<label for=\"altitude\">Altitude (in meters)</label><input type=\"number\" id=\"altitude\"/>" +
	  		"<label for=\"accuracy\">Accuracy (in meters)</label><input type=\"number\" id=\"accuracy\"/>" +
	  		"<label for=\"bearing\">Bearing</label><input type=\"number\" id=\"bearing\"/>" +
	  		"<label for=\"provider\">Source of the Position</label><input type=\"text\" id=\"provider\"/>" +
	  		"</fieldset></div>");
	  
	  
	  lmap = (cnf.mapType == "osm" ? new L.Map(this.attr("id") + "_map") : new google.maps.Map($("#" + this.attr("id") + "_map")[0],
			  {
		  		mapTypeId : google.maps.MapTypeId.ROADMAP,
		  		center : new google.maps.LatLng(0,0),
		  		zoom : 0
			  }));
	  

	  // create the tile layer with correct attribution
	  if(cnf.mapType == "osm")
	  {
		  var osmUrl='http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
		  var osmAttrib='Map data © OpenStreetMap contributors';
		  var osm = new L.TileLayer(osmUrl, {attribution: osmAttrib});
	  }

	  if(cnf.mapType == "osm")
	  {
		  lmap.setView(new L.LatLng(0, 0),1);
		  lmap.addLayer(osm);
		  
		  mkr = new L.Marker(new L.LatLng(0, 0), {draggable : true});
		  lmap.addLayer(mkr);
		  
		  accCircle = new L.Circle(new L.LatLng(0, 0), 100);
		  lmap.addLayer(accCircle);
	  }
	  else
	  {
		  mkr = new google.maps.Marker({position : new google.maps.LatLng(0, 0), draggable : true})
		  mkr.setMap(lmap);
		  
		  accCircle = new google.maps.Circle({center : new google.maps.LatLng(0, 0), radius : 100, map : lmap});  
	  }
	  ctx = this;	  
	  
	  function dropmarker(){ 
		  try{
			  if(cnf.mapType == "osm")
			  {
				  accCircle.setLatLng(mkr.getLatLng())
			  }
			  else
			  {
				  accCircle.setCenter(mkr.getPosition());
			  }
			  
			  $("#latitude", ctx).val((cnf.mapType == "osm" ? mkr.getLatLng().lat : mkr.getPosition().lat()));
			  $("#longitude", ctx).val((cnf.mapType == "osm" ? mkr.getLatLng().lng : mkr.getPosition().lng()));
			  $("#accuracy", ctx).val(Math.max(accCircle.radius, 100));
			  $("#provider", ctx).val("Marker Dropped");
			  $("#bearing", ctx).val("0");
			  
			  if(eleService)
			  {
				  eleService.getElevationForLocations({locations  : [ mkr.getPosition() ]}, elevationCallback);
			  }
		  }catch(err){alert(err);}
	  }
	  
	  function elevationCallback(results, status)
	  {
		  if(status == google.maps.ElevationStatus.OK)
		  {
			  $("#altitude", ctx).val(results[0].elevation);
		  }
		  else
		  {
			  alert("Elevation lookup failed");
		  }
	  }
	  
	  function geocodeSearch(address)
	  {
		if(!coder) return;
		
		coder.geocode({address : address}, geocodeCallback);
			
	  }
	  
	  function geocodeCallback(results, status)
	  {
		  if(status == google.maps.GeocoderStatus.OK)
		  {
			  pos = results[0].geometry.location;
			  
			  $("#latitude", ctx).val(pos.lat());
			  $("#longitude", ctx).val(pos.lng());
			  $("#accuracy", ctx).val(Math.max(accCircle.radius, 100));
			  $("#provider", ctx).val("Geocoding");
			  $("#bearing", ctx).val("0");
			  
			  mkr.setPosition(pos);
			  accCircle.setCenter(pos);
			  
			  if(eleService)
			  {
				  eleService.getElevationForLocations({locations  : [ pos ]}, elevationCallback);
			  }
		  }
	  }
	  
	  $.fn.geocode = geocodeSearch;
	  
	  $("#latitude, #longitude", ctx).change(function()
	  {
		  var pos = (cnf.mapType == "osm" ? new L.LatLng($("#latitude", ctx).val(), $("#longitude", ctx).val()) : new google.maps.LatLng($("#latitude", ctx).val(), $("#longitude", ctx).val()));
		  cnf.mapType == "osm" ? mkr.setLatLng(pos) : mkr.setPosition(pos);
		  cnf.mapType == "osm" ? accCircle.setLatLng(pos) : accCircle.setCenter(pos);
		  cnf.mapType == "osm" ? lmap.setView(pos, 9): lmap.setCenter(pos);
	  });
	  
	  $("#accuracy", ctx).change(function(){
		  accCircle.setRadius(Number($("#accuracy", ctx).val())); 		  
	  });
	
	  if(cnf.mapType == "osm")
	  {
		  mkr.on("dragend", dropmarker);
	  }
	  else
	  {
		  google.maps.event.addListener(mkr, "dragend", dropmarker)
	  }
	  
	  var originalVal = $.fn.val;
	  $.fn.val = function(value) {
		if(this.hasClass("locationControl"))
		{
			if (typeof value != 'undefined') 
			{
			     fromJSON(value);
			}
			else
			{
				 return toJSON();
			}
		}
		else if(value)
		{
			return originalVal.call(this, value);
		}
		else
		{
			return originalVal.call(this);
		}
	  };

	  
	  function toJSON()
	  {
		  var lat = $("#latitude", ctx).val();
		  lat = lat ? lat : "0";
		  var lon = $("#longitude", ctx).val();
		  lon = lon ? lon : "0";
		  var acc = $("#accuracy", ctx).val();
		  acc = acc ? acc : "-1";
		  var alt = $("#altitude", ctx).val();
		  alt = alt ? alt : "0";
		  var bear = $("#bearing", ctx).val();
		  bear = bear ? bear : "0";
		  return  "{ \"latitude\" : " + lat +
		  	" , \"longitude\" : " + lon + 
		  	", \"accuracy\" : " +  acc +
		  	", \"altitude\" : " + alt  +
		  	", \"bearing\" : " + bear  + 
		  	", \"provider\" : \"" + $("#provider", ctx).val()+ "\"}";
	  }
	  
	  function fromJSON(json)
	  {
		  if(typeof json != "object") obj = JSON.parse(json);
		  else obj = json;
		  
		  for(f in obj)
		  {
			  $("#" + f, ctx).val(obj[f] === 0 ? "0" : obj[f]);
		  }
		  
		  center = new google.maps.LatLng(obj.latitude, obj.longitude);
		  mkr.setPosition(center);
		  accCircle.setCenter(center);
		  accCircle.setRadius(Number(obj.accuracy));
	  }
  };
})( jQuery );