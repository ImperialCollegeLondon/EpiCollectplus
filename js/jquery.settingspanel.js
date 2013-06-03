(function( $ ) {
  $.fn.graphPanel = function(cnf, arg) {
	  
	  if(cnf == "expand")
	  { 
		  this.clearQueue();
		  $(".ecplus-settingspanel", this).animate({left : "5%"});
		  this[0].expanded = true;
	  }
	  else if(cnf == "collapse")
	  {
		  if(this[0].expanded){
			  this.clearQueue();
			  $(".ecplus-settingspanel", this).animate({left : "95%"});
			  this[0].expanded = false;
		  }
	  }
	  else if(cnf == "toggle")
	  {

		  if(this[0].expanded){
			  this.graphPanel("collapse");
		  }
		  else
		  {
			  this.graphPanel("expand");
		  }
	  }
	  else if(cnf == "get")
	  {
		  return $("form", this).serializeArray();
	  }
	  else
	  {
		  this[0].expanded = false;
		  
		  this.append("<div class=\"ecplus-settingspanel\"></div><div class=\"ecplus-pane\"> </div><img class=\"minmax\" src=\"" + SITE_ROOT + "/images/glyphicons/glyphicons_215_resize_full.png\" />");
		  //this.css("position", "relative");
		  //$(".ecplus-settingspanel", this).append("<div class=\"ecplus-expansionbar\">&lt;&lt;</div>");
		  //$(".ecplus-settingspanel", this).height(this.height);
		  //$(".ecplus-settingspanel .ecplus-expansionbar", this).click(function(evt){$(evt.target.parentNode.parentNode).graphPanel("toggle");});
		  
		  if(cnf.form)
		  {
			  var frm = $(".ecplus-settingspanel", this).append("<form>" + cnf.form + "</form>");
			  $('.toggle', frm).buttonset();
		  }
		  
		  $(".ecplus-settingspanel", this).append("<a class=\"button\" href=\"#\">Draw Graph</a>");
		  $(".ecplus-settingspanel .button", this).button();	
		  
		  $("a", this).click(function(evt){
			 var node = evt.target.parentNode.parentNode.parentNode;
			 var data = $(node).graphPanel("get");
			 var l = data.length;
			 var field = "";
			 var graphType = "";
			 
			 for(var i = l; i--;)
			 {
				 if(data[i].name == "field")
					 field = data[i].value;
				 else if(data[i].name == "chartType")
					 graphType = data[i].value;
			 }
			 
			 drawGraph("#" + $(node).attr("id") + " .ecplus-pane", field, graphType);
			 $(node).graphPanel("collapse");
		  });
	  }
  };
  })( jQuery );