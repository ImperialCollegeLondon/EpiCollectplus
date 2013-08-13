var map;
var completed;
var succeeded;
var project;
var checker = new URLChecker();
var checking = {};
var nchecks = 0;
var formName = '';

if(!window['console'])
{
    window.console = {};    
}

if(!console.debug)
{
	if( console.info )
	{
		console.debug = console.info;
	}
	else
	{
		console.debug = function(msg){};
	}
}

checker.oncheck = function(evt)
{
	var jq = $('#' + checking[evt.url]);
	
	if(!evt.exists)
	{
		jq.replaceWith('<i>File not uploaded</i>');
	}
};

var baseUrl = (location.href.indexOf("?") > 0 ? location.href.substr(0, location.href.indexOf("?")) :location.href );
baseUrl = baseUrl.indexOf('#') > 0 ? baseUrl.substr(0, baseUrl.indexOf('#')) : baseUrl;

function generateUUID(){
    var d = new Date().getTime();
    var uuid = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
        var r = (d + Math.random()*16)%16 | 0;
        d = Math.floor(d/16);
        return (c=='x' ? r : (r&0x7|0x8)).toString(16);
    });
    return uuid;
};

EpiCollect = {};

EpiCollect.KEYWORDS = [
     'test', 'markers', 'images', 'js', 'css', 'ec', 'pc', 'create'
];

/**
 * Methods to validate entries into EpiCollect+ from the WebApp. Each method will take a value, paramaters in an object/dictionary and a callback. The callback will accept a dictionary containing a validation 
 * result and an array containing messages that describe any validation errors.
 *
 * @author Chris I Powell
 */
EpiCollect.Validators = {
    "required" : function(val, params, callback, ctrl_id)
    {
    	var obj ;
    	
        if(!!val)
        {
        	 obj = { valid : true, messages : [], control_id : ctrl_id, name : 'required' };
           
        }
        else
        {
             obj = { valid : false, messages : ['This field is required'], control_id : ctrl_id, name : 'required'  };
        }
        
        if ( callback ) 
        {
        	callback(obj);
        }
        else 
        {
        	return obj;
        }
        
    },
    "integer" : function(val, params, callback, ctrl_id)
    {
        var intval = Number(val);
        var obj = {};
        
        if(isNaN(intval))
        {
            obj = { valid : false, messages : ['is not a number'], name : 'integer', control_id : ctrl_id  };
        }
        else if( intval % 1 !== 0 )
        {
            obj = { valid : false, messages : ['is not an integer'], name : 'integer', control_id : ctrl_id   };
        }
        else
        {
            obj = { valid : true, name : 'integer', messages :[], control_id : ctrl_id };
        }
   
        if ( callback ) 
        {
        	callback(obj);
        }
        else 
        {
        	return obj;
        }
    },
    "double" : function(val, params, callback, ctrl_id)
    {
        var dblval = Number(val);
        var obj = {};
        
        if(isNaN(dblval))
        {
            obj = { valid : false, messages : ['is not a number'], name : 'double', control_id : ctrl_id   };
        }
        else
        {
            obj = { valid : true, messages :[], name : 'double', control_id : ctrl_id  };
        }
        
        if ( callback ) 
        {
        	callback(obj);
        }
        else 
        {
        	return obj;
        }
    },
    "date" : function(value, params, callback, ctrl_id)
    {			
        var fmt = params.fmt;
        var obj = {};
    
        var day = null;
        var month = null;
        var year = null;
        
        var msgs = [];
        
        for( var i = 0; i < fmt.length; i++ )
        {
            if(fmt[i] === "d")
            {
                if(fmt[i+1] === "d")
                {
                    //console.debug(value.substr(i,2))
                    day = Number(value.substr(i,2));
                    if(isNaN(day)) msgs.push("Day is not a number");
                    i++;
                }
                else
                {
                    msgs.push( "Invalid date format");
                }
            }
            else if( fmt[i] === "M" )
            {
                if( fmt[i+1] === "M" )
                {
                    //console.debug(value.substr(i,2))
                    month = Number(value.substr(i,2));
                    if(isNaN(month)) msgs.push("Month is not a number");
                    i++;
                }
                else
                {
                	msgs.push( "Invalid date format");
                }
            }
            else if( fmt[i] === "y" )
            {
                if( fmt[i+1] === "y" && fmt[i+2] === "y" && fmt[i+3] === "y" )
                {
                    year = Number(value.substr(i,4));
                    if(isNaN(year)) msgs.push("Year is not a number");
                    i+=3;
                }
                else
                {
                	msgs.push( "Invalid date format");
                }
            }
            
        }
        //console.debug('Day = ' + day)
        if(day || day === 0)
        {
            if(day < 1 || day > 31)	msgs.push("Day is out of range");
            else if(month && (month === 4 || month === 6 || month === 9 || month === 11) && day > 30) msgs.push("Day is out of range");
            else if(month && month === 2 && day > 29 && (year % 4 === 0 && (year % 100 !== 0 || year % 400 === 0))) msgs.push("Day is out of range");
            else if(month && month === 2 && day > 28) msgs.push("Day is out of range");
        }
        //console.debug('Month = ' + month)
        if(month || month === 0)
        {
            if(month < 1 ||  month > 12) msgs.push("Month is out of range");
        }
        
        obj = { valid : msgs.length == 0, messages : msgs, name : 'date', control_id : ctrl_id  };
        
        if ( callback ) 
        {
        	callback(obj);
        }
        else 
        {
        	return obj;
        }
    },
    "time" : function(value, params, callback, ctrl_id)
    {
        var fmt = params.fmt;
    
        var hours = null;
        var minutes = null;
        var seconds = null;
        
        var msgs = [];
        
        for( var i = 0; i < fmt.length; i ++ )
        {
            if( fmt[i] === "H" )
            {
                if( fmt[i+1] === "H" )
                { 
                    hours = Number(value.substr(i, 2));
                    if(isNaN(hours)) msgs.push("Hours are not a number");
                    if(hours < 0 || hours > 23) msgs.push("Hours out of range");
                    i++;
                    
                }
                else
                	msgs.push("Time Format is invalid");
                    
            }
            else if( fmt[i] === "m" ) 
            {
                if( fmt[i+1] === "m" )
                {
                    minutes === Number(value.substr(i,2));
                    if(isNaN(minutes)) msgs.push("Minutes are not a number");
                    if(minutes < 0 || minutes > 59) msgs.push("Minutes out of range");
                    i++;
                }
            }
            else if( fmt[i] === "s" )
            {
                if( fmt[i+1] === "s" )
                {
                    seconds === Number(value.substr(i,2));
                    if(isNaN(seconds)) msgs.push("Seconds are not a number");
                    if(seconds < 0 || seconds > 59) msgs.push("Seconds out of range");
                    i++;
                }
            }
        }
        
        var obj = { valid : msgs.length == 0, messages : msgs, name : 'time', control_id : ctrl_id  };
        
        if ( callback ) 
        {
        	return callback(obj);
        }
        else 
        {
        	return obj;
        }
    },
    "regex" :function(value, params, callback, ctrl_id)
    {
    	var obj = {};
        if(!(value.match(new RegExp(params.regex))))
        {
            obj = { valid : false, messages : [params.regexMessage ? params.regexMessage : "The value you have entered is not in the right format."], name : 'regex', control_id : ctrl_id  };
        }
        else
        {
            obj = { valid : true, messages : [], name : 'regex', control_id : ctrl_id  };
        }
        
        if ( callback ) 
        {
        	return callback(obj);
        }
        else 
        {
        	return obj;
        }
    },
    "max" : function(val, params, callback, ctrl_id)
    {
        var nval = Number(val);
        var obj = {};
       
        if(isNaN(nval))
        {
        	obj = { valid : false, messages : ['value must be a number less than or equal to ' + params.max] , name : 'max', control_id : ctrl_id  };
        }
        else if(nval > Number(params.max))
        {
            obj = { valid : false, messages : ['value must be a number less than or equal to  ' + params.max] , name : 'max', control_id : ctrl_id  };
        }
        else
        {
            obj = { valid : true, messages : [''], name : 'max', control_id : ctrl_id  };
        }
        
        if ( callback ) 
        {
        	return callback(obj);
        }
        else 
        {
        	return obj;
        }
        
    },
    "min" : function(val, params, callback, ctrl_id)
    {
    	var nval = Number(val);
    	var obj = {};
    	
    	if(isNaN(nval))
        {
        	obj = { valid : false, messages : ['value must be a number greater than or equal to  ' + params.max] , name : 'max', control_id : ctrl_id  };
        }
        else if(nval < Number(params.min))
        {
            obj = { valid : false, messages : ['value must be a number greater than or equal to  ' + params.min] , name : 'min', control_id : ctrl_id };
        }
        else
        {
            obj = { valid : true, messages : [''], name : 'min' , control_id : ctrl_id };
        }
        
        if ( callback ) 
        {
        	return callback(obj);
        }
        else 
        {
        	return obj;
        }
    },
    "match" : function(val, params, callback, ctrl_id)
    {
        var matchStr = params.parentValue.match(new RegExp(params.regex))[0];
        var valStr = value.match(new RegExp(params.regex))[0];
		var obj = {};
		if(valStr !== matchStr)
        {
            obj = { valid : false, messages : ["The value does not match the string from the parent field"], name : 'match', control_id : ctrl_id  };
        }
        else
        {
            obj = { valid : true, messages : [''], name : 'match', control_id : ctrl_id  };
        }
		
		if ( callback ) 
        {
        	callback(obj);
        }
        else 
        {
        	return obj;
        }
    },
    "verify" : function(value, params, callback, ctrl_id)
    {
        $("#" + this.id).hide();

        var obj = {};
        EpiCollect.prompt({ content : "Please re-enter the value for " + this.text + " to confirm the value", callback : function(newvalue){
            if( newvalue !== value )
            {
                callback({ valid : false, messages : ['the two entered values did not match, please try again.'], name : 'verify', control_id : ctrl_id  });
            }
            else
            {
               callback({ valid : true, messages : [], name : 'verify', control_id : ctrl_id  });
            }
        }});
    },
    "key" : function(value, params, callback, ctrl_id)
    {

        $.ajax({
            url : params.url + '?' + params.field + '=' + value,
            success : function(data,status,xhr)
            {
                if(data.length > 0)
                {
                    callback({ valid : false, messages : ["This field must be unique, the value " + value + " had already been saved for this form."], name : 'key', control_id : ctrl_id  });
                }
                else
                {
                    callback({ valid : true, messages : [], name : 'key', control_id : ctrl_id  });
                }
            }
        });
    },
    "fk" : function(value, params, callback, ctrl_id)
    {
        $.ajax({
            url : params.url + '?term=' + value + '&validate=true',
            success : function(data, status, xhr)
            {
                var res = JSON.parse(data);
                if(res.valid)
                {
                   callback({ valid : true, messages : [], name : 'fk', control_id : ctrl_id  });
                }
                else
                {
                    callback({ valid : false, messages : ['the parent entry could not be found'], name : 'fk', control_id : ctrl_id  });
                }
            }
        });
    }
};

if (!String.prototype.encodeXML) {
  String.prototype.encodeXML = function () {
    return this.replace(/&/g, '&amp;')
               .replace(/</g, '&lt;')
               .replace(/>/g, '&gt;')
               .replace(/"/g, '&quot;');
  };
}

if (!String.prototype.decodeXML) {
  String.prototype.decodeXML = function () {
    return this.replace(/&amp;/g, '&')
               .replace(/&lt;/g, '<')
               .replace(/&gt;/g, '>')
               .replace(/&quot;/g, '"');
  };
}


/**
 * Function to produce a dialog box.
 * 
 * @argument {object} conf {message, buttons, title}
 */
EpiCollect.dialog = function(conf)
{
	var diajq = $('#ec_dialog');
	if(diajq.length === 0)
	{
		$(document.body).append('<div id="ec_dialog"></div>');
		diajq = $('#ec_dialog');
	}
	diajq.hide();
	diajq.html(conf.content);
  
	if(conf.title) diajq.attr('title', conf.title);
	else diajq.attr('title', 'EpiCollect+ Message');
	
	if(!conf.buttons)
	{
		diajq.dialog({
			modal : true,
			buttons: {
				'OK' : function(){
					$( this ).dialog("close");
				}
			},
            closeOnEscape: false,
			resizable : false
		});
	}
	else
	{
		diajq.dialog({
			modal : true,
			buttons: conf.buttons,	
            closeOnEscape: false,
			resizable : false
		});
	} 
    
    if(conf.closeable === false){
        diajq.parent().addClass('uncloseable');
    }
    
};

EpiCollect.toJSTimestamp = function(val){
    var value = Number(val);
    while((Math.log(value) / Math.log(10)) < 12)
    {
        value = value * 10;
    }
    return value;
}

EpiCollect.ShowPrivacy = function()
{
    console.debug('show privacy')
    var html = "<p>EpiCollect plus will place one cookie in your browser labelled PHPSESSID. The only reason we do this is to track whether you are logged in or not.</p><p>EpiCollect does not collect data for commercial purposes.</p>";
    EpiCollect.dialog({ content : html });
}

EpiCollect.prompt = function(conf)
{
	var diajq = $('#ec_dialog');
	if(diajq.length === 0)
	{
		$(document.body).append('<div id="ec_dialog"></div>');
		diajq = $('#ec_dialog');
	}
	diajq.hide();
	diajq.html(conf.content);

    
	if(!conf.form)
	{
		diajq.append('<br /><input type="text" style="width: 80%" name="ecplus-dialog-input" />');
	}
	else
	{
		diajq.append(conf.form);
	}
	
	if(conf.title) diajq.attr('title', conf.title);
	else diajq.attr('title', 'EpiCollect+ Prompt');
	
	if(!conf.buttons)
	{
		diajq.dialog({
			modal : true,
			buttons: {
				'OK' : function(){
					var val = $( 'input', this ).val();
					$( this ).dialog('close');
					conf.callback(val);
				},
				'Cancel' : function(){
					$( this ).dialog('close');
				}
			},
			resizable : false,
			width : 'auto'
		});
	}
	else
	{
		diajq.dialog({
			modal : true,
			buttons: conf.buttons,	
			resizable : false,
			width : 'auto'
		});
	}
	if(conf.closeable === false){
 
        diajq.parent().addClass('uncloseable');
    }
	$('.toggle').buttonset();
};

EpiCollect.LoadingOverlay = function()
{
	var message = "Loading ...";
	var bdy = $(document.body);
	var size = Math.min(window.innerWidth, window.innerHeight) * 0.6;	
	var drawer = null;
	
	bdy.append("<div id=\"ecplus_loader_bg\" style=\"display:none\"><canvas id=\"ecplus_loader\" width=\""+size+"\" height=\""+size+"\" >Loading...</canvas></div>");
	
	var ctx;
	var step = (2 * Math.PI) / 300;
	var i = 0;
	try{
		ctx = $("#ecplus_loader")[0].getContext('2d');
		ctx.translate(size/2, size/2);
		ctx.font = "18pt sans-serif";
	}
	catch(err)
	{
		$('.ecplus_loader canvas').replaceWith('<img src="../images/loading.gif">');
	}
	
	
	this.setMessage = function (msg)
	{
		message = msg;
		//$('#ecplus_loader p').text(message);
	};
	
	this.start = function()
	{
		size = Math.min(window.innerWidth, window.innerHeight, 800) * 0.4;	
		
		$("#ecplus_loader").css({"width"  : size + "px", "height" : size + "px", "position" : "fixed", "top" : "50%", "left" : "50%", "margin-left" : "-" + (size/2) + "px", "margin-top" : "-" + (size/2) + "px", "border-radius" :"20px"});
		$("#ecplus_loader_bg").show();
		try{
			ctx = $("#ecplus_loader")[0].getContext('2d');
			drawer = setInterval(this.draw, 10);
		}catch(err){}
	};
	
	this.draw = function()
	{
		if(!ctx) return;
		
		var d = size;
		var halfd = d/2;
		ctx.clearRect(-halfd, -halfd, d, d);
		ctx.save();
		ctx.beginPath();
		
		var ts = ctx.measureText(message).width;
		ctx.fillText(message, -ts/2, 0);
		ctx.rotate(step * (++i % 300));
		
		ctx.lineWidth = 5;
		
		var tts = ts * 0.8;
		ctx.arc(0, 0, tts, 0, Math.PI * 0.5);
		ctx.stroke();
		
		ctx.beginPath();
		ctx.arc(0, 0, tts, Math.PI, Math.PI * 1.5);
		ctx.stroke();
		
		ctx.beginPath();
		ctx.strokeStyle = "rgba(255,255,255,1)";
		ctx.lineWidth = 10;
		ctx.arc(0, -10, tts, 0, Math.PI * 0.5);
		ctx.stroke();
		
		ctx.beginPath();
		ctx.lineWidth = 10;
		ctx.arc(0, 10, tts, Math.PI, Math.PI * 1.5);
		ctx.stroke();
		
		ctx.restore();
	};
	
	this.stop = function()
	{
		clearInterval(drawer);
		$("#ecplus_loader_bg").hide();
	}
	;
};

String.prototype.pluralize = function(str)	
{
	if(str[str.length-1] !== "s")
	{
		str += "s";
	}
	return str;
};

String.prototype.padLeft = function(length, c)	
{
	var str = this;
	while(str.length < length) { str = c + str; }
	return str;
};

String.prototype.padRight = function(length, c)	
{
	var str = this;
	while(str.length < length) { str = str + c; }
	return str;
};


String.prototype.trimChars = function(chars)
{
	// Extends the string class to incluide the trim method.
	var str = this;
	if(chars)
	{
		for(var c = 0; c < chars.length; c++)
		{
            
			if(chars[c] === this.charAt(0))
			{
				str = str.substr(1);
			}
			if(chars[c] === str.charAt(str.length -1))
			{
				str = str.substr(0, str.length - 1);
			}
				
		}
	}
	else
	{
		str = str.replace(/^\s+/gi, '').replace(/\s+$/gi, '');
	}
	return str.toString();
};


Date.prototype.format = function(fmt)
{
	var months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
	
	return fmt.replace("dd", this.getDate())
		.replace("MM" , months[this.getMonth()])
                .replace("Mth" , this.getMonth())
		.replace("yyyy", this.getFullYear())
		.replace("HH", this.getHours().toString().padLeft(2, "0"))
		.replace("hh", (this.getHours() % 12).toString().padLeft(2, "0"))
		.replace("mm", this.getMinutes().toString().padLeft(2, "0"))
		.replace("ss", this.getSeconds().toString().padLeft(2, "0"));
};

EpiCollect.parseDate = function(dateString)
{
    if(!dateString) return null;
    var date_time = dateString.split(' ');
    var date = date_time[0].split('-');
    var time = date_time[1].split(':');
    
    return new Date(date[0], date[1], date[2], time[0], time[1], time[2]);
};

EpiCollect.server_format = function(d)
{
    return d.format('yyyy-Mth-dd HH:mm:ss');
};

if(!Object.keys)
{
	Object.keys = function(obj)
	{
		var arr = [];
		for(key in obj)
		{
			if(key !== 'keys')
			{
				arr.push(key);
			}
		}
		return arr;
	};
	
}


EpiCollect.onload = null;

// url should point to an XML doc
EpiCollect.loadProject = function(turl, callback)
{
	if(callback) EpiCollect.onload = callback;
	if(turl.match(/\.xml$/))
	{
		$.ajax({
			url : turl,
			success : EpiCollect.loadProjectCallback
		});
	}
	else
	{
		throw "Project must be loaded from an XML file";
	}
};

EpiCollect.loadProjectCallback = function(xhr)
{
	project = new EpiCollect.Project();
	project.parse(xhr);
	if(EpiCollect.onload) EpiCollect.onload(project);
};

function createHandler(obj, func)
{
	return (function(e, f){obj[func](e, f);});
}

EpiCollect.Project = function()
{
    this.forms = {};
    this.localUrl = '';
    this.remoteUrl = '';
    this.name = '';
    this.id = '';
    this.allowEdits = false;
    this.version = 0.0;
    this.map;
	this.description = "";
	
	this.getNextForm = function(name)
	{
		var n = Number(this.forms[name].num) + 1;
		var tbl = false;

		for(var t in this.forms)
		{
			if(Number(this.forms[t].num) === n)
			{
				tbl = this.forms[t];
				break;
			}
		}
		return tbl;
	};
	
	this.getPrevForm = function(tblName)
	{
            //if(!this.forms[tblName]) return null
		var n = Number(this.forms[tblName].num) - 1;
		var tbl = false;
		
		for(var t in this.forms)
		{
			if(Number(this.forms[t].num) === n)
			{
				tbl = this.forms[t];
				break;
			}
		}
		return tbl;
	};
	
	this.validateFormName = function(name)
	{
		if(this.forms[name] && this.forms[name].num > 0) return "There is already a form called " + name + " in this project" ;
		var kw = EpiCollect.KEYWORDS;
		
		for(var i = kw.length; kw--;)
		{
            if (name === kw[i])
            {
                return name + " cannot be user as a form name, other words that cannot be used are : " + EpiCollect.KEYWORDS.join(', ');
            }
		}
                    
        if(name.match(/\s/gi)) return "The form name cannot contain spaces";
        if(name.match(/[^A-Z0-9_-]/gi)) return "The form name can only contain letter, numbers and _ or -";
        return true;
	};
	
	/**
	 * 
	 * @param form {EpiCollect.Form} The form the field belongs to
 	 * @param field {EpiCollect.Field} The field having it's name validated 
	 * @param newName {String} Optional, if the field name hasn't been assigned to the field yet, then use this parameter to check it 
	 * @returns true or a message describing an error
	 */
	this.validateFieldName = function(form, field, newName)
	{
		
        var name = newName ? newName : field.id; 
        if(name === "") return 'The field ID cannot be blank';
		if(form.fields[field.id] && form.fields[name].index != field.index)
		{
			return '<em>' + name + '</em> is not a valid ID. There is already a field called ' + name + ' in this form';
		}
		
		var kw = EpiCollect.KEYWORDS;
		
		for(var i = kw.length; kw--;)
		{
			if (name === kw[i]) return name + " cannot be user as a field ID. Other words that cannot be used are : " + EpiCollect.KEYWORDS.join(', ');
		}
        
        if(name === form.name) return '<em>' + name + '</em> is not a valid ID. The field name cannot be the same as the form name';
		if(name.match(/\s/gi)) return '<em>' + name + '</em> is not a valid ID. The form name cannot contain spaces';
        if(name.match(/[^A-Z0-9_]/gi)) return '<em>' + name + '</em> is not a valid ID. The field ID can only contain letter, numbers and underscores';
                
		return true;
	};
	
    this.parse = function(xml)
    {
		keys = {};
		
        var mdl = xml.getElementsByTagName('model')[0];
        var sub = mdl.getElementsByTagName('submission')[0];
        this.id = sub.getAttribute('id');
        this.name = sub.getAttribute('projectName');
		
        this.allowEdits = sub.getAttribute('allowDownloadEdits') === 'true';
        this.version = sub.getAttribute('versionNumber');
		
		var localServers = mdl.getElementsByTagName('uploadToLocalServer');
		if(localServers.length > 0){
			this.localUrl = localServers[0].firstChild.data;
		}
		if(mdl.getElementsByTagName('uploadToServer').length> 0) this.remoteUrl = mdl.getElementsByTagName('uploadToServer')[0].firstChild.data;
        
		var desc = xml.getElementsByTagName('description');
		if(desc && desc.length > 0)
		{
			this.description = desc[0].firstChild.data;
		}
		
        var tbls = xml.getElementsByTagName('table');
		if (tbls.length === 0)
		{
			tbls = xml.getElementsByTagName('form');
		}
        for(var i = 0 ; i < tbls.length; i++){
            var frm = new EpiCollect.Form();
            frm.parse(tbls[i]);

            this.forms[frm.name] = frm;
			keys[frm.key] = frm.name;
			if(this.getPrevForm(frm.name)){
				t = this.getPrevForm(frm.name);
				t.cols.push(frm.name + "Entries");
			}
        }
		for( tbl in this.forms )
		{
			var branches = this.forms[tbl].branchForms;
			for( var i = 0; i < branches.length; i ++ )
			{
				this.forms[branches[i]].branchOf = tbl;
			}
		}
	};
		
	this.draw = function(div)
	{
		for( tbl in this.forms )
		{
			$(div).append("<p>" + tbl + "</p>");
		}
	};
	
	this.toXML = function()
	{
		var xml = "<?xml version=\"1.0\"?><ecml><model><submission id=\"" + this.id + "\" projectName=\"" + this.name + "\" allowDownloadEdits=\"" + (this.allowEdits ? "true" : "false") +"\" version=\"" + this.version + "\" /></model>";
		for( form in this.forms )
		{
			xml = xml + this.forms[form].toXML();
		}
		xml = xml + "</ecml>";
		return xml;
	};
};

EpiCollect.Form = function()
{
	
	/**
	 * Project that this form is part of
	 * @author cpowell 
	 */
	this.project = false;
    this.fields = {};
    this.num = 0;
    this.name = '';
    this.key = '';
    this.titleFields = [];
    this.cols = [];
	this.main = true;
	
	this.filterField = false;
	this.filterValue = false;
	this.searchValue = false;
	this.branchOf = false;
	this.branchForms = [];
	this.groupForms = [];
    this.store = {};

	this.hasMedia = false;
	this.gpsFlds = [];
	
	this.deletedBranches = [];
	
	this.pendingReqs = [];
	
    this.parse = function(xml)
    {
        var tblData = xml.getElementsByTagName('table_data')[0];
		if(tblData){
			this.name = tblData.getAttribute('table_name');
			this.num = tblData.getAttribute('table_num');
			this.key = tblData.getAttribute('table_key');
		}
		else
		{
			this.name = xml.getAttribute('name');
			this.num = xml.getAttribute('num');
			this.key = xml.getAttribute('key');
			if(xml.getAttribute('main')) this.main = xml.getAttribute('main') === "true";
		}
        
		this.fld = new EpiCollect.Field();
		this.fld.id = "created";
		this.fld.form = this;
		this.fld.text = "Time Created";
		this.fields[this.fld.id] = this.fld;
		this.cols.push("created");
		
		this.fld = new EpiCollect.Field();
		this.fld.id = "uploaded";
		this.fld.form = this;
		this.fld.text = "Time Uploaded";
		this.fields[this.fld.id] = this.fld;
		this.cols.push("uploaded");
		
		this.fld = new EpiCollect.Field();
		this.fld.id = "lastEdited";
		this.fld.form = this;
		this.fld.text = "Last Updated";
		this.fields[this.fld.id] = this.fld;
		this.cols.push("lastEdited");
		
		this.fld = new EpiCollect.Field();
		this.fld.id = "DeviceID";
		this.fld.text = "Device ID";
		this.fld.form = this;
		this.fields[this.fld.id] = this.fld;
		this.cols.push("DeviceID");
		
		var f_idx = 0;
		
		for(var nd = 0; nd < xml.childNodes.length; nd++)
		{
			if(xml.childNodes[nd].nodeType === 1 && xml.childNodes[nd].nodeName !== "table_data")
			{
				var field = new EpiCollect.Field();
				field.parse(xml.childNodes[nd]);
				field.form = this;
				field.index = f_idx++;
				
				this.fields[field.id] = field;
				this.cols.push(field.id);
				
				if(keys[field.id])
				{
					field.fkField = field.id;
					field.fkTable = keys[field.id];
				}
				if(field.title)this.titleFields.push(field.id);
				if(field.type === "video" || field.type === "audio" || field.type === "photo")
				{
					this.hasMedia = true;
				}
				else if (field.type === "gps" || field.type === "location")
				{
					this.gpsFlds.push(field.id);
				}
				else if (field.type === "branch")
				{
					this.branchForms.push(field.connectedForm);
				}
				else if(field.type === "group")
				{
					this.groupForms.push(field.connectedForm);
				}

			}
		}
		
        if(this.key && this.fields[this.key])
        {
            this.fields[this.key].isKey = true;
            this.fields[this.key].required = true;
        }
    };
		
	this.nextMkr = function()
	{
		this.currentMkr++;
		if(this.currentMkr >= this.mkrs.length) this.currentMkr = 0;
		return this.mkrs[this.currentMkr];
	};
		
	this.setGroupField = function(grpField)
	{
		try{
			this.currentMkr = -1;
			this.grps = {};
			this.groupField = grpField;
		
			for(var i in this.markers)
			{
				var mkr = "redCircle";
				var gVal = this.markerData[i][this.groupField];
				if(this.grps[gVal])
				{
						mkr = this.grps[gVal];
				}
				else
				{
						mkr = this.nextMkr();
						this.grps[gVal] = mkr;
				}
				this.markers[i].setIcon("../images/mapMarkers/" + (mkr ? mkr : "redCircle") + ".png");
			}
			this.drawMapLegend();
		}catch(err){/*alert(err);*/}
	};
	
	this.drawMapLegend = function()
	{
		
		if(!map.controls[google.maps.ControlPosition.RIGHT_BOTTOM].length)
		{
			this.legend = document.createElement('div');
			this.legend.style.backgroundColor = '#EEEEEE';
			this.legend.style.padding = '5px 5px 5px 5px';
			this.legend.style.border = '1px solid #000000';
			var inner = document.createTextNode('Hello World');
			this.legend.appendChild(inner);
			map.controls[google.maps.ControlPosition.RIGHT_BOTTOM].push(this.legend);
		}
		
		
		var i = 0;
		for(var grp in this.grps)
		{
			var legRow = document.createElement('div');
			var img = document.createElement('img');
			img.style.display = 'inline';
			img.src = "../images/mapMarkers/" + this.grps[grp] + ".png";
			legRow.appendChild(img);
			legRow.appendChild(document.createTextNode(grp));
			if(i < this.legend.childNodes.length)
			{
				this.legend.replaceChild(legRow, this.legend.childNodes[i]);
			}
			else
			{
				this.legend.appendChild(legRow);
			}
			i++;
		}
		while(i < this.legend.childNodes.length) this.legend.removeChild(this.legend.childNodes[i]);
	};

	this.removeAllMarkers = function()
	{
		for(var mkr in this.markers)
		{
			this.markers[mkr].setMap(null);
		}
		this.markers = {};
	};
	
	/**
	 * @author Chris I Powell
	 * 
	 *  JQuery function to render the EpiCollect+ form
	 *  
	 *  @param {Object} cnf [Optional] an object containing the entry data.
	 *  
	 */
	this.displayForm = function(cnf)//(ele, data, vertical, index, editMode )
	{
		if(!cnf) cnf = {};	
		var ele = cnf.element;
		var data = cnf.data;
		var vertical = cnf.vertical;
		var index = cnf.index;
		var editMode = cnf.edit;
		var debug  = cnf.debug;
		
		if(index === undefined)
			this.formIndex = 0;
		else
			this.formIndex = index+1;
			
		if(data) data["updated"] = new Date().getTime().toString();
		
		var popup = false;
		if(typeof ele === "string" && ele[0] !== "#" && ele[0] !== ".")
		{
			ele  = "#" + ele; 
		}
		else if(typeof ele === "object")
		{
			ele = "#" + ele.id;
		}	
		else
		{
			$(document.body).append("<div id=\"ecplus-form-" + this.name + "\"></div>");
			ele = "#ecplus-form-" + this.name;
			popup = true;
		}
		
		this.formElement = $(ele);

		var frm = this;
		var w = window.innerWidth ? window.innerWidth * 0.45 : 300;
		var h = window.innerHeight ? window.innerHeight * 0.65 : 400;
		this.formElement.dialog({
			width: w,
			height: h,
			modal : true,
			resizable : true,
			title : (data ? "Edit " : "Add ") + this.name,
			close : function(event, ui)
			{
				while(frm.pendingReqs.length > 0)
				{
					frm.pendingReqs.pop().abort();
				}
				$(".ecplus-input", this.formElement).unbind("blur");
				$(event.target).remove();
			}
		});

		this.formElement
			.dialog("option", "title", "Add " + this.name)
			.empty()
			.attr("title", (editMode ? "Edit " : "Add ") + this.name)
			.addClass(vertical ? "ecplus-vertical-form" : "ecplus-form")
			.removeClass(vertical ? "ecplus-form" :"ecplus-vertical-form")
			.append("<div class=\"ecplus-form-next\"><a href=\"#\" onclick=\"project.forms['"+ this.name +"'].checkAndAdvance();\">Next</a></div>")
			.append("<div class=\"ecplus-form-previous\"><a href=\"#\" onclick=\"project.forms['"+ this.name +"'].movePrevious();\">Previous</a></div>")
			.append("<div class=\"ecplus-form-pane\"><form name=\"" + this.name + "\"></form></div>");
		
		if(cnf.debug) { this.formElement.addClass('debug'); }
		
		if(editMode) { this.formElement.addClass('editing'); } else { this.formElement.removeClass('editing'); }
		
		var form_ele = $(".ecplus-form-pane form", this.formElement).css("width", ($(".ecplus-question", this.formElement).width() * $(".ecplus-question", this.formElement).length + 1) + "px");

		
		for(var field in this.fields)
		{
			if(this.fields[field].type === "" || (this.fields[field].hidden && project.getPrevForm(this.name).key !== field))
			{
                form_ele.append("<div class=\"ecplus-question-hidden\" id=\"ecplus-question-" + field + "\"><label>" + this.fields[field].text + "</label></div>");
				$("#ecplus-question-" + field, this.formElement).append(this.fields[field].getInput(data ? data[field] : undefined, cnf.debug));
			}
			else
			{
                form_ele.append("<div class=\"ecplus-question\" id=\"ecplus-question-" + field + "\"><label>" + this.fields[field].text + "</label></div>");
				$("#ecplus-question-" + field, this.formElement).append(this.fields[field].getInput(data ? data[field] : undefined, cnf.debug));
				$("#ecplus-question-" + field, this.formElement).append("<div  id=\"" + field + "-messages\" class=\"ecplus-messages\"></div>");
				
			}
		}
		
		
		//$("form", this.formElement).append("<div class=\"ecplus-question\" id=\"ecplus-save-button\"><label></label><br /></div>");
		if(cnf.debug)
		{
            form_ele.append("<div class=\"ecplus-question\" id=\"ecplus-save-button\"><label></label><br /><a class=\"button\" href=\"javascript:project.forms['" + this.name +  "'].closeForm();\">End of Form, click here to close.</a></div>");
		}
		else if(editMode)
		{
            form_ele.append("<div class=\"ecplus-question\" id=\"ecplus-save-button\"><label></label><br /><a class=\"button\" href=\"javascript:project.forms['" + this.name +  "'].editEntry();\">Save Entry</a></div>");
		}
		else
		{
            form_ele.append("<div class=\"ecplus-question\" id=\"ecplus-save-button\"><label></label><br /><a class=\"button\" href=\"javascript:project.forms['" + this.name +  "'].addEntry();\">Save Entry</a></div>");
		}
		
		$(".ecplus-question").width($(".ecplus-form-pane").width());
        form_ele.css("width", ($(".ecplus-question").width() * $(".ecplus-question").length + 1) + "px");

		
		// TODO : Previously the idea was to set the type of the field to date and the use jQuery to augment the browsers that don't yet support
		// type=date. However as HTML 5 does nto support formats that doesn't work. That said we should look at whether the date should
		// be stored in-format or as unix timestamp/ISO format then displayed according to the locality settings of the browser/phone
		// NB this could be a setting to localStorage, as number of rows is.

        $("input.ecplus-datepicker", this.formElement).each(function(idx, ele) {
            if(!project.forms[formName].fields[ele.name]) return;

            var fmt = project.forms[formName].fields[ele.name].date;
            if(project.forms[formName].fields[ele.name].setDate)
            {
                fmt = project.forms[formName].fields[ele.name].setDate;
            }
            fmt = fmt.replace("MM", "mm").replace("yyyy", "yy");

            $(ele).datepicker({
                dateFormat : fmt,
                beforeShow : function(input, inst)
                {
                    $(input).off('blur');
                },
                onClose:function(input, inst)
                {
                    //$( input ).focusin();
                    /*$( input ).on('blur', function(evt)
                    {
                        if(!project.forms[formName].moveNext(true))
                        {
                            $(evt.target).focus();
                        }
                    });*/
                }

            });
            if(project.forms[formName].fields[ele.name].setDate)
            {
                $(ele).datepicker("setDate", new Date());
            }
        });

        $("input.ecplus-timepicker", this.formElement).each(function(idx, ele) {
            var fmt = project.forms[formName].fields[ele.name].time;
            var jq = $(ele);

            if(project.forms[formName].fields[ele.name].setTime)
            {
                fmt = project.forms[formName].fields[ele.name].setTime;
            }

            jq.timepicker({ format : fmt });
            if(project.forms[formName].fields[ele.name].setTime)
            {
                if(!data || !data[ele.name])
                {
                    console.debug(jq.attr('id'));
                    jq.timepicker("setTime", new Date().format(fmt));
                }
                else
                {
                    jq.timepicker("setTime", data[ele.name]);
                }
            }
        });

		if(this.gpsFlds.length > 0) $(".locationControl", this.formElement).each(function(idx, ele) { $( ele ).gpsPicker(); } );
		$(".ecplus-radio-group, .ecplus-check-group, select", this.formElement).controlgroup();
		$(".ecplus-media-input", this.formElement).mediainput();
		
		if(data)
		{
			for(var field in data)
			{
				if(data[field] === "NULL" || data[field] === "undefined") data[field] = "";
				$("#" + field, this.formElement).val(data[field]);
			}
		}
		
		$("select[childcontrol]", this.formElement).change(function(evt){
			var ctrl = $(evt.target);
			var child = $("#" + ctrl.attr("childcontrol"));
			child.attr('parentvalue', ctrl.val());
			child.attr('parentfield', ctrl.attr('id'));
		});

    
        $('.ecplus-form').keydown(function(e) { 
              var keyCode = e.keyCode || e.which; 

              if (keyCode === 9) { 
                e.preventDefault(); 
              }
        });
		
		
	
		$('.ecplus-ac').each(function(idx, ele)
		{
			var jq = $(ele);
			var pform = jq.attr('pform');
			jq.autocomplete({
				source : baseUrl+ "/../" + pform + "/title",
				minLength : 2,
				open : function(evt, ui)
				{
					 $(evt.target).off('blur');
				},
				close : function(evt, ui)
				{
					var ele = $(evt.target);
					ele.focusin();
				}
			});
		});
		if(editMode) { $('#' + this.key).prop('disabled' , true); } else  { $('#' + this.key).prop('disabled' , false); }
		this.jumpFormTo(this.formIndex);
	};
	
	this.closeForm = function()
	{
		//console.debug('closing...');
		while(this.pendingReqs.length > 0)
		{
			this.pendingReqs.pop().abort();
		}
		$(".ecplus-input", this.formElement).unbind("blur");
		this.formElement.dialog("close");
	};
    
    this.getCurrentControl = function()
    {
        var ctrlindex = this.formIndex;
        
        var field_id = $('.ecplus-input', $('.ecplus-question')[ctrlindex]).attr('id');
        
        return this.fields[field_id];
    };
    
    this.checkAndAdvance = function()
    {
        var field = this.getCurrentControl();
        if(field)
    	{
	        var control =  $('#' + field.id, this.formElement);
	   
	        control.off('valid'); //clear any handlers already present
	        
	        control.one('valid', {form : this}, function(evt) {
	            evt.data.form.moveNext();
	        });
	
	        field.validate(control.val(), (this.formElement.hasClass('debug') ? ['fk'] : undefined));
    	}
        else
        {
        	this.moveNext();
        }
    };
	
	this.doJump = function(fieldName, startField)
	{
            
		var start = this.formIndex;
		var done = false;
		
		if(startField)
		{
			start = $('#ecplus-question-' + startField).index();
		}

		//console.debug('start at : ' + start + ' -- ' + startField);
		
		var _frm = this;
		
		$(".ecplus-question, .ecplus-question-jumped").each(function(idx, ele){
			var fld = ele.id.replace("ecplus-question-", "");
			
			//console.debug(idx + ' :: ' + start)
			
			if( !startField && $(ele).hasClass('ecplus-question-jumped') && idx < start ) start++;
			
			if( idx <= start || !_frm.fields[fld] ) return;
			if( fld === fieldName ) done = true;
			
			if( !fieldName || done )
			{
			//	console.debug("show " + fld);
				$(ele).show();
				$(ele).addClass('ecplus-question');
				$(ele).removeClass('ecplus-question-jumped');
			}
			else if( !done )
			{
				//console.debug("hide " + fld);
				$(ele).val('');
				$(ele).hide();
				$(ele).removeClass('ecplus-question');
				$(ele).addClass('ecplus-question-jumped');
			}
			else
			{
				//console.debug("show " + fld);
				$(ele).show();
				$(ele).addClass('ecplus-question');
				$(ele).removeClass('ecplus-question-jumped');
			}
		});
		
		
	};
	
	this.jumpFormTo = function(idx)
	{
		this.formIndex = idx;
		$(".ecplus-form-pane").scrollLeft(idx * $(".ecplus-question").width());
	};
	
	this.moveFormTo = function(idx)
	{
		if( $(".ecplus-form").length === 0 ) return;
		
		if( window["interval"] ) clearInterval(interval);
		if( idx < 0 || idx > $(".ecplus-question").length )
		{
			this.formIndex = 0;
			return;
		}
		this.formIndex = idx;
		
		var step = $(".ecplus-question").width() / 15;
		
		interval = setInterval(function()
		{
			if(Math.abs($(".ecplus-form-pane").scrollLeft() - idx * $(".ecplus-question").width()) < step)
			{
				$(".ecplus-form-pane").scrollLeft(idx * $(".ecplus-question").width());
				clearInterval(interval);
			}
			
			if($(".ecplus-form-pane").scrollLeft() === idx * $(".ecplus-question").width())
			{
				clearInterval(interval);
			}
			else if($(".ecplus-form-pane").scrollLeft() < (idx * $(".ecplus-question").width()))
			{
				$(".ecplus-form-pane").scrollLeft($(".ecplus-form-pane").scrollLeft() + step);
			}
			else if($(".ecplus-form-pane").scrollLeft() > (idx * $(".ecplus-question").width()))
			{
				$(".ecplus-form-pane").scrollLeft($(".ecplus-form-pane").scrollLeft() - step);
			}
		}, 5);
		
	};
	
	this.moveNext = function(preventBlur)
	{
		try{
			if(this.formIndex === $('.ecplus-question').length - 1) return;
			//validate answer to previous question
			var fldName = $('.ecplus-question')[this.formIndex].id.replace("ecplus-question-", "");

            if(this.fields[fldName].jump)
            {
                var jumped = false; // is a jump required
                jbits = this.fields[fldName].jump.split(",");
                
                  console.debug(jbits);
                    
                for(var j = 0; j < jbits.length; j+=2)
                {
                    
                    if(Number(jbits[j+1]) === $("#" + this.fields[fldName].id, this.formElement).idx() || jbits[j+1].toLowerCase().trimChars() === 'all')
                    {
                        this.doJump(jbits[j]);
                        jumped = true;
                        break;
                    }
                }
                
                if(!jumped)
                {
                    this.doJump(false);
                }
            }
            
            this.formIndex++;
            this.moveFormTo(this.formIndex);
            return true;
			
		}catch(err){/*alert(err);*/}
	};
	
	this.movePrevious = function()
	{
		this.formIndex--;
		this.moveFormTo(this.formIndex);
	};
		
	this.getValues = function()
	{
		var vals = {};
		for(fld in this.fields)
		{
			if( $("#" + fld).parent().hasClass('ecplus-question-jumped') )
			{
				vals[fld] = '';
			}
			else
			{
				vals[fld] = $("#" + fld).val();
			}
		}
		return vals;
	};
	
	this.openBranch = function(branchName, rec)
	{
		var newEntry = !rec;
		if(newEntry)
		{
			var data = this.getValues();
			rec = {};
			rec[this.key] = data[this.key];
		}
		this.saveEntry();
		this.closeForm();
		project.forms[branchName].displayForm({data : rec});
		if(newEntry)
		{
			$("#" + this.key, project.forms[branchName].formElement).append('<option value="' + rec[this.key] + '" SELECTED>' + rec[this.key] + '</option>'  );
			$("#ecplus-question-" + this.key, project.forms[branchName].formElement).removeClass("ecplus-question").addClass("ecplus-question-hidden");
		}
	};
	
	this.closeForm = function()
	{
		while(this.pendingReqs.length > 0)
		{
			this.pendingReqs.pop().abort();
		}
		$(".ecplus-input", this.formElement).unbind("blur");
		this.formElement.dialog("close");
		if(this.branchOf === formName)
		{
			project.forms[formName].displayForm({ 
				data : project.forms[formName].getSavedEntry(),
				index: project.forms[formName].formIndex
			});
		}
		/*else
		{
			this.formElement.dialog("close");
		}*/
	};
	
	this.deleteEntry = function(key)
	{
		if(confirm("Are you sure you want to delete this entry?"))
		{
			$.ajax(baseUrl + "/" + key, {
				type : "DELETE",
				success : function()
				{
					getData();
				},
				error : function(xhr, err, statusText)
				{
					if(statusText.toUpperCase() === "CONFLICT")
					{
						EpiCollect.dialog({ content: 'cannot delete a record with child records.' });
					}
				}
			});
		}
	};
	
	this.addEntry = function()
	{
		if(this.branchOf)
		{
			this.saveBranch();
			this.closeForm();
			return;
		}
		
		var frm = this;
		$.ajax(this.name, {
			type : "POST",
			data : this.getValues(),
			success:function(data, status, xhr)
			{
				var obj = JSON.parse(data);
				if(obj.success)
				{
					if(window["entryQueue"])
					{
						for(var qfrm in entryQueue)
						{
							var queue = entryQueue[qfrm];
							var len = queue.length;
							
							if(!window.branches) window.branches = 0;
							window.branches += len;
							
							for(var e = len; e--;)
							{
								$.ajax(qfrm, {
									type : "POST",
									data : queue[e],
									success : function(data, status, xhr){
										var obj = JSON.parse(data);
										
										if(!--window.branches)
										{
											$(frm.formElement).dialog("close");
											getData();
										}
									}
								});
							}
						}
					}
					else
					{
						$(frm.formElement).dialog("close");
						getData();
					}
				}
				else
				{
					EpiCollect.dialog({content : obj.msg});
				}
			},
			error:function()
			{
				EpiCollect.dialog({content : "Add request failed"});
			}	
		});
	};
	
	this.saveEntry = function()
	{
		if(!localStorage) localStorage = {};
		localStorage[project.name + "_" + this.name] = JSON.stringify(this.getValues());
	};
	
	this.getSavedEntry = function()
	{
		var ent = localStorage[project.name + "_" + this.name];
		if(typeof ent === "string" && ent !== "undefined") ent = JSON.parse(ent);
		else ent = {};
		return ent;
	};
	
	this.saveBranch = function()
	{
		if(!window["entryQueue"]) entryQueue = {};
		if(!entryQueue[this.name]) entryQueue[this.name] = [];
		entryQueue[this.name].push(this.getValues());
		
		var ent = project.forms[formName].getSavedEntry();
		
		var flds = project.forms[formName].fields;
		
		for( f in flds )
		{
			if( flds[f].connectedForm === this.name ) {
				if(ent[f]){
					ent[f]++;
				}
				else
				{
					ent[f] = 1;
				}
			}	
		}
		
		localStorage[project.name + "_" + formName] = JSON.stringify(ent);
	};
		
	this.editEntry = function()
	{
		if( this.branchOf === formName )
		{
			this.saveBranch();
			this.closeForm();
			return;
		}
		
		vals = this.getValues();
		vals["lastEdited"] = new Date().getTime();
		
		var frm = this;
		
		$.ajax(baseUrl + "/" + vals[this.key], {
			type : "PUT",
			data : vals,
			success:function(data, status, xhr)
			{
				var obj = JSON.parse(data);
				if(obj.success)
				{
					$(frm.formElement).dialog("close");
					getData();
				}
				else
				{
					EpiCollect.dialog({ content : obj.msg });
				}
			},
			error:function()
			{
				EpiCollect.dialog({content : "Edit request failed" });
			}
		});
	};
	
	this.toXML = function()
	{
		var xml = "<form name=\"" + this.name + "\" num=\"" + this.num + "\" key=\"" + this.key + "\" main=\"" + this.main + "\">";
		for(fld in this.fields){
			xml = xml + this.fields[fld].toXML();
		}
		xml = xml + "</form>";
		return xml;
	};

};

EpiCollect.Field = function()
{
	
    this.text = '';
    this.id = '';
    this.required = undefined;
    this.type = '';
    this.isinteger = false;
    this.isdouble = false;
    this.options = [];
    this.local = false;
    this.title = false;
    this.isKey = false;
    this.regex = null;
    this.verify = false;
    this.genkey = false;
    this.display = true;
    this.edit = true;

    this.date = null;
    this.time = null;
    this.setDate = null;
    this.setTime = null;

    this.min = null;
    this.max = null;
    this.defaultValue = null;

    this.match = false;
    this.crumb = false;

    this.hidden = false;
    this.search = false;

    /**
     * form - the form that this field is part of
     */
    this.form = false;
    /**
     * connectedForm - the branch or group form that this field represents
     */
    this.connectedForm = false;

    this.fkTable = false;
    this.fkField = false;

    this.jump = false;

    this.parse = function(xml)
    {
		this.type = xml.tagName;
		if(this.type === "gps") this.type = "location";
		this.id = xml.getAttribute('name');
		if(!this.id) this.id = xml.getAttribute('ref');
		this.title = Boolean(xml.getAttribute('title'));
		this.required = xml.getAttribute('required') === "true";
		this.isinteger = xml.getAttribute('integer') === "true";
		this.isdouble = xml.getAttribute('decimal') === "true";
		this.local = xml.getAttribute("local") === "true";
		this.regex = xml.getAttribute('regex');
		this.verify = xml.getAttribute('verify')==="true";
		this.genkey = xml.getAttribute('genkey') === "true";
		this.hidden = xml.getAttribute('display') === "false";
		this.search = xml.getAttribute('search') === "true";
		this.uppercase = xml.getAttribute('uppercase') === "true";
		
		this.date = xml.getAttribute('date');
		this.time = xml.getAttribute('time');
		this.setDate = xml.getAttribute('setdate');
		this.setTime = xml.getAttribute('settime');
		
		this.edit = Boolean(xml.getAttribute('edit'));
		this.min = xml.getAttribute('min');
		this.max = xml.getAttribute('max');
		this.defaultValue = xml.getAttribute('default');
		
		this.jump = xml.getAttribute('jump');
		
		this.match = xml.getAttribute('match');
		this.crumb = xml.getAttribute('crumb');
		
		if(this.type === "branch")
		{
			this.connectedForm = xml.getAttribute("branch_form");
		}
		else if(this.type === "group")
		{
			this.connectedForm = xml.getAttribute("group_form");
		}
		else
		{
			for(t in this.form.forms)
			{
				if(survey.forms[t].key === this.id)
				{
					//FUTURE-PROOF : if we want to allow the foreign key field to have a differnt name to the primary key field
					this.fkParentTbl = survey.getPrevForm(survey.forms[t].name).name;
					this.fkParentField = survey.getPrevForm(survey.forms[t].name).key;
					this.fkChildTbl = survey.getNextTable(survey.forms[t].name).name;
					this.fkChildField = survey.getNextTable(survey.forms[t].name).key;
					
					this.fkTable = survey.forms[t].name;
					this.fkField = survey.forms[t].key;
				}
			}
		}
		
		this.text = xml.getElementsByTagName('label')[0].firstChild.data;
		var opts = xml.getElementsByTagName('item');
		for(var o = 0; o < opts.length; o++)
		{
			if(!opts[o].getElementsByTagName('value')[0].firstChild){ throw 'Option ' + o + ' for field ' + this.id + ' does not have a value'; }
			if(!opts[o].getElementsByTagName('label')[0].firstChild){ throw 'Option ' + o + ' for field ' + this.id + ' does not have a label'; }
			this.options.push({value : opts[o].getElementsByTagName('value')[0].firstChild.data, label : opts[o].getElementsByTagName('label')[0].firstChild.data});  
		}
		
		if(this.title) this.required = true;
    };

    this.getSuffix = function()
    {
        var suffix = '';
        if(this.date || this.setDate)
        {
            suffix = ' (' + (this.date ? this.date : this.setDate) + ')';
          
        }
        else if(this.time || this.setTime)
        {
            suffix = ' (' + (this.time ? this.time : this.setTime);
            if(suffix.match(/HH/g))
            {
                suffix += ' - 24 hours';
            }
            else if(suffix.match(/hh/g))
            {
                suffix += ' - 12 hours';
            }
            suffix += ')';
            
        }
        return suffix;
    };
    
   this.getInput = function(val, debug)
   {
	   try{
		   pre = "";
		   
		   if(!val || (typeof val === 'string' && val.match(/null|undefined/i)))
		   {
			   //console.debug(this.id);
			   if(this.id === "created" || this.id === "uploaded")
			   {
				   val = new Date().getTime().toString();
			   }
			   else if(this.id === "DeviceID")
			   {
				   val = "web";
			   }
			   else if(this.genkey || (this.isKey && this.hidden))
			   {
				   val = generateUUID();
			   }
			   else if(this.defaultValue)
               {
                    val = this.defaultValue;
               }
               else
			   {
				   val = "";
			   }
		   }
		   
		   
		   if(this.crumb)
		   {
			   var bits = this.crumb.split(',');
			  pre = "<p>" + bits[1] + ' - <span class="ecpval-' + bits[0] + '">' +  $('#' + bits[0]).val() + "</p>";
		   }
		   
		   //recursively check to see if this field is a key field from another form
		   //also need to make sure that 
		   var fkfrm;
		   var fkfld;
		   for(var frm = this.form; frm; frm = project.getPrevForm(frm.name))
		   {
			   if(frm.name === this.form.name) continue;
			   if(this.id === frm.key)
			   {
				   if(debug)
				   {
					   return pre + "<div>This is a key field from another form, when the form is being used a drop down list of keys from the previous form will appear here.</div>";
				   }
				   else
				   {
					   var pfield;
					   var pfrm = project.getPrevForm(frm.name);
					   if(pfrm && this.form.fields[pfrm.key])
					   {
						   pfield = pfrm.key;
					   }
					   else
					   {
						   pfield = false;
					   }
					   
					   this.required = true;
					  // ctrl = "<select name=\""  + this.id + "\" id=\""  + this.id + "\"" + (fkfld ? " childcontrol=\"" + fkfld + "\"" : "") + " class=\"ecplus-input loading\" >";
					   //get options;
					   var cname = this.id;
					   var key = frm.key;
					   var title = frm.titleFields;
					   var ctrl = '<input name="' + cname + '" id="' + cname +  '" class="ecplus-input ecplus-ac" pfield="' + key + '" pform="' + frm.name + '" ' + (fkfld ? ' childcontrol="' + fkfld + '"' : '') + ' /><input type="hidden" name="' + cname + '" id="' + cname +  '" value="' + val + '" class="ecplus-input-hidden" />';
					   
					   if(val)
					   {
						   ctrl.replace('ecplus-ac"', 'ecplus-ac loading"');
						   this.form.pendingReqs.push($.ajax({
							   url : baseUrl + '/../' + this.fkTable + '/title?term=' + val + '&key_from=true',
							   success : function(data, status, xhr)
							   {
								   //console.debug(data);
								   if(data.trimChars() !== "")
								   {
									   $('#' + this.id)
								   			.val(data)
								   			.removeClass('loading');
									   
								   }
								   
							   },
							   context : this
						   }));
					   }
						  
					   return pre + ctrl;
				   }
			   }
			   else
			   {
				   fkfrm = frm.name;
				   fkfld = frm.key;
			   }
		   }
			   
		   if(this.type === "branch")
		   {
			   return pre + "<div id=\"" + this.id + "\" class=\"ecplus-input\"><a href=\"javascript:project.forms['"+ this.form.name+"'].openBranch('" + this.connectedForm + "')\">Add Branch</a><p>This entry currently has <span>" +(val ? val : 0) +"</span> branch entries</p></div>";
		   }
		   else if(this.type === "select1")
		   {
			   ret =  "<select name=\"" + this.id + "\" id=\"" + this.id + "\" class=\"ecplus-input\" ><option value=\"\">Select an Option...</option> ";
			   for(var i = 0; i < this.options.length; i++)
			   {
				   
				   ret += "<option value=\"" + this.options[i].value + "\" " + (this.options[i].value === val || this.options[i].label === val ? "SELECTED" : "")  + ">" + this.options[i].label + "</option>";
			   }
			   ret +="</select>";
			   return pre +  ret;
		   }
		   else if(this.type === "select")
		   {
			   ret =  "<p id=\"" + this.id + "\"  class=\"ecplus-check-group ecplus-input\">";
			   for(var i = 0; i < this.options.length; i++)
			   {
				   var regex = new RegExp('/(^|,)' + this.options[i].value + '|' + this.options[i].label + '(,|$)/');
				   ret += "<input type=\"checkbox\" name=\"" + this.id + "\" value=\"" + this.options[i].value + "\" " + (val.match(regex) ? " checked=\"checked\" " : "") + " /><label>" + this.options[i].label + "</label><br />";
				   
			   }
			   return pre + "</p>" + ret;
		   }
		   else if(this.type === "radio")
		   {
			   ret =  "<p id=\"" + this.id + "\" class=\"ecplus-radio-group ecplus-input\">";
			   for(var i = 0; i < this.options.length; i++)
			   {
				   //console.debug();
				   if((this.options[i].value === val) || (this.options[i].label === val))
				   {
					   ret += "<input type=\"radio\" name=\"" + this.id + "\" value=\"" + this.options[i].value + "\" checked=\"checked\" labelText=\"" + this.options[i].label + "\" /><label>" + this.options[i].label + "</label><br />";
				   }
				   else
				   {
					   ret += "<input type=\"radio\" name=\"" + this.id + "\" value=\"" + this.options[i].value + "\" labelText=\"" + this.options[i].label + "\"  /><label>" + this.options[i].label + "</label><br />";
				   }
			   }
			   return pre + "</p>" + ret;
		   }
		   else if(this.type === "textarea")
		   {
			   return pre + "<textarea name=\"" + this.id + "\" id=\"" + this.id + "\" class=\"ecplus-input\">" + val + "</textarea>";
		   }
		   else if(this.date || this.setDate)
		   {
			   //Custom Date Picker
			   return pre + "<input type=\"text\" name=\"" + this.id + "\" value=\"" + val + "\" id=\"" + this.id + "\" class=\"ecplus-input ecplus-datepicker\" />";
		   }
		   else if(this.time || this.setTime)
		   {
			   return pre + "<input type=\"text\" name=\"" + this.id + "\"  value=\"" + val + "\" id=\"" + this.id + "\" class=\"ecplus-input ecplus-timepicker\" />";
		   }
		   else if(this.type === "input" || this.type === "barcode")
		   {

			   var valstring = val && val !== 'NULL' ? "value=\"" + val + "\"" : "";		   
			   return pre + "<input type=\"text\" name=\"" + this.id + "\" " + valstring + " id=\"" + this.id + "\" class=\"ecplus-input\" />";
		   }
		   else if(this.type === "video" || this.type === "audio" || this.type === "photo")
		   {
			   return pre + "<iframe id=\"" + this.id + "_iframe\" src=\"" + this.form.name + "/uploadMedia\" class=\"ecplus-input ecplus-media-input\" ></iframe><input type=\"hidden\" id=\"" + this.id + "\" name=\"" + this.id + "\" value=\"" + val + "\" />";
		   }
		   if(this.type === "location")
		   {
			   return pre + "<div id=\"" + this.id+ "\" class=\"locationControl ecplus-input\" ></div>";
		   }
		   else
		   {
			   return pre + "<input type=\"hidden\"id=\"" + this.id + "\" class=\"ecplus-input\" name=\"" + this.id + "\" value=\"" + val + "\" />";
		   }
	   }catch(err){}//console.debug(err);}
   };
	
   this.populateControl = function(data)
   {
	   for(var i = 0; i < data.length; i++)
	   {
		   $('#' + this.id +'').append("<option value=\"" + data[i][this.id] +  "\" "   + " >" +  data[i][this.id] + "</option>");
	   }
   };
   
	this.formatValue = function(value, data)
	{
		if( !value || (typeof value === "string" && (value === "undefined" || value.match(/null/i))) )
		{
			return '';
		}
		if( this.type === "select1" || this.type === "radio" )
		{
			
			var opts = this.options;
			var l = opts.length;
			for( var i =0; i < l; i++ )
			{
				if( opts[i].value === value || opts[i].label === value )
				{
					return opts[i].label;
				}
			}
			return '<i color="FF0000">' + value + '</i>';
		}
		else if( this.type === "select" )
		{
			var sels = value.split(',');
			var opts = this.options;
			var l = opts.length;
			
			var ret = '';
			for( var i =0; i < l; i++ )
			{
				var l_s = sels.length;
				for( var j = 0; j < l_s; j++ )
				{
					if( opts[i].value === sels[j] || opts[i].label === sels[j] )
					{
						ret = ret + (ret !== '' ? ', ' : '') + opts[i].label;
						sels.splice(j, 1);
						break;
					}
				}
			}
			if(sels.length > 0)
			{
				if(ret !== '') ret = ret + ', ';
				ret = ret + sels.join(',');
			}
			return ret;
		}
		else if(this.type === "photo"){
			if(value && !value.match(/^null$/i) && value !== "-1")
			{
				if(value.match(/^http:\/\//))
				{
					return  "<a href=\""+value+"\" target=\"__blank\"><img src=\"" + value + "&thumbnail=true\" alt=\""+value+"\" height=\"125\"/></a>";
				}
				else
				{
					return  "<a href=\"./" +formName+"/__getImage?img="+value+"\" target=\"__blank\"><img src=\"./" +formName+"/__getImage?img="+value+"&thumbnail=true\" alt=\""+value+"\" height=\"125\"/></a>";	
				}
				
			}
			else
			{
				return "<i>No Image</i>";
			}
		}else if(this.type === "video" || this.type === "audio"){
			if(value)
			{
                var checkid = 'check' + (nchecks++);
                var checkurl;
                var valUrl;
                if(value.match(/^http:/i))
                {
                    valUrl = value;
                    checkurl = value;
                }
                else
                {
				    valUrl = "ec/uploads/" + project.name + "~" +value;
				    checkurl = (location.href.replace(project.name + '/' + formName, '') + valUrl).trimChars('/');
                }

                checking[checkurl] = checkid;
                checker.startCheck(checkurl);

                return "<a id=\"" + checkid + "\" href=\"" + checkurl + "\" target=\"__blank\"> View Media </a>";
			}
			else
			{
				return "<i>No Media</i>";
			}
		}else if(this.type === "location" || this.type === "gps"){
			if(value)
			{
				return value.latitude + ", " + value.longitude + ' <a href="javascript:showGPS(' + JSON.stringify(value).replace(/"/g, '\'').replace(/[\n\r]/g, '') + ')">Show Details</a>' ;
			}
			else
			{
				return "No Value";
			}
		}
		else if(this.id === 'created' && value.match(/^\d+$/))
		{
			return new Date(EpiCollect.toJSTimestamp(value)).toLocaleString();
		}
		else if(this.type === 'branch')
		{
			if(!value)
			{
				return '0 <a href="javascript:project.forms[\'' + this.connectedForm + '\'].displayForm({ vertical : false, data : { \'' + this.form.key + '\': \'' + data[this.form.key] + '\'} });">Add ' +  this.connectedForm + '</a>' ;
			}
			else
			{
                if(data){
				    return value +  ' <a href="' + this.connectedForm + '?' + this.form.key +  '=' + data[this.form.key] + '&trail=' + this.form.name +  '">View entries</a> | <a href="javascript:project.forms[\'' + this.connectedForm + '\'].displayForm({ vertical : false, data : { \'' + this.form.key + '\': \'' + data[this.form.key] + '\'} });">Add ' +  this.connectedForm + '</a>' ;
                }
                else
                {
                    return value;
                }
			}
		}
		else
		{ 
			if(!value || (typeof value === "string" && (value === "undefined" || value.match(/\s?null\s?/i))))
			{
				return '';
			}
			else
			{
				return value;
			}
		}
	};
	
    this.getValidators = function(ignore) // ignoreDouble entry (verify)
    {
        var validators = [];
        
        if( this.required )
        {
            validators.push({ name : 'required', params : {} });
        }
        
        if( this.isinteger )
        {
            validators.push({ name : 'integer', params : {} });
        }
        
        if( this.isdouble )
        {
            validators.push({ name : 'double', params : {} });
        }
        
        if( this.verify )
        {
            validators.push({ name : 'verify', params : {} });
        }
        
        if( this.date || this.setDate )
        {
            validators.push({ name : 'date', params : { fmt : this.date ? this.date : this.setDate } });
        }
        
        if( this.time || this.setTime )
        {
            validators.push({ name : 'time', params : { fmt : this.time ? this.time : this.setTime } });
        }
        
        if( this.regex )
        {
            validators.push({ name : 'regex', params : { regex : this.regex } });
        }
        
        if( this.max || this.max === '0' )
        {
            validators.push({ name : 'max', params : { max : this.max } });
        }
        
        if( this.min || this.min === '0'  )
        {
            validators.push({ name : 'min', params : { min : this.min } });
        }
        
        if( this.match )
        {
            validators.push({ name : 'match', params : { regex : this.match.split(',')[3], parentValue : '' } });
        }
        
        if( this.isKey )
        {
            validators.push({ name : 'key', params : { url :  baseUrl + '/../' + this.form.name + '.json' , field : this.id }});
        }
        
        if( this.fkField && this.fkTable )
        {
            validators.push({ name : 'fk' , params : { url : baseUrl + '/../' + this.fkTable + '/title' }});  
        }
    	
        for(var i = 0; ignore && i < ignore.length; i++)
        {
        	 for(var v = 0; v < validators.length; v++)
        	 {
        		if(validators[v].name == ignore[i])
        		{ 
        			validators.splice(v, 1);
        		}
        	 }
        }
        
        return validators;
    };
    
    
	this.validate = function(value, ignore)
	{
        var validators = this.getValidators(ignore);
        var n_vals = validators.length;

        if(n_vals !== 0)
        {
        	var control = $("#" + this.id, this.form.formElement);
        	// for validation of formbuilder controls where there
        	if(control && control.hasClass('ecplus-input')) //is no actual field	so we can do min, max and default
        	{
	            control.addClass('checking')
	                .removeClass('valid')
	                .removeClass('invalid');
	                
	            $("#" + this.id + '-messages', this.form.formElement).empty();
        	}
        	
        	// Set up the "voter" if we don't then setting up the voter and dispatching the validation method causes a race
        	// condition that might cause an early "valid" to incorrectly fire the valid event
        	for ( var v = n_vals; v--; )
            {
        		var voter = {};
	        	if( control.prop('voter') )
	            {
	                voter = control.prop('voter');
	            }
	            
	            voter[validators[v].name] = undefined;
	            control.prop('voter', voter);
            }
        	
            for ( var v = n_vals; v--; )
            {
                this.dispatchValidator(value, validators[v]);
            }
        }
        else
        {
            $('#' + this.id, this.form.formElement).trigger({ type : 'valid', field : this.id });
        }
	};
    
    this.dispatchValidator = function(value, validatorDescription)
    {

        EpiCollect.Validators[validatorDescription.name](value, validatorDescription.params, this.validatorCallback, this.id);
    };
    
    /**
     * Add messages to the field, primarily for validation.
     *
     *  @param messages [] an array of messages
     *  @param className String The CSS class of the messages.
     */
    this.addMessages = function(messages, className)
    {
        var mlen = messages.length;

        var messageArea = $('#' +this.id + '-messages', this.form.formElement);
        
        for ( var m = mlen; m--; )
        {
            messageArea.append('<p class=' + className + '>' + messages[m] + '</p>');
        }
    };
    
    this.validatorCallback = function(result)
    {
        var form = project.forms[formName];
        var field = form.fields[result.control_id];
        var control = $('#' + result.control_id, form.formElement);

        var voter = control.prop('voter');
        voter[result.name] = result.valid;
        control.prop('voter', voter);
        
        var is_input = control && control.hasClass('ecplus-input');
        
        if( !result.valid )
        {
            if(is_input){
            	
            	control.addClass('invalid');
            }
            
            field.addMessages(result.messages, 'err');
        }
        
        var complete = true;
        var valid = true;
        
        for ( var v in voter )
        {
        	if (voter[v] === undefined)
        	{
        		complete = false;
        		break;
        	}
            
        	valid = valid && voter[v];
        	
        }
        
        console.debug(complete, valid, voter);
        
        if(complete) 
        { 
        	if(is_input)
        	{
	            control.removeClass('checking');
	            if(!valid)
	            {
	            	control.removeClass('valid');
	                control.trigger({ type : 'invalid', field : result.control_id });
	            }
	            else
	            {
	            	console.debug('fire valid');
	                control.addClass('valid');
	                control.trigger({ type : 'valid', field : result.control_id });
	            }
            }
        }
    };
	
	this.toXML = function()
	{
		if( !this.type ) { return ""; }
		
		var xml = "<" + this.type + " ref=\"" + this.id + "\"";
		//TODO: Other settings
		if(this.required) xml = xml + " required=\"true\"";
		if(this.isinteger) xml = xml + " integer=\"true\"";
		if(this.isdouble) xml = xml + " decimal=\"true\"";
		if(this.local) xml = xml + " local=\"true\"";
		if(this.title) xml = xml + " title=\"true\"";
		if(this.regex) xml = xml + " regex=\"" + this.regex + "\"";
		if(this.verify) xml = xml + " verify=\"true\"";
		if(this.genkey) xml = xml + " genkey=\"true\"";
		if(!this.display || this.hidden) xml = xml + " display=\"false\"";
		if(this.edit) xml = xml + " edit=\"true\"";
		if(this.date && this.date !== "") xml = xml + " date=\"" + this.date + "\"";
		if(this.time && this.time !== "") xml = xml + " time=\"" + this.time + "\"";
		if(this.setDate && this.setDate !== "") xml = xml + " setdate=\"" + this.setDate + "\"";
		if(this.setTime && this.setTime !== "") xml = xml + " settime=\"" + this.setTime + "\"";
		if(this.min || this.min === 0) xml = xml + " min=\"" + this.min.toString() + "\"";
		if(this.max || this.max === 0) xml = xml + " max=\"" + this.max.toString() + "\"";
		if(this.defaultValue) xml = xml + " default=\"" + this.defaultValue + "\"";
		if(this.match) xml = xml + " match=\"" + this.match + "\"";
		if(this.crumb) xml = xml + " crumb=\"" + this.crumb + "\"";
		if(this.search) xml = xml + " search=\"true\"";
		if(this.jump) xml = xml + " jump=\"" + this.jump + "\"";
		if(this.type === "branch") xml = xml + " branch_form=\"" + this.connectedForm +"\"";
		
		xml = xml + "><label>" + this.text.encodeXML() + "</label>";
		
		for(var i = 0; i < this.options.length; i++)
		{
			var opt = this.options[i];
			xml = xml + "<item><label>" + opt.label.encodeXML() + "</label><value>" + opt.value + "</value></item>";
		}
		
		xml = xml + "</" + this.type + ">";
		return xml;
	};
	
};