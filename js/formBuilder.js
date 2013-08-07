var currentForm = undefined;
var currentControl = undefined;
var formName = undefined;
var dirty = false;

/**
 * A widget to display Errors and warnings
 * 
 * @param id The id of the element containing the error List
 * @returns
 */
function ErrorList(id)
{
	this.errors = [];
	this.warnings = [];
	
	this.div = $('#' + id);
	
	this.div.empty();
	
	this.div.append('<h3>Form Errors</h3><div class="body"></div><div class="footer"><span class="index"></span> of <span class="total"></span><a class="next">next</a><a class="prev">previous</a></div>');
	
	$('.next', this.div).bind('click',{ctx : this}, function(evt){
		evt.data.ctx.next();
	});
	
	$('.prev', this.div).bind('click', {ctx : this}, function(evt){
		evt.data.ctx.previous();
	});
	
	this.reset();
}

ErrorList.prototype.addError = function(error){
	this.errors.push(error);
	
	this.setTotal();
	this.showError(0);
	
};

ErrorList.prototype.addWarning = function(warning){
	this.warnings.push(warning);
	
	this.setTotal();
	this.showError(0);
	
};

/**
 * Add several errors to the errorlist
 * 
 * @param errors {Array}
 */
ErrorList.prototype.addErrors = function(errors){
	for( var e = 0; e < errors.length; e++ )
	{
		this.addError(errors[e]);
	}
};

ErrorList.prototype.setTotal = function()
{
	this.total = this.errors.length + this.warnings.length;
	$('.total', this.div).text(this.total);
	$('.index', this.div).text('0');
	
	if( this.total > 0 )
	{
		this.showError(0);
	}
	else
	{
		this.noErrors();
	}
};

ErrorList.prototype.showError = function(idx)
{
	var err = {};
	var cls = '';
	
	if(this.errors.length > 0 && idx < this.errors.length)
	{
		err = this.errors[idx];
		cls = 'error';
	}
	else if( idx < this.total )
	{
		if(this.errors.length === 0)
		{
			err = this.warnings[idx];
		}
		else
		{
			err = this.warnings[idx % this.errors.length];
		}
		
		cls = 'warning';
	}
	
	$('.body', this.div).html('<h4>Component : ' + err.control + '</h4><p>' + err.message + '</p>');
	$('.body', this.div).removeClass('error').removeClass('warning').addClass(cls);
	
	this.index = idx;
	$('.index', this.div).text(idx + 1);
};

ErrorList.prototype.noErrors = function()
{
	//show message saying there are no error and a save and preview button
	$('.body', this.div).html('<h4>Form Valid</h4><p>This form has no errors.</p>');
};

ErrorList.prototype.reset = function()
{
	this.errors = [];
	this.warnings = [];
	
	$('.body', this.div).empty().removeClass('warning').removeClass('error');
	
	this.setTotal();
};

ErrorList.prototype.next = function()
{
	if( this.index < (this.total - 1))
	{
		this.showError(this.index + 1);
	}
};

ErrorList.prototype.previous = function()
{
	if( this.index > 0)
	{
		this.showError(this.index - 1);
	}
};

var errorList;

$(function()
{
	var url = location.href;
        
	EpiCollect.loadProject(url.substr(0, url.lastIndexOf("/")) + ".xml", drawProject);

	var details_top = $("#details").offset().top;
	
	details_top = details_top - $("#toolbox").height();
	        
	$(window).scroll(function(evt){
		if($(document.body).scrollTop() > $("#toolbox").offset().top) 
		{  
			$("#toolbox-inner").css({
				"position":"fixed",
				"top" : "0px"
			}); 
		}
		else
		{
			$("#toolbox-inner").css({
				"position":"relative"
			}); 
		}
		if($(document.body).scrollTop() > details_top) 
		{  
            var dest = $('#destination');
            
			$("#details").css({
				"position":"fixed",
				"top" : $("#toolbox-inner").height() + 10 + "px",
				"left" : (dest.offset().left + dest.width() + 20) + "px"
			}); 
			
			$("#source").css({
				"position":"fixed",
				"top" : $("#toolbox-inner").height() + 10 + "px",
				"left" : "auto"
			}); 
			
		}
		else
		{
			$("#details").css({
				"position":"absolute",
                                "top" : "0px",
                                "left" : ''
			}); 
			$("#source").css({
				"position":"absolute",
				"top" : "0px",
				"left" : "0px"
			}); 
		}	
	});
        
    $(window).unload(function(){
        if($('.unsaved').length > 0)
        {
            localStorage.setItem(project.name + '_xml', project.toXML());
        }
    });
	
	$('.first').accordion({ collapsible : true });
	
	$("[allow]").hide();
	$("[notfor]").show();
	
	$('#destination').sortable({
		revert : 50,
		tolerance : 'pointer',
        items : '> .ecplus-form-element',
		start : function(evt, ui)
		{
			ui.placeholder.css("visibility", "");
			ui.placeholder.css("background-color", "#CCFFCC");
		},
		stop : function(evt, ui)
		{
			if(!currentForm)
			{
				EpiCollect.dialog({ content : "You need to choose a form in order to change the controls on it."});
				$("#destination div").remove();
			}
			else
			{
				var jq = $('#destination .end').remove();
				$('#destination').append(jq[0]);
				dirty = true;
                setSelected($(ui.item));
                updateStructure();
                updateJumps();
			}
		}
	});
	
	$(".ecplus-form-element").draggable({
		connectToSortable: "#destination",
		helper: 'clone',
		revert: "invalid",
		revertDuration : 100,
		appendTo : 'body',
        scroll : true
	});
	
	$('#destination').click(function(evt){
           
		var div = evt.target;
		while(div.tagName !== "DIV") { div = div.parentNode;}
		
		var jq = $(div);
		if(jq.hasClass("ecplus-form-element"))
		{
			setSelected(jq);
		}
	});
	
	$('#formList').click(function(evt){
		var sp = evt.target;
		
		if(sp.tagName === "SPAN")
		{
			sp = $(sp);
			$('#formList span').removeClass('selected');
			if(sp.hasClass("control"))
			{
				newForm();
			}
			else if(sp.hasClass("form"))
			{
				switchToForm(sp.text());
			}
		}
	});
	
	$("#key").change(function(evt){
		if(evt.target.checked)
		{
			$("[allow*=key]").show();
			$("[notfor*=key]").hide();
		}
		else
		{
			$("[allow*=key]").hide();
			$("[notfor*=key]").show();
		}
	});
	$('#genkey').change(function(evt){
		if(evt.target.checked)
		{
			$("[allow*=gen]").show();
			$("[notfor*=gen]").hide();
		}
		else
		{
			$("[allow*=gen]").hide();
			$("[notfor*=gen]").show();
		}
	});
	
	
	$("#options .removeOption").unbind('click').bind('click', removeOption);
	$("#jumps .remove").unbind('click').bind('click', removeOption);
    $('#destination').append('<img src="../images/editmarker.png" class="editmarker">');
    $('.editmarker').hide();
    $('.last input, .last select').change(function(){ 
    	$('#destination .selected').addClass('editing');
    	dirty = true;
    });
    $('.last').hide();
    $('#required').change(function(evt)
    {
        $('[name=jumpType] option[value=NULL]').toggle(!$( this ) .prop('checked'));
    });

    $('.last input, .last select').bind('change', function(){ dirty = true; });
    
    errorList = new ErrorList('errorList');
});

function drawProject(prj)
{
    project = prj;
    var temp_xml = localStorage.getItem(project.name + '_xml');
    if(temp_xml)
    {
        if(confirm("There is an unsaved version of this project stored locally. Do you wish to load it?"))
        {
            project = new EpiCollect.Project();
            project.parse($.parseXML(temp_xml));
        }
        else
        {
            localStorage.removeItem(project.name + '_xml');
        }
    }
    
	$("#formList .form").remove();
	
	for(var frm in project.forms)
	{
        if(project.forms[frm].main)
		addFormToList(frm);
	}
        
    if($("#formList .form").length === 0)
    {
        newForm('Please choose a name for your first form - this should only consist of letters, numbers and underscores.');
    }
    else
    {
        switchToForm(Object.keys(project.forms)[0]);
        validateCurrentForm();
    }
   
}

/**
 * 
 * @param message
 * @param name
 */
function newForm(message, name, closeable)
{
	if(!message) 
	{
		message = "Enter the new form name below. Form names must contain only letters, number and underscores.";
	}
        
    buttons  = {
        'OK' :function(){ 
            var name = $( 'input', this ).val();
            
            $( this ).dialog('close');
            var valid_name = project.validateFormName(name);
            
            if(name !== '' && valid_name === true)
            {
                var frm = new EpiCollect.Form();
                frm.name = name;
                frm.num = $('.form').length + 1;
                project.forms[name] = frm;

                addFormToList(name);

                var par = project.getPrevForm(name);

                if(par && frm.main)
                {
                        frm.fields[par.key] = new EpiCollect.Field();
                        frm.fields[par.key].id = par.key;
                        frm.fields[par.key].text = par.fields[par.key].text;
                        frm.fields[par.key].isKey = false;
                        frm.fields[par.key].title = false;
                        frm.fields[par.key].type = 'input';
                        frm.fields[par.key].form = frm;
                }

                switchToForm(name);
            }
            else if(name)
            {
                newForm("<p class=\"err\">" + valid_name + "</p>", name);
            }
            else
            {
                newForm(message + "<p class=\"err\">The form name cannot be blank</p>", name);
            }

        }
    };
        
    if(closeable){
        buttons[ 'Cancel' ] = function()
        {
            $( this ).dialog('close');
        };
    }
        
	EpiCollect.prompt({ 
        closeable : closeable,
        buttons: buttons,
        content : "<p>" + message + "</p>"
    });
	
}

function addFormToList(name)
{
	$( "#formList .control" ).before("<span id=\"" + name + "\" class=\"form\">" + name + "</span>");
}

/**
 * Function to add the field representation onto the form
 * 
 * @param id the id of the element
 * @param text the text for the element
 * @param type the css class of the template in the left bar
 */
function addControlToForm(id, text, type)
{
	if(type.trimChars() === "") return; 

	if(!type.match(/\.?ecplus-[a-z0-9]+-element/))
	{
		type = '.ecplus-' + type + '-element';
	}
	
	if( type[0] !== "." ) type = "." + type;
	var jq = $(type, $(".first")).clone();
	
	
	$("p.title", jq).text(text.decodeXML());
	jq.prop("id", id);
    
	$(".option", jq).remove();

	if( type.match(/select1?|radio/) )
	{
		var opts = currentForm.fields[id].options;
		var l = opts.length;
		for( var i = 0; i < l; i++ )
		{
			jq.append("<p class=\"option\">" + opts[i].label.decodeXML() + "</p>");
		}
	}
	
	$( "#destination" ).append(jq);
}

function addOption()
{
	var panel = $("#options");
	panel.append('<div class="selectOption"><hr /><label title="The text displayed to the user">Label</label><input title="The text displayed to the user" name="optLabel" size="12" />'
			+ '<div style="float:right; font-weight:bold;font-size:10pt;"></div>'
			+ '<br /><label title="The value stored in the database"l>Value</label><input title="The value stored in the database" name="optValue" size="12" />'
			+ '<div style="float:right; font-weight:bold;font-size:10pt;"></div><br /><a href="javascript:void(0);" class="button removeOption" >Remove Option</a> </div>');

    $('.last input, .last select').unbind('change').bind('change', function(){ dirty = true; });
	$("#options .removeOption").unbind('click').bind('click', removeOption);
	
	$("#options input").unbind();
	$("#options input").change(function()
	{
		updateSelected(true);
		updateJumps();
	});
}

function removeOption(evt)
{
	var ele = evt.target;
	while(ele.tagName !== "DIV") ele = ele.parentNode;
	
	$(ele).remove();
	updateJumps();
}

function addJump()
{
	var panel = $("#jumps");
	
	var sta = '<div class="jumpoption"><hr /><label>Jump when </label><select name="jumpType"><option value="">value is</option><option value="!">value is not</option><option value="NULL">field is blank</option><option value="ALL">field has any value</option></select>';
	
	if(currentControl.type === 'input')
	{
		panel.append(sta + '<label>Value</label><input type="text" class="jumpvalues" /><br /><label>Jump to</label> <select class="jumpdestination"></select><br /><a href="javascript:void(0);" class="button remove" >Remove Jump</a></div>');
	}
	else
	{
		panel.append(sta + '<label>Value</label> <select class="jumpvalues"></select><br /><label>Jump to</label> <select class="jumpdestination"></select><br /><a href="javascript:void(0);" class="button remove" >Remove Jump</a></div>');
	}
	
	$("#jumps .remove").unbind('click').bind('click', removeJump);
    $('.last input, .last select').unbind('change').bind('change', function(){ dirty = true; });
}

function removeJump(evt)
{
	var ele = evt.target;
	while(ele.tagName !== "DIV") ele = ele.parentNode;
	
	$(ele).remove();
}



function drawFormControls(form)
{	
	$("#destination div").remove();
	
	var fields = form.fields;
	
	for(var f in fields)
	{
		var fld = fields[f];
		var cls = undefined;
		//var suffix = '';
        
		if(fld.type === "input")
		{
			
			if(fld.isinteger || fld.isdouble)
			{
				cls = "ecplus-numeric-element";
			}
			else if(fld.date || fld.setDate)
			{
				cls = "ecplus-date-element";
              
			}
			else if(fld.time || fld.setTime)
			{
				cls = "ecplus-time-element";
              
			}
			else
			{
				cls = "ecplus-text-element";
			}
			
			var forms = project.forms;
			
			for(var fm in forms)
			{
				if(fm !== form.name && fld.id === forms[fm].key && fld.form.num > forms[fm].num)
				{
					cls = "ecplus-fk-element";
				}
			}
		}
		else
        {        
            cls = "ecplus-" + fld.type + "-element";
        }
		
		addControlToForm(fld.id, fld.text + fld.getSuffix(), cls);		
	}
	
}

function validateCurrentForm()
{
	errorList.reset();
	
	validateForm(currentForm);
}

function validateForm(v_form)
{
	var titleFields = [];
	
	for ( var fld in v_form.fields )
	{
		var jq = $('#destination #' + fld);
		var _type = jq.attr("type");
		
		validateControl(v_form.fields[fld], _type, function(){});
		
		if( v_form.fields[fld].title ) { titleFields.push(fld); }
		
		if( !!v_form.fields[fld].jump )
		{
			var jump_arr = v_form.fields[fld].jump.split;
			//TODO: Jump Validation
		}
	}
	
	$('#btn_save, #btn_preview').toggle(errorList.errors.length === 0);
	$('.saveError').toggle(errorList.errors.length !== 0);
	
	if( titleFields.length === 0 )
	{
		errorList.addWarning({ control : 'form', message : "There is no title field selected, it is advisable to set a field as a title " +
				"to help users quickly distinguish between entries" });
	}
}

/**
 * Added in 1.5 - fire validation asynchronously and generate and error list. If the list finishes empty enable the save button
 * 
 * @param ctrl
 * @param callback
 */
function validateControl(ctrl, _type, callback)
{
	//validate control name
	 var nameValid = project.validateFieldName(currentForm, ctrl);
	 var messages = [];
	 
	ctrl.fb_voter = {};
	
	if( !ctrl.text )
	{
		console.debug('label fail');
		messages.push({control : ctrl.id, message : "Every field must have a label"});
	}
	
	if( nameValid !== true )
	{
		messages.push({ control : ctrl.id, message: nameValid });
	}
	
	if(_type === 'date')
	{	
		if (!ctrl.date && !ctrl.setDate )
		{
			messages.push({control : ctrl.id,  message : "You must select a date format." });
			//throw "You must select a date format.";
			success = false;
		}
		
	}
	else if(_type === 'time')
	{
        if( !ctrl.time && !ctrl.setTime )
        {
        	messages.push({ control : ctrl.id, message : "You must select a time format." });
            success = false; //throw "You must select a time format.";
        }
        
	}
	else if(_type === 'numeric')
	{        
       // if( isNaN(Number(ctrl.min)) ) messages.push({ control : ctrl.id, message : "Minimum value is not a number" });
       // if( isNaN(Number(ctrl.max)) ) messages.push({ control : ctrl.id, message : "Maximum value is not a number" });

        if(ctrl.min !== '')
        {
        	var validators = ctrl.getValidators(['key','fk', 'min', 'max', 'required']);
    		for( var v = 0; v < validators.length; v++ )
    		{
    			var vali = validators[v];
    			
    			var res = EpiCollect.Validators[vali.name](ctrl.min, vali.params, null, ctrl.id);
    			
    			if ( !res.valid )
    			{
    				messages.push({ control : ctrl.id, message : '<em>Maximum</em> ' + res.messages[0] });
    			}
    		}
        }
        if(ctrl.max !== '')
        {
        	var validators = ctrl.getValidators(['key','fk', 'max', 'min', 'required']);
    		for( var v = 0; v < validators.length; v++ )
    		{
    			var vali = validators[v];
    			
    			var res = EpiCollect.Validators[vali.name](ctrl.max, vali.params, null, ctrl.id);
    			
    			if ( !res.valid )
    			{
    				messages.push({ control : ctrl.id, message : '<em>Maximum</em> ' + res.messages[0] });
    			}
    		}
        }
        if((!!ctrl.min || ctrl.min === 0) && (!!ctrl.max|| ctrl.max === 0)  && ctrl.min >= ctrl.max)
        {
        	messages.push({ control : ctrl.id, message : "<em>Minimum</em> must be smaller than the <em>Maximum</em>" });
        }
	}
	
	//Validate Jumps?
	
	// All that's left is to validate the default, to do this we need to store the current message array
	// If we hit errors 
	var df_val = ctrl.defaultValue;
	
	if( !!df_val )
	{
		var validators = ctrl.getValidators(['key','fk', 'required']);
		for( var v = 0; v < validators.length; v++ )
		{
			var vali = validators[v];
			
			var res = EpiCollect.Validators[vali.name](df_val, vali.params, null, ctrl.id);

			if ( !res.valid )
			{
				messages.push({ control : ctrl.id, message : '<em>Default</em> ' + res.messages[0] });
			}
		}
		
	}		
	
	validateCallback({control : ctrl, messages : messages }, ctrl.fb_voter !== {});

}

/**
 * Added in 1.5 
 *  
 * @param info
 */
function validateCallback(info, wait)
{	
	// check list length and enable/disable save button
	//var errorList = $('#errorList');
	//errorList.empty();
	//$('.' + info.control.id, errorList).remove();	
	
	// populate list
	for( var m = 0; info.messages && m < info.messages.length; m++ )
	{
		if(typeof info.messages[m] == 'object')
		{
			errorList.addError(info.messages[m]);
		}
		else
		{
			errorList.addError(info);
		}
	}
	
	if(wait) return;
}

function expandErrorList()
{
	
}

function collapseErrorList()
{
	//should leave one visible and the user should be able to change to one that they want to be able to seen and correct
}

/**
 * @param is_silent
 * @returns {Boolean}
 */
function updateSelected(is_silent)
{
	
    //if(!dirty){ return true;}
    
	var jq = $("#destination .selected");
	var cur = currentControl;
    var cfrm = currentForm;
	if( jq === undefined || jq.length === 0 ) return true;
	
	var name = cur.id; 
	var _type = jq.attr("type");
    
	if( _type === 'fk' )
	{
	//	cur.id = project.forms[$('#parent').val()].key;
	}
	else
	{
        cur.id = $('#inputId').val();
        cur.text = $('#inputLabel').val();
	}
	
	if( _type.match(/^(text|numeric|date|time|fk)$/) )
	{
        cur.type = "input";
        if(_type === "fk")
        {
            /*var f = cur.
            var frm = project.forms[f]
            cur.id = frm.key;
            cur.text = frm.fields[frm.key].text;*/
        }
	}
	else
    { 
        cur.type = _type; 
    }
	
	var notset = !$("#set").prop("checked");
	
	cur.required = !!$("#required").prop("checked");
	cur.title = !!$("#title").prop("checked");
	cur.regex = $("#regex").val(); // Can't really validate regexes?!?
	cur.verify = !!$("#verify").prop("checked");
	
	cur.date = false;
	cur.setDate = false;
	cur.time = false;
	cur.setTime = false;
	
	if( _type === "time" )
	{
		cur[(notset ? "time": "setTime")] = $("#time").val();
	}
	if( _type === "date" )
	{
		cur[(notset ? "date": "setDate")] = $("#date").val();
	}

	cur.min = $('#min').val();
	cur.max = $('#max').val();
	cur.isinteger = !!$("#rdo_integer").prop("checked");
	cur.isdouble = !!$("#rdo_decimal").prop("checked");

    cur.genkey = !!$("#genkey").prop("checked");
    cur.hidden = !!$("#hidden").prop("checked");
	
	cur.defaultValue = $("#default").val();
	cur.search = !!$("#search").prop("checked");
	
	var optCtrls = $(".selectOption");
	var options = [];
	
	var n = optCtrls.length;
	for(var i = 0; i < n; i++)
	{
		options[i] = { label : $("input[name=optLabel]", optCtrls[i]).val(), value : $("input[name=optValue]", optCtrls[i]).val() };
	}
	cur.options = options;
	
	var jump = "";
	var jumpCtrls = $(".jumpoption");
	var jn = jumpCtrls.length;
	
	for(var i = jn; i--;)
	{
		var jumpType = $('[name=jumpType]', jumpCtrls[i]).val();
		var jval = (jumpType.length > 1 ? jumpType :  jumpType + (Number($(".jumpvalues", jumpCtrls[i]).val())));
		
		jump = $(".jumpdestination", jumpCtrls[i]).val() + ","  + jval + (jump === "" ? "" : "," + jump);
	}
	
	cur.jump = jump.trimChars(",");
	
	jq.attr("id", cur.id);
	$("p.title", jq).text(cur.text + cur.getSuffix());
	$(".option", jq).remove();
	
	if(cur.type.match(/select1?|radio/))
	{
		var opts = cur.options;
		var l = opts.length;
		for(var i = 0; i < l; i++)
		{
			jq.append("<p class=\"option\">" + opts[i].label + "</p>");
		}
	}
	else
	{
		cur.options = [];
	}
	
	if(name !== cur.id)
	{
		var newFlds = {};
		var prevFlds = cfrm.fields;
		
		$('#destination .ecplus-form-element').each(function(idx, ele){
			if( $(ele).hasClass('selected') )
			{
				newFlds[ele.id] = cur;
			}
			else
			{
				newFlds[ele.id] = prevFlds[ele.id];
			}
		});
		
		cfrm.fields = newFlds;
    }
    
  
    if(!is_silent)
    {
        if(dirty) $('#' + cfrm.name).addClass('unsaved');
        $('#destination .selected').removeClass('editing');
        $('.editmarker').hide();
        $('#details').hide();
    }
    else
    {
        updateEditMarker();
    }
   
    currentControl = cur;
    currentForm = cfrm; 
	
	validateCurrentForm();
    return true;
}

function updateSelectedCtl(){
	updateSelected();
}

function updateForm()
{
	var success = true;
	
	if(!currentForm) return true;
	if(!updateSelected()) success = false;	
	if(!currentForm.key)
	{
		EpiCollect.dialog({ content : "The form " + currentForm.name + " needs a key defined." });
		//throw "The form " + currentForm.name + " needs a key defined.";
		return false;
	}
	
    if(dirty) $('#' + currentForm.name).addClass('unsaved');
        
	updateStructure();

	return success;
}

function updateStructure()
{
	var fields = {};
	var form = currentForm;
	
	var elements = $("#destination div");
	for(var i = 0; i < elements.length && form; i++)
	{
		var id = elements[i].id;
		
		if(form.fields[id])
		{
			fields[id] = form.fields[id]; 
			form.fields[id].index = i;
		}
		
		if(fields[id].isKey) form.key = id;
	}
	currentForm.fields = fields;
}

function updateJumps()
{
	try{
        //updateForm();
            
        var opts = currentControl.options;
        
        var fieldCtls = $(".jumpvalues");
        
        var vals = [];
        
        
        fieldCtls.each(function(idx, ele){
            vals[idx] = $(ele).val();
        });
        
        fieldCtls.empty();
        for(var i = 0; i < opts.length; i++)
        {
            fieldCtls.append("<option value=\"" + (i + 1) + "\" >" + opts[i].label + "</option>");
        }
        
        $(".jumpvalues").each(function(idx, ele){
             $(ele).val(vals[idx]);
        });
        
        fieldCtls = $(".jumpdestination");
        
        vals = [];
        
        fieldCtls.each(function(idx, ele){
            vals[idx] = $(ele).val();
        });
        
        
        fieldCtls.empty();
        
        var ctrls = $('#destination .ecplus-form-element');
        
        ctrls.each(function(idx, ele)
        {
        	var fld = ele.id;
            var field = currentForm.fields[fld];
            var lbl = currentForm.fields[fld].text;
            if(lbl.length > 25) lbl = lbl.substr(0,22) + "...";
            if(field.type && !field.hidden) fieldCtls.append("<option value=\"" + fld + "\">" + lbl + "</option>");
        });
        fieldCtls.append("<option value=\"END\">END OF FORM</option>");
        
        $(".jumpdestination").each(function(idx, ele){
             var jq = $(ele);
             var opts = $('option', jq);
             var len = opts.length;
             
             var show = false;
             var cField = $('.ecplus-form-element.selected').attr('id');
             //var fidx;
             
             for(var i = 0; i < len; i++ )
             {
                $(opts[i]).toggle(show);
                if( opts[i].value === cField ) {
                    // hide the next + 1 element as there's no point jumping to the next question!
                    $(opts[++i]).toggle(show);
                    show = true;
                }
             }
             if(vals.length > idx) jq.val(vals[idx]);
        });
        
        $('[name=jumpType] option[value=NULL]').toggle(!$('#required').prop('checked'));
        
	}catch(err)
	{
		/*alert(err)*/;
	}
}

function genID()
{
	var x = $('#destination .ecplus-form-element').length;
	var name= 'ecplus_' + currentForm.name + '_ctrl' + x;
	for(; currentForm.fields[name]; x++)
	{
		name = 'ecplus_' + currentForm.name + '_ctrl' + x;
	}
	return name;
}

function updateEditMarker()
{
    var jqEle = $('#destination .selected');
    var mkr = $('.editmarker');
    mkr.show();
    mkr.animate({
        height : jqEle.outerHeight(),
        top : jqEle.offset().top - $('#destination').offset().top,
        width : jqEle.width(),
        left : jqEle.offset().left - $('#destination').offset().left
    }, {
        duration : 100
    });
}

function setSelected(jq)
{
    var jqEle = jq;
    dirty = false;
    
    if(jqEle.hasClass("ecplus-form-element"))
    {
        if(window["currentControl"])
        {
            if(!updateSelected()) return;
            $(".last input[type=text]").val("");
            $(".last input[type=checkbox]").prop("checked", false);
        }

        $("#parent").val("");
        $("#destination .ecplus-form-element").removeClass("selected");
        jqEle.addClass("selected");

        updateEditMarker();

        $('#date').val('');
        $('#time').val('');
        $('#min').val('');
        $('#max').val('');
        $('#default').val('');

        if(currentForm.fields[jqEle.attr("id")])
        {	
            currentControl =  currentForm.fields[jqEle.attr("id")];
        }
        else
        {
            currentControl = new EpiCollect.Field(currentForm);
        }

        var type = jqEle.attr("type");

        $("[allow]").hide();
        $("[notfor]").show();

        $("[allow*=" + type + "]").show();
        $("[notfor*=" + type + "]").hide();


        if(currentControl.isKey)
        {
                $("[allow*=key]").show();
                $("[notfor*=key]").hide();
        }

        if(currentControl.genkey)
        {
                $("[allow*=gen]").show();
                $("[notfor*=gen]").hide();
        }

        $('#inputLabel').val(currentControl.text);
        
        if(currentControl.id && currentControl.id !== '')
        {
                $('#inputId').val(currentControl.id);
        }
        else
        {
                $('#inputId').val(genID);
        }
        
        $("#required").prop("checked", (currentControl.required));
        $("#title").prop("checked", (currentControl.title));
        $("#key").prop("checked", (currentControl.isKey));
        $("#rdo_decimal").prop("checked", currentControl.isdouble);
        $("#rdo_integer").prop("checked", currentControl.isinteger);
        $("#min").val((currentControl.min || currentControl.min === 0) ? currentControl.min : '');
        $("#max").val((currentControl.max || currentControl.max === 0) ? currentControl.max : '');

        if(currentControl.date)$("#date").val(currentControl.date);
        if(!!currentControl.setDate)
        {
            $("#date").val(currentControl.setDate);
            $("#set").prop("checked", true);
        }
        else
        {
            $("#set").prop("checked", false);
        }

        if(currentControl.time) $("#time").val(currentControl.time);
        if(currentControl.setTime)
        {
            $("#time").val(currentControl.setTime);
            $("#set").prop("checked", true);
        }
        else
        {
            if(!currentControl.setDate) $("#set").prop("checked", false);
        }

        $("#default").val(currentControl.defaultValue);
        $("#regex").val(currentControl.regex);
        $("#verify").prop("checked", currentControl.verify);
        $("#hidden").prop("checked", currentControl.hidden );
        $("#genkey").prop("checked", currentControl.genkey );
        $("#search").prop("checked", currentControl.search );

        var opts = currentControl.options;
        var nOpts = currentControl.options.length;

        $(".selectOption").remove();

        while($(".selectOption").length < nOpts) addOption();

        var optEles = $(".selectOption");
        for(var i = nOpts; i--;)
        {
            $("input[name=optLabel]", optEles[i]).val(opts[i].label);
            $("input[name=optValue]", optEles[i]).val(opts[i].value);
        }

        var forms = project.forms;

        if(jqEle.attr("type") === "fk")
        {	
            for(f in forms)
            {
                if(jqEle.prop("id") === forms[f].key){
                    $("#parent").val(f);
                }
            }
        }

        $(".jumpoption").remove();

        if(currentControl.jump)
        {
            var jumps =  currentControl.jump.split(",");
            var nJumps = jumps.length / 2;

            while($(".jumpoption").length < nJumps) addJump();

            updateJumps();
            var jumpCtrls = $(".jumpoption");
            var n = jumps.length;

            for( var i = 0; i < n; i += 2 )
            {
                if(jumps[i+1] === "NULL")
                {
                    $("[name=jumpType]", jumpCtrls[i/2]).val('NULL');
                    $(".jumpvalues", jumpCtrls[i/2]).val('');
                }
                else if(jumps[i+1] === "ALL")
                {
                    $("[name=jumpType]", jumpCtrls[i/2]).val('ALL');
                    $(".jumpvalues", jumpCtrls[i/2]).val('');
                }
                else if(jumps[i+1][0] === "!")
                {
                    $("[name=jumpType]", jumpCtrls[i/2]).val('!');
                    $(".jumpvalues", jumpCtrls[i/2]).val(jumps[i+1].substr(1));
                }
                else
                {
                    $("[name=jumpType]", jumpCtrls[i/2]).val('');
                    $(".jumpvalues", jumpCtrls[i/2]).val(jumps[i+1]);
                }
                $(".jumpdestination", jumpCtrls[i/2]).val(jumps[i]);
            }

        }
    }
    else
    {
    	throw "div is not a form Element!";
    }

    if(currentControl){ $(".last").show();}
    else {$(".last").hide();}

    //set default as integer
    if(type === 'numeric' && !($('#rdo_integer').prop('checked') || $('#rdo_decimal').prop('checked')))
    {
           $('#rdo_integer').prop('checked', 'checked');
    }

    if( currentControl.id === project.getPrevForm(currentForm.name).key )
    {
            $(".removeControl").hide();
            $("#fkPanel").hide();	
    }
    else
    {
            $(".removeControl").show();
    }
}

function removeForm(name)
{
    if(project.getNextForm(name))
    {    
        EpiCollect.dialog({ content : "You can only delete the last form in the project." });
        return;
    }
	
	if(confirm('Are you sure you want to remove the form ' + name + '?'))
	{
		currentForm = false;
		$('#' + name, $("#formList")).remove();
		project.forms[name].num = -1;
		
        
		for( frm in project.forms )
		{
			if( !currentForm ) switchToForm(frm); // switch to the first form
            break;
		}   
        
        delete project.forms[frm];
        
	}
}

function removeSelected()
{
	var jq = $("#destination .selected");
	
	if(currentControl.isKey){
		currentForm.key = null;
		askForKey(true);
	}
	
	delete currentForm.fields[jq.prop("id")];
	jq.remove();
	
	$("[allow]").hide();
	$(".last input[type=text]").val("");
	$(".last input[type=checkbox]").prop("checked", false);
        $('.editmarker').hide();
}

function renameForm(name)
{
	EpiCollect.prompt({ content : 'What would you like to rename the form ' + name + ' to?', callback : function(newName){
		var forms = project.forms;
		var form = forms[name];
		var newForms = {};
        
        var valid = project.validateFormName(newName);
        
        if(valid === true)
        {
            form.name = newName;
            
            for(frm in forms)
            {
                if(frm === name)
                {
                    newForms[newName] = form;
                }
                else
                {
                    newForms[frm] = forms[frm];
                }
            }
            
            project.forms = newForms;
            drawProject(project);
        }
        else
        {
            EpiCollect.dialog({ content : valid });
        }
	}});
}

function switchToBranch()
{
	//var ctrlname = $('destination .selected').attr('id')
	$('.form').removeClass("selected");
	updateSelected();
	
	var frm = currentControl.connectedForm;
	//var par_frm = currentForm.name;
	
	if(!frm || frm === '')
	{
		frm = currentControl.id + "_form";
		currentControl.connectedForm = frm;
	}
	
	if(currentForm){
		updateForm();
		project.forms[currentForm.name] = currentForm;
	}
	
	
	if(!project.forms[frm])
	{
		project.forms[frm] = new EpiCollect.Form();
		project.forms[frm].num = Object.keys(project.forms).length + 1; // Form numbering is 1-indexed not 0-indexed
		project.forms[frm].name = frm;
		
		var key = currentForm.key;
		var fklabel = currentForm.fields[currentForm.key].text;
		var flds = project.forms[frm].fields;
		
		flds[key] = new EpiCollect.Field();
		flds[key].id = key;
		flds[key].isKey = false;
		flds[key].title = false;
		flds[key].type = 'input';
		flds[key].text = fklabel;
		flds[key].form = project.forms[frm];
        
        currentForm = project.forms[frm];
        currentForm.main = false;
        askForKey(false);
	}
    else
    {
         currentForm = project.forms[frm];
	}
	formName = currentForm.name;
	drawFormControls(currentForm);
    
    $('#source .ecplus-branch-element').hide();
    $('#source .ecplus-fk-element').hide();
}

function switchToForm(name)
{
	$('.form').removeClass("selected");
	
	if(currentForm){
		updateForm();
		project.forms[currentForm.name] = currentForm;
	}
	
	$('.form').each(function(idx,ele){
		if($(ele).text() === name) $(ele).addClass("selected");
	});
	
	$("#parent").empty();
	for(frm in project.forms)
	{
		if(frm === name) break;
		
		if(project.forms[frm].main) $("#parent").append("<option value=\"" + frm + "\">" + frm + " (" + project.forms[frm].key + ")</option>");
	}
	
	
	if(!project.forms[name]) project.forms[name] = new EpiCollect.Form();
	currentForm = project.forms[name];
	formName = name;
	
	if( !currentForm.key )
	{
		askForKey();
	}
	$('#source .ecplus-branch-element').show();
    
	if( project.getPrevForm(currentForm.name) )
    {
        $('#source .ecplus-fk-element').show();
    }
    else
    {
         $('#source .ecplus-fk-element').hide();
    }
	drawFormControls(currentForm);
    
}

function askForKey(keyDeleted)
{
	var default_name = currentForm.name + "_key";
	var frm = currentForm;
	
        var possibleFields = '';
        
        for (var f in frm.fields)
        {
            var fld = frm.fields[f];
            if(fld.type == 'input' && !(fld.date || fld.setDate || fld.time || fld.setTime || fld.isKey))
            {
                possibleFields += '<option value="' + fld.id + '">' + fld.text + '</option>'; 
            }
        }
        
	EpiCollect.prompt({
		title : "Add a key field",
        closeable : false,
		content : (!keyDeleted ? "Each EpiCollect+ form must have a unique 'key' question - i.e. one where the value entered by a user will be unique each time a form is filled in.   Do you have a question that will be unique to each form entry?" : "You have deleted the key for this form, please choose a new key field to generate. " ),
		form:(!keyDeleted ? "<form><div id=\"key_radios\" class=\"toggle choice\"> "+
            "<label for=\"key_no\"><b>No</b> I do not have a unique key question for this form, please generate one for me</label><input type=\"radio\" id=\"key_no\" name=\"key\" value = \"no\" checked=\"checked\" />"+
			"<label for=\"key_yes\"><b>Yes</b> I have a unique key question for this form.</label><input type=\"radio\" id=\"key_yes\" name=\"key\" value = \"yes\"/></div>"+
			"<div id=\"key_details\" style=\"display:none;\"><label for=\"key_type\">My key field is a </label><select id=\"key_type\" name=\"key_type\"><option value=\"text\">Text Field</option><option value=\"numeric\">Integer Field</option><option value=\"barcode\">Barcode Field</option></select><p id=\"key_type_err\" class=\"validation-msg\"></p>" + 
            "<label for=\"key_label\">Label for the key field (the question a user is asked e.g. what colour are your eyes?)</label><input id=\"key_label\" name=\"key_label\" /><p id=\"key_label_err\" class=\"validation-msg\"></p>"+
            "<label for=\"key_name\">ID for the key field (a name used to identify the question. e.g. colour)</label><input id=\"key_name\" name=\"key_name\" /><p id=\"key_name_err\" class=\"validation-msg\"></p>"+
			"</div></form>" :"<form><div id=\"key_radios\" class=\"toggle choice\">"+ 
            "<label for=\"key_change\">I want to make another<br /> field the key<br />&nbsp;</label><input type=\"radio\" id=\"key_change\" name=\"key\" value = \"change\"/><label for=\"key_yes\"><b>Yes</b> I have a unique key question for this form.</label><input type=\"radio\" id=\"key_yes\" name=\"key\" value = \"yes\"/>"+
			"<label for=\"key_no\"><b>No</b> I do not have a unique key question for this form, please generate one for me</label><input type=\"radio\" id=\"key_no\" name=\"key\" value = \"no\" checked=\"checked\" /></div>"+  
			"<div id=\"key_details\" style=\"display:none;\"><label for=\"key_type\">My key field is a </label><select id=\"key_type\" name=\"key_type\"><option value=\"text\">Text Field</option><option value=\"numeric\">Integer Field</option><option value=\"barcode\">Barcode Field</option></select><p id=\"key_type_err\" class=\"validation-msg\"></p>" + 
            "<label for=\"key_label\">Label for the key field (the question a user is asked e.g. what colour are your eyes?)</label><input id=\"key_label\" name=\"key_label\" /><p id=\"key_label_err\" class=\"validation-msg\"></p>"+
            "<label for=\"key_name\">ID for the key field (a name used to identify the question. eg colour)</label><input id=\"key_name\" name=\"key_name\" /><p id=\"key_name_err\" class=\"validation-msg\"></p>"+
			"</div>"+
            "<div id=\"select_key\" style=\"display:none;\"> Please make this field the key <select id=\"new_key\" name=\"new_key\">" + possibleFields + "</select>"+
            "</form>"),
		buttons : {
			"OK" : function(){
                $('.validation-msg').text('').removeClass('err');
                            
				var raw_vals = $('form', this).serializeArray();
				var vals = {};
				
				for(var v = 0; v < raw_vals.length; v++)
				{
					vals[raw_vals[v].name] = raw_vals[v].value;
				}
				
                var key_id = '';
                var new_field = new EpiCollect.Field();
                new_field.isKey = true;
                new_field.title = false;
                
                
                if(vals.key === 'yes')
                {
                    key_id = vals.key_name ;
                    new_field.id = vals.key_name;
                    new_field.text =  vals.key_label;
                    new_field.type = vals.key_type === 'barcode' ? 'barcode' : 'input';
                    new_field.form = frm;
                    new_field.isInt = (vals.key_type === 'numeric');
                    new_field.genkey = false;
                    
                }
                else if(vals.key ==='no')
                {
                    key_id = default_name ;
                    new_field.id = key_id;
                    new_field.text = 'Unique ID';
                    new_field.type = 'input';
                    new_field.form = currentForm;
                    new_field.isInt = false;
                    new_field.genkey = true;
                    vals.key_type = 'text';
                }
                else
                {
                    key_id = vals.new_key;
                    new_field.isKey = true; 
                }
                
                currentForm.fields[key_id] = new_field;
                
                var fieldNameValid = project.validateFieldName(frm, new_field);
                
                if(vals.key !== "yes" || (fieldNameValid === true && vals.key_label !== '' && vals.key_type !== ''))
                {
                    if( vals.key !== 'change' ) addControlToForm(new_field.id, new_field.text, vals.key_type);
                    currentForm.key = key_id;		
                    setSelected($('#' + key_id));
                    $( this ).dialog("close");
                }
                else
                {
                    if(fieldNameValid !== true) $('#key_name_err').html(fieldNameValid).addClass('err');
                    if(vals.key_label === '') $('#key_label_err').html("The field must have a a label").addClass('err');
                    if(vals.key_type === '') $('#key_type_err').html("You must select a key type").addClass('err');
                }
			}
		}
	});
    
	$('#key_radios input[type=radio]').on('change', function(){
		$('#key_details').toggle(this.id === "key_yes");
                $('#select_key').toggle(this.id === "key_change");
	});
}

function saveProject()
{
	
	if(!updateSelected()) return;
	if(!updateForm()) return;
	
	var loader = new EpiCollect.LoadingOverlay();
	loader.setMessage('Saving...');
	loader.start();
	window.loader = loader;
	
	$.ajax("./updateStructure" ,{
		type : "POST",
		data : {data : project.toXML(), skipdesc : true},
		success : saveProjectCallback,
		error : saveProjectError
	});
        
        // remove the local temporary version
        localStorage.removeItem(project.name + '_xml');
        $('.unsaved').removeClass('unsaved');
}

function saveProjectCallback(data, status, xhr)
{
	var result = JSON.parse(data);
	window.loader.stop();
	
	if(result.result)
	{
		
		new  EpiCollect.dialog({content:"Project Saved"});
                $('.unsaved').removeClass('unsaved');
	}
	else
	{
		EpiCollect.dialog({content : "Project not saved : " + result.message });
	}
}

function saveProjectError(xhr, status, err)
{
	EpiCollect.dialog({content : "Project not saved : " + status });
}
