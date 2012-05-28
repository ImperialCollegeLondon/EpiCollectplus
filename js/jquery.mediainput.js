(function( $ ) {
	/**
	 * Expects to be called on an iFrame with and associated hidden input
	 */
	$.fn.mediainput = function(cnf) {
		var frame = this;
		this.one("load", false, function(evt) {
			frame.load(function(evt){
				
				var inputId = "#" + evt.target.id.replace("_iframe","");
				var eles = evt.target.contentDocument.getElementsByTagName("img");

				var path = eles.lenght > 0 ? eles[0].src : evt.target.contentDocument.getElementsByTagName("a")[0].href;
				
				$(inputId).val(path.substr(path.lastIndexOf("/") + 1));
			});
		});
    }
})( jQuery );