(function( $ ) {
  $.fn.gpsPicker = function(cnf) {
      
      var _id = this.attr("id");
      
	  if(typeof cnf != "object") cnf = {};
	  if(!cnf.mapType) cnf.mapType = "google";
	  
	  var eleService = null;
	  var coder = null;
      var mkr = null;
	  
	  if(cnf.mapType == "google")
	  {
		  eleService = new google.maps.ElevationService();
		  coder = new google.maps.Geocoder();
	  }
	  
	  
	  this.append("<div id=\"" + _id + "_map\" class=\"ecplus-map\" style=\"height: " +this.height()+ "px;\"></div>");
	  this.append("<div class=\"ecplus-map-controls\"  style=\"display:inline-block;\">" +
	  		"<fieldset><label for=\"search\">Search for an address </label><input  type=text id=\"" + _id + "geocodeCtrl\" /><a class=\"geocode button\">Search</a></fieldset>" +
	  		"<fieldset>" +
	  		"<label for=\"" + _id + "latitude\">Latitude (Decimal Degrees)</label><input type=\"number\" id=\"" + _id + "latitude\"/>" +
	  		"<label for=\"" + _id + "longitude\">Longitude (Decimal Degrees)</label><input type=\"number\" id=\"" + _id + "longitude\"/>" +
	  		"<label for=\"" + _id + "altitude\">Altitude (in meters)</label><input type=\"number\" id=\"" + _id + "altitude\"/>" +
	  		"<label for=\"" + _id + "accuracy\">Accuracy (in meters)</label><input type=\"number\" id=\"" + _id + "accuracy\"/>" +
	  		"<label for=\"" + _id + "bearing\">Bearing</label><input type=\"number\" id=\"" + _id + "bearing\"/>" +
	  		"<label for=\"" + _id + "provider\">Source of the Position</label><input type=\"text\" id=\"" + _id + "provider\"/>" +
	  		"</fieldset></div>");
	  
	  
	  var lmap = (cnf.mapType == "osm" ? new L.Map(_id + "_map") : new google.maps.Map($('.ecplus-map', this)[0],
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
			  
			  $("#" + _id + "latitude").val((cnf.mapType == "osm" ? mkr.getLatLng().lat : mkr.getPosition().lat()));
			  $("#" + _id + "longitude").val((cnf.mapType == "osm" ? mkr.getLatLng().lng : mkr.getPosition().lng()));
			  $("#" + _id + "accuracy").val(Math.max(accCircle.radius, 100));
			  $("#" + _id + "provider").val("Marker Dropped");
			  $("#" + _id + "bearing").val("0");
			  
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
			  $("#" + _id + "altitude").val(results[0].elevation);
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
			  var res = results[0].geometry
			  var pos = res.location;
              var bnds;
              
              if(res.bounds)
              {
                bnds = res.bounds;
                acc = google.maps.geometry.spherical.computeDistanceBetween(bnds.getCenter(), bnds.getNorthEast());
             }
             else
             {
                acc = 1000;
             }
			  
			  $("#" + _id + "latitude").val(pos.lat());
			  $("#" + _id + "longitude").val(pos.lng());
			  $("#" + _id + "accuracy").val(acc);
			  $("#" + _id + "provider").val("Geocoding");
			  $("#" + _id + "bearing").val("0");
			  
			  mkr.setPosition(pos);
			  accCircle.setCenter(pos);
			  accCircle.setRadius(acc);
			  
			  if(eleService)
			  {
				  eleService.getElevationForLocations({locations  : [ pos ]}, elevationCallback);
			  }
		  }
	  }
	  
	  $('.geocode', this).click(function(){ geocodeSearch($('#' + _id + 'geocodeCtrl').val()); })
	  
	  $("#" + _id + "latitude, #" + _id + "longitude").change(function()
	  {
		  var pos = (cnf.mapType == "osm" ? new L.LatLng($("#" + _id + "latitude").val(), $("#" + _id + "longitude").val()) : new google.maps.LatLng($("#" + _id + "latitude").val(), $("#" + _id + "longitude").val()));
		  cnf.mapType == "osm" ? mkr.setLatLng(pos) : mkr.setPosition(pos);
		  cnf.mapType == "osm" ? accCircle.setLatLng(pos) : accCircle.setCenter(pos);
		  cnf.mapType == "osm" ? lmap.setView(pos, 9): lmap.setCenter(pos);
	  });
	  
	  $("#" + _id + "accuracy").change(function(){
		  accCircle.setRadius(Number($("#" + _id + "accuracy").val())); 		  
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
		  var lat = $("#" + _id + "latitude").val();
		  lat = lat ? lat : "0";
		  var lon = $("#" + _id + "longitude").val();
		  lon = lon ? lon : "0";
		  var acc = $("#" + _id + "accuracy").val();
		  acc = acc ? acc : "-1";
		  var alt = $("#" + _id + "altitude").val();
		  alt = alt ? alt : "0";
		  var bear = $("#" + _id + "bearing").val();
		  bear = bear ? bear : "0";
		  return  "{ \"latitude\" : " + lat +
		  	" , \"longitude\" : " + lon + 
		  	", \"accuracy\" : " +  acc +
		  	", \"altitude\" : " + alt  +
		  	", \"bearing\" : " + bear  + 
		  	", \"provider\" : \"" + $("#" + _id + "provider").val()+ "\"}";
	  }
	  
	  function fromJSON(json)
	  {
		  if(typeof json != "object") obj = JSON.parse(json);
		  else obj = json;
		  
		  for(f in obj)
		  {
			  $("#" + _id + f).val(obj[f] === 0 ? "0" : obj[f]);
		  }
		  
		  center = new google.maps.LatLng(obj.latitude, obj.longitude);
		  mkr.setPosition(center);
		  accCircle.setCenter(center);
		  accCircle.setRadius(Number(obj.accuracy));
	  }
  };
})( jQuery );