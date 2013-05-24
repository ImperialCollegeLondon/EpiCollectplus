(function( $ ) {
	$.fn.timepicker = function(cnf, val) {
		if( cnf == 'setTime' )
		{
			var i = 0;
			var t = val.split(':');
			
            this.val(val);
            
			$('.ecplus-timepicker-section input', this.parent()).each(function(idx, ele){
				if(t[i]) $(ele).val(t[i++]);
			});
            
		}
		else if( cnf == 'getTime' )
		{
            
			var time = '';
			$('.ecplus-timepicker-section input', this.parent()).each(function(idx, ele){
				if(time == '')
				{
					time = $(ele).val();
				}
				else
				{
					time = time + ':' + $(ele).val();
				}
			});
			return time;
		}
		else
		{
			this.hide();
			this.after('<div id="' + this.attr('id') + '_time_picker" class="ecplus-timepicker">');
			  
			var divId = '#' + this.attr('id') + '_time_picker';
			this.div = $(divId);
			
			var fmt = cnf.format.split(':');
			for(var i = 0; i < fmt.length; i++)
			{
				createElement(fmt[i], this.div, this);
			}
			
			var ctx = this;
			
			$('input:last', this.div).blur(function(evt){
				ctx.blur();
			});
		}
		
		function createElement(type, jq, originalInput)
		{
			jq.append('<div class="ecplus-timepicker-section"><input type="text" class="ecplus-timepicker-section-number" value="00" maxlength="2" /><div class="ecplus-timepicker-section-up"></div><div class="ecplus-timepicker-section-down"></div></div>')
		  
			jq = $('.ecplus-timepicker-section', jq);
			jq = $(jq[jq.length -1 ]);
			
			$('.ecplus-timepicker-section-up', jq).click(function(evt){
				var sec = $(evt.target.offsetParent);
				
				var ctrl = $('input', sec);
				ctrl.val((Number(ctrl.val()) + 1).toString().padLeft(2, '0'));
				ctrl.change();
				
			});
			$('.ecplus-timepicker-section-down', jq).click(function(evt){
				var sec = $(evt.target.offsetParent);
				
				var ctrl = $('input', sec);
				ctrl.val((Number(ctrl.val()) - 1).toString().padLeft(2, '0'));
				ctrl.change();
				
			});
			
			if( type == 'hh' )
			{
				$('input', jq).attr('title', 'Hours (12-hour)');
				$('input', jq).change(function(evt)
				{
					var ele = $(evt.target);
					
					var val  = Number(ele.val());
					if( isNaN(val) || val < 0 )
					{
						ele.val('00');
					}
					else if ( val > 11 ) 
					{
						ele.val('11');
					}
					else if ( ele.val().length < 2 )
					{
						ele.val( ele.val().padLeft(2,'0'));
					}
					$(originalInput).val($(originalInput).timepicker('getTime'));
					$(originalInput).trigger({
						type : 'changed'
					});
				});
			}
			else if( type == 'HH' )
			{
				$('input', jq).attr('title', 'Hours (24-hour)');
				$('input', jq).change(function(evt)
				{
					var ele = $(evt.target);
					
					var val  = Number(ele.val());
					if( isNaN(val) || val < 0 )
					{
						ele.val('00');
					}
					else if ( val > 23 )
					{
						ele.val('23');
					}
					else if ( ele.val().length < 2 )
					{
						ele.val( ele.val().padLeft(2,'0'));
					}
										
					$(originalInput).val($(originalInput).timepicker('getTime'));
					$(originalInput).trigger({
						type : 'changed'
					});
				});
		  	}
			else if( type == 'mm' )
			{
				$('input', jq).attr('title', 'Minutes');
				$('input', jq).change(function(evt)
				{
					var ele = $(evt.target);
					
					var val  = Number(ele.val());
					if( isNaN(val) || val < 0 )
					{
						ele.val('00');
					}
					else if ( val > 59 )
					{
						ele.val('59');
					}
					else if ( ele.val().length < 2 )
					{
						ele.val( ele.val().padLeft(2,'0'));
					}
					$(originalInput).val($(originalInput).timepicker('getTime'));
					$(originalInput).trigger({
						type : 'changed'
					});
				});
			}
			else if( type == 'ss')
			{
				$('input', jq).attr('title', 'Seconds');
				$('input', jq).change(function(evt)
				{
					var ele = $(evt.target);
					
					var val  = Number(ele.val());
					if( isNaN(val) || val < 0 )
					{
						ele.val('00');
					}
					else if ( val > 59 )
					{
						ele.val('59');
					}
					else if ( ele.val().length < 2 )
					{
						ele.val( ele.val().padLeft(2,'0'));
					}
					
					$(originalInput).val($(originalInput).timepicker('getTime'));
					$(originalInput).trigger({
						type : 'changed'
					});
				});
			}
	  
		}
	}
})( jQuery );