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

				var path = (eles.length > 0) ? eles[0].src : '';
				
				$(inputId).val(path.substr(path.lastIndexOf("/") + 1).replace(/^[a-z0-9\-_]+~tn~/i, ''));
			});
		});
    }
})( jQuery );