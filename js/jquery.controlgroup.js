(function( $ ) {
	
  $.fn.controlgroup = function(cnf) {
	 this.addClass("ecplus-control-group");
	  
	  var originalVal = $.fn.val;
	  $.fn.idx = function(value) {
		  if(this[0].tagName == "SELECT")
		  {
			return this[0].selectedIndex;  
		  }	
		  else
		  {
			  ret = null;
			  $('input', this).each(function (idx, ele)
			  {
				  if(ele.checked) ret = idx;
			  });
			  return ret;
		  }
	  }
	  
	  $.fn.val = function(value) {

		if(this.hasClass("ecplus-control-group") && this[0].tagName != "SELECT")
		{
			retval = [];
			$('input', this).each(function (idx, ele)
			{
				if(value)
				{
					//set
					if(ele.type == "radio")
					{
						if(value == ele.value || value == ele.getAttribute('labeltext'))
							ele.checked = "checked";
						else
							ele.checked = "";
					}
					else if(ele.type == "checkbox")
					{
					
						if(value.match(new RegExp("(,|^)" + ele.value + "(,|$)")))
							ele.checked = "checked";
						else
							ele.checked = "";
					}
				}
				else
				{
					//get
					if(ele.type == "radio")
					{
						if(ele.checked)
						{
							retval = ele.value;
						}
					}
					else if(ele.type == "checkbox")
					{
						if(ele.checked)
                        {
							retval.push(ele.value);
                        }
					}				
				}
			});

            if( typeof retval == 'object' )
            {
                retval = retval.join(',');
            }
			return retval;
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
  }
})( jQuery );
