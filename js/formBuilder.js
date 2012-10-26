var currentForm = undefined;
var currentControl = undefined;
var formName = undefined;

$(function()
{
	var url = location.href;
	EpiCollect.loadProject(url.substr(0, url.lastIndexOf("/")) + ".xml", drawProject);
	
	var details_top = $("#details").offset().top;
	var toolbox_top =  $("#toolbox").offset().top;
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
			$("#details").css({
				"position":"fixed",
				"top" : $("#toolbox-inner").height() + 10 + "px",
				"right" : "25px"
			}); 
		}
		else
		{
			$("#details").css({
				"position":"absolute",
				"top" : "0px",
				"right" : "0px"
			}); 
		}	
	});
	
	$('.first').accordion({ collapsible : true });
	
	$("[allow]").hide();
	$("[notfor]").show();
	
	$('#destination').sortable({
		revert : 50,
		tolerance : 'pointer',
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
				setSelected($(ui.item));
				var jq = $('#destination .end').remove();
				$('#destination').append(jq[0]);
			}
		}
	});
	
	$(".ecplus-form-element").draggable({
		connectToSortable: "#destination",
		helper: "clone",
		revert: "invalid",
		revertDuration : 100,
		appendTo : 'body'
	});
	
	$('#destination').click(function(evt){
		var div = evt.target;
		while(div.tagName != "DIV") { div = div.parentNode;}
		
		var jq = $(div);
		if(jq.hasClass("ecplus-form-element"))
		{
			setSelected(jq);
		}
	});
	
	$('#formList').click(function(evt){
		var sp = evt.target;
		
		if(sp.tagName == "SPAN")
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
});

function drawProject(project)
{
	$("#formList .form").remove();
	
	for(var frm in project.forms)
	{
		if(project.forms[frm].main)
			addFormToList(frm);
	}
}

/**
 * 
 * @param message
 * @param name
 */
function newForm(message, name)
{
	if(!message) 
	{
		message = "";
	}
	else
	{
		message = "\r\n\r\n" + message;
	}
	var name = EpiCollect.prompt({ content : "What would you like to call the new form?" + message, callback : function(name){ 
		if(name && project.validateFormName(name))
		{
			
			var frm = new EpiCollect.Form();
			frm.name = name;
			frm.num = $('.form').length + 1;
			project.forms[name] = frm;
			
			addFormToList(name);
		}
		else if(name)
		{
			newForm("The form name must only contain letters, numbers and _ or - and be unique within this project.", name);
		}
		
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
		
	}});
	
}

function addFormToList(name)
{
	$("#formList .control").before("<span id=\"" + name + "\" class=\"form\">" + name + "</span>");
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
	if(type.trimChars() == "") return; 
	
	if(type[0] != ".") type = "." + type;
	var jq = $(type, $(".first")).clone();
	$("p.title", jq).text(text);
	jq.attr("id", id);
	
	$(".option", jq).remove();

	if(type.match(/select1?|radio/))
	{
		var opts = currentForm.fields[id].options;
		var l = opts.length;
		for(var i = 0; i < l; i++)
		{
			jq.append("<p class=\"option\">" + opts[i].label + "</p>");
		}
	}
	
	$("#destination").append(jq);
}

function addOption()
{
	var panel = $("#options");
	panel.append('<div class="selectOption"><hr /><a href="http://www.epicollect.net/formHelp.asp#editSelects" target="_blank">Label</a><input name="optLabel" size="12" /><div style="float:right; font-weight:bold;font-size:10pt;"><a href="javascript:void(0)" onclick="popup($(this).parent(),\'Option &gt; Name\', \'The label displayed to the user\')">?</a></div>	<br /><a href="http://www.epicollect.net/formHelp.asp#editSelects" target="_blank">Value</a><input name="optValue" size="12" /><div style="float:right; font-weight:bold;font-size:10pt;"><a href="javascript:void(0)" onclick="popup($(this).parent(),\'Option &gt; Value\', \'The value entered into the database\')">?</a></div>	<br><a href="javascript:void(0);" class="button removeOption" >Remove Option</a> </div>');
	
	$("#options .removeOption").unbind('click').bind('click', removeOption);
	
	$("#options input").unbind();
	$("#options input").change(function(e)
	{
		updateSelected();
		updateJumps();
	});
}

function removeOption(evt)
{
	var ele = evt.target;
	while(ele.tagName != "DIV") ele = ele.parentNode;
	
	$(ele).remove();
	updateJumps();
}

function addJump()
{
	var panel = $("#jumps");
	
	var sta = '<div class="jumpoption"><hr /><label>Jump when </label><select name="jumpType"><option value="">value is</option><option value="!">value is not</option><option value="NULL">field is blank</option><option value="ALL">field has any value</option></select>';
	
	if(currentControl.type == 'input')
	{
		panel.append(sta + '<label>Value</label><input type="text" class="jumpvalues" /><br /><label>Jump to</label> <select class="jumpdestination"></select><br /><a href="javascript:void(0);" class="button remove" >Remove Jump</a></div>');
	}
	else
	{
		panel.append(sta + '<label>Value</label> <select class="jumpvalues"></select><br /><label>Jump to</label> <select class="jumpdestination"></select><br /><a href="javascript:void(0);" class="button remove" >Remove Jump</a></div>');
	}
	
	$("#jumps .remove").unbind('click').bind('click', removeJump);
}

function removeJump(evt)
{
	var ele = evt.target;
	while(ele.tagName != "DIV") ele = ele.parentNode;
	
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
		
		if(fld.type == "input")
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
			for(f in forms)
			{
				if(f != form.name && fld.id == forms[f].key && fld.form.num > forms[f].num) cls = "ecplus-fk-element";
			}
		}
		else cls = "ecplus-" + fld.type + "-element";
		
		addControlToForm(fld.id, fld.text, cls);		
	}
	
}

function updateSelected()
{
	var jq = $("#destination .selected");
	
	if(jq == undefined || jq.length == 0) return true;
	
	var name = currentControl.id; 
	var _type = jq.attr("type");
	
	if(_type == 'fk')
	{
		currentControl.id = project.forms[$('#parent').val()].key;
	}
	else
	{
		currentControl.id = $('#inputId').val();
		currentControl.text = $('#inputLabel').val();
		if(_type == "date" || _type == "time")
		{
			var suf = '(' + $('#' + _type).val() + ')';
			var rxsuf = '\\(' + $('#' + _type).val().replace(/\//g, '\\/') + '\\)';
			
			//console.debug (new RegExp(rxsuf +'$', 'g'));
			if(!currentControl.text.match(new RegExp(rxsuf +'$', 'g')))
			{
				currentControl.text = currentControl.text + ' ' + suf;
			}
		}
	}
	
	if(!currentForm.validateFieldName(currentControl.id, name))
	{
		EpiCollect.dialog({ content : 'Field name must be unique within the form, not the same as the form name and not one of ' + EpiCollect.KEYWORDS.join(', ') });
		return false;
	}
	
	if(_type.match(/^(text|numeric|date|time|fk)$/))
	{
		currentControl.type = "input";
		if(_type == "fk")
		{
			var f = $("#parent").val();
			var frm = project.forms[f]
			currentControl.id = frm.key;
			currentControl.text = frm.fields[frm.key].text;
		}
	}
	else{ currentControl.type = _type; }
	
	var notset = !$("#set").attr("checked");
	//TODO: need to set other params;
	var fk = $("#parent").val();
	
	currentControl.required = !!$("#required").attr("checked");
	currentControl.title = !!$("#title").attr("checked");
	if($("#key").attr("checked") == "checked")
	{
		currentControl.isKey = true;
		currentForm.key = currentControl.id;
	}
	else
	{
		currentControl.isKey = false;
	}
	
	//console.debug()
	
	currentControl.regex = $("#regex").val();
	currentControl.verify = !!$("#verify").attr("checked");
	currentControl.date = false;
	currentControl.time = false;
	currentControl.setDate = false;
	currentControl.setTime = false;
	currentControl.isdouble = false;
	currentControl.isinteger = false;
	currentControl.min = false;
	currentControl.max = false;
	
	if(_type == 'date')
	{
		if($("#date").val() == "")
		{
			EpiCollect.dialog({ content : "You must select a date format." });
			throw "You must select a date format.";
		}
		currentControl[(notset ? "date": "setDate")] = $("#date").val();
	}
	else if(_type == 'time')
	{
		if($("#time").val() == "")
		{
			EpiCollect.dialog({ content : "You must select a time format." });
			throw "You must select a time format.";
		}
		currentControl[(notset ? "time": "setTime")] = $("#time").val();
	}
	else if(_type == 'numeric')
	{
		currentControl.isinteger = !!$("#integer").attr("checked");
		currentControl.isdouble = !!$("#decimal").attr("checked");
	
		currentControl.min = $("#min").val();
		currentControl.max = $("#max").val();
	}
	currentControl.genkey = !!$("#genkey").attr("checked");
	currentControl.hidden = !!$("#hidden").attr("checked");
	
	if( $("#default").val() !== '' && !currentControl.validate($("#default").val()) ) throw 'Default value does not match the format of the control';
	currentControl.defaultValue = $("#default").val();
	currentControl.search = !!$("#search").attr("checked");
	
	//TODO: get and add options
	var optCtrls = $(".selectOption");
	var options = [];
	
	var n = optCtrls.length;
	for(var i = 0; i < n; i++)
	{
		options[i] = { label : $("input[name=optLabel]", optCtrls[i]).val(), value : $("input[name=optValue]", optCtrls[i]).val() };
	}
	currentControl.options = options;
	
	var jump = "";
	var jumpCtrls = $(".jumpoption");
	var jn = jumpCtrls.length;
	
	for(var i = jn; i--;)
	{
		var jumpType = $('[name=jumpType]', jumpCtrls[i]).val();
		var jval = (jumpType.length > 1 ? jumpType :  jumpType + (Number($(".jumpvalues", jumpCtrls[i]).val())));
		
		jump = $(".jumpdestination", jumpCtrls[i]).val() + ","  + jval + (jump == "" ? "" : "," + jump);
	}
	
	currentControl.jump = jump.trimChars(",");
	
	jq.attr("id", currentControl.id);
	$("p.title", jq).text(currentControl.text);
	
	$(".option", jq).remove();
	
	if(currentControl.type.match(/select1?|radio/))
	{
		var opts = currentControl.options;
		var l = opts.length;
		for(var i = 0; i < l; i++)
		{
			jq.append("<p class=\"option\">" + opts[i].label + "</p>");
		}
	}
	else
	{
		currentControl.options = [];
	}
	
	if(name !== currentControl.id)
	{
		var newFlds = {};
		var prevFlds = currentForm.fields;
		
		$('#destination .ecplus-form-element').each(function(idx, ele){
			if( $(ele).hasClass('selected') )
			{
				newFlds[ele.id] = currentControl;
			}
			else
			{
				newFlds[ele.id] = prevFlds[ele.id];
			}
		});
		
		currentForm.fields = newFlds;
		//delete currentForm.fields[name];
		//currentForm.fields[currentControl.id] = currentControl;
	}
	
	return true;
}

function updateSelectedCtl(){
	updateSelected();
}

function updateForm()
{
	if(!currentForm) return true;
	if(!updateSelected()) return false;	
	if(!currentForm.key)
	{
		EpiCollect.dialog({ content : "The form " + currentForm.name + " needs a key defined." });
		throw "The form " + currentForm.name + " needs a key defined.";
		return false;
	}
	
	var fields = {};
	var form = currentForm;
	
	var elements = $("#destination div");
	for(var i = 0; i < elements.length && form; i++)
	{
		var id = elements[i].id;
		fields[id] = form.fields[id]; 
		
		if(fields[id].isKey) form.key = id;
	}
	
	currentForm.fields = fields;
	return true;
}

function updateJumps()
{
	try{
	updateForm();
		
	var opts = currentControl.options;
	
	var fieldCtls = $(".jumpvalues");
	
	var vals = [];
	
	
	fieldCtls.each(function(idx, ele){
		vals[idx] = $(ele).val();
	});
	
	fieldCtls.empty();
	fieldCtls.html(fieldCtls.html());
	for(var i = opts.length; i--;)
	{
		fieldCtls.html("<option value=\"" + (i + 1) + "\" >" + opts[i].label + "</option>" + fieldCtls.html());
	}
	
	$(".jumpvalues").each(function(idx, ele){
		 $(ele).val(vals[idx]);
	});
	
	fieldCtls = $(".jumpdestination");
	
	vals = [];
	
	fieldCtls.each(function(idx, ele){
		vals[idx] = $(ele).val();
	});
	
	for(fld in currentForm.fields)
	{
		var field = currentForm.fields[fld];
		var lbl = currentForm.fields[fld].text;
		if(lbl.length > 25) lbl = lbl.substr(0,22) + "...";
		if(field.type && !field.hidden) fieldCtls.append("<option value=\"" + fld + "\">" + lbl + "</option>");
	}
	fieldCtls.append("<option value=\"END\">END OF FORM</option>");
	$(".jumpdestination").each(function(idx, ele){
		 var jq = $(ele);
		 var opts = $('option', jq);
		 var len = opts.length;
		 
		 var show = false;
		 var cField = $('.ecplus-form-element.selected').attr('id');
		 var fidx;
		 
		 for(var i = 0; i < len; i++ )
		 {
			$(opts[i]).attr('disabled', !show);
			if( opts[i].value == cField ) {
				// hide the next + 1 element as there's no point jumping to the next question!
				$(opts[++i]).attr('disabled', !show);
				show = true;
			}
		 }
		 if(vals.length > idx) jq.val(vals[idx]);
	});
	}catch(err)
	{
		/*alert(err)*/;
	}
}

function updateLastJump()
{
	var opts = currentControl.options;
	
	var fieldCtls = $(".jumpvalues:last");
	
	fieldCtls.empty();
	fieldCtls.html(fieldCtls.html());
	for(var i = opts.length; i; i--)
	{
		fieldCtls.html("<option value=\"" + i + "\">" + opts[i-1].label + "</option>" + fieldCtls.html());
	}
	
	fieldCtls = $(".jumpdestination:last");
	
	var cField = $('.ecplus-form-element.selected').attr('id');
	var hide = true;
	var val;
	
	for(fld in currentForm.fields)
	{
		var field = currentForm.fields[fld];
		var lbl = currentForm.fields[fld].text;
		if(lbl.length > 25) lbl = lbl.substr(0,22) + "...";
		
		if(field.type && !field.hidden)
		{
			if(hide)
			{
				fieldCtls.append("<option value=\"" + fld + "\" style=\"display:none;\">" + lbl + "</option>");
			}
			else
			{
				if(!val) val = fld;
				fieldCtls.append("<option value=\"" + fld + "\">" + lbl + "</option>");
			}
		}
		hide = hide && fld != cField;
	}
	fieldCtls.append("<option value=\"END\">END OF SURVEY</option>");
	fieldCtls.val(val);
}

function genID()
{
	var x = $('#destination .ecplus-form-element').length
	var name= 'ecplus-' + currentForm.name + '-ctrl' + x;
	for(; currentForm.fields[name]; x++)
	{
		name = 'ecplus-' + currentForm.name + '-ctrl' + x;
	}
	return name;
}

function setSelected(jqEle)
{
	try{
		if(window["currentControl"])
		{
			if(!updateSelected()) return;
			$(".last input[type=text]").val("");
			$(".last input[type=checkbox]").attr("checked", false);
			$('#inputId').val(genID);
		}
		
		$('#date').val('');
		$('#time').val('');
		
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
		
		if(jqEle.hasClass("ecplus-form-element"))
		{
		
			$("#parent").val("");
			$("#destination .ecplus-form-element").removeClass("selected");
			jqEle.addClass("selected");
			
			$('#inputLabel').val(currentControl.text);
			if(currentControl.id && currentControl.id != '')
			{
				$('#inputId').val(currentControl.id);
			}
			else
			{
				$('#inputId').val(genID);
			}
			$("#required").attr("checked", (currentControl.required));
			$("#title").attr("checked", (currentControl.title));
			$("#key").attr("checked", (currentControl.isKey));
			$("#decimal").attr("checked", currentControl.isdouble);
			if(! currentControl.isdouble) $("#integer").attr("checked", currentControl.isinteger);
			$("#min").val(currentControl.min ? Number(currentControl.min) : '');
			$("#max").val(currentControl.max ? Number(currentControl.max) : '');
			
			if(currentControl.date)$("#date").val(currentControl.date);
			if(!!currentControl.setDate)
			{
				$("#date").val(currentControl.setDate);
				$("#set").attr("checked", true);
			}
			else
			{
				$("#set").attr("checked", false);
			}
			
			if(currentControl.time) $("#time").val(currentControl.time);
			if(currentControl.setTime)
			{
				$("#time").val(currentControl.setTime);
				$("#set").attr("checked", true);
			}
			else
			{
				if(!currentControl.setDate) $("#set").attr("checked", false);
			}
	
			$("#default").val(currentControl.defaultValue);
			$("#regex").val(currentControl.regex);
			$("#verify").attr("checked", currentControl.verify);
			$("#hidden").attr("checked", currentControl.hidden);
			$("#genkey").attr("checked", currentControl.genkey);
			$("#search").attr("checked", currentControl.search);
			
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
			
			if(jqEle.attr("type") == "fk")
			{	
				for(f in forms)
				{
					if(jqEle.attr("id") == forms[f].key){
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
				var n = jumps.length
				
				for( var i = 0; i < n; i += 2 )
				{
					if(jumps[i+1] == "NULL")
					{
						$("[name=jumpType]", jumpCtrls[i/2]).val('NULL');
						$(".jumpvalues", jumpCtrls[i/2]).val('');
					}
					else if(jumps[i+1] == "ALL")
					{
						$("[name=jumpType]", jumpCtrls[i/2]).val('ALL');
						$(".jumpvalues", jumpCtrls[i/2]).val('');
					}
					else if(jumps[i+1][0] == "!")
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
	
		if(type == 'numeric' && !($('#integer').attr('checked') || $('#decimal').attr('checked')))
		{
			$('#integer').attr('checked', 'checked');
		}
		
		if( currentControl.id == project.getPrevForm(currentForm.name).key )
		{
			$(".removeControl").hide();
			$("#fkPanel").hide();	
		}
		else
		{
			$(".removeControl").show();
		}
	}catch(err){/*alert(err)*/;}
}

function removeForm(name)
{
	
	if(confirm('Are you sure you want to remove the form ' + name + '?'))
	{
		currentForm = false;
		$('#' + name, $("#formList")).remove();
		var key = project.forms[name].key;
		var num = project.forms[name].num;
		project.forms[name].num = -1;
		
		for( frm in project.forms )
		{
			if(!currentForm) switchToForm(frm);
			for( fld in project.forms[frm].fields )
			{
				if( fld == key )
				{
					delete project.forms[frm].fields[fld];
				}
			}
			if(project.forms[frm].num > num)
			{
				project.forms[frm].num = Number(project.forms[frm].num) - 1;
			}
		}
		$('#destination').empty();
	}
}

function removeSelected()
{
	var jq = $("#destination .selected")
	
	if(currentControl.isKey) currentForm.key = null;
	
	delete currentForm.fields[jq.attr("id")];
	jq.remove();
	
	$("[allow]").hide();
	$(".last input[type=text]").val("");
	$(".last input[type=checkbox]").attr("checked", false);
}

function renameForm(name)
{
	EpiCollect.prompt({ content : 'What would you like to rename the form ' + name + ' to?', callback : function(newName){
		var forms = project.forms;
		var form = forms[name];
		var newForms = {};
		form.name = newName;
		
		for(frm in forms)
		{
			if(frm == name)
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
	}});
}

function switchToBranch()
{
	var ctrlname = $('destination .selected').attr('id')
	$('.form').removeClass("selected");
	updateSelected();
	
	var frm = currentControl.connectedForm;
	var par_frm = currentForm.name;
	
	if(!frm || frm == '')
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
		project.forms[frm].num = Object.keys(project.forms).length;
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
	}
	currentForm = project.forms[frm];
	currentForm.main = false;
	formName = currentForm.name;
	drawFormControls(currentForm);
}

function switchToForm(name)
{
	$('.form').removeClass("selected");
	
	if(currentForm){
		updateForm();
		project.forms[currentForm.name] = currentForm;
	}
	
	$('.form').each(function(idx,ele){
		if($(ele).text() == name) $(ele).addClass("selected");
	});
	
	$("#parent").empty();
	for(frm in project.forms)
	{
		if(frm == name) break;
		
		if(project.forms[frm].main) $("#parent").append("<option value=\"" + frm + "\">" + frm + " (" + project.forms[frm].key + ")</option>");
	}
	
	
	if(!project.forms[name]) project.forms[name] = new EpiCollect.Form();
	currentForm = project.forms[name];
	formName = name;
	drawFormControls(currentForm);
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
}

function saveProjectCallback(data, status, xhr)
{
	var result = JSON.parse(data);
	window.loader.stop();
	
	if(result.result)
	{
		
		new  EpiCollect.dialog({content:"Project Saved"});
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
