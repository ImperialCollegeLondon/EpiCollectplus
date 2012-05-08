var currentForm = undefined;
var currentControl = undefined;
var formName = undefined;

$(function()
{
	var url = location.href;
	EpiCollect.loadProject(url.substr(0, url.lastIndexOf("/")) + ".xml", drawProject);
	
	$(window).scroll(function(evt){
		if($(document.body).scrollTop() > $("#toolbox")[0].offsetTop) 
		{  
			$("#toolbox-inner").css({
				"position":"fixed",
				"top" : "0px"
			}); 
		}else{
			$("#toolbox-inner").css({
				"position":"relative"
			}); 
		}
			
	});
	
	$("[allow]").hide();
	$("[notfor]").show();
	
	$('#destination').sortable({
		revert : 50,
		start : function(evt, ui)
		{
			ui.placeholder.css("visibility", "");
			ui.placeholder.css("background-color", "#CCFFCC");
		},
		stop : function(evt, ui)
		{
			if(!currentForm)
			{
				alert("You need to choose a form in order to change the controls on it.")
				$("#destination div").remove();
			}
			else
			{
				setSelected($(ui.item));
			}
		}
	});
	
	$(".ecplus-form-element").draggable({
		connectToSortable: "#destination",
		helper: "clone",
		revert: "invalid",
		revertDuration : 100
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
				/*if(currentForm){
					updateForm();
					project.forms[currentForm.name] = currentForm;
				}
				sp.addClass('selected');
				currentForm = project.forms[sp.text()];
				formName = currentForm.name;
				drawFormControls(currentForm);*/
			}
		}
	});
	
	$("#key").change(function(evt){
		if(evt.target.checked)
		{
			$("[allow=key]").show();
		}
		else
		{
			$("[allow=key]").hide();
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
	var name = prompt("What would you like to call the new form?" + message, name);
	if(name && project.validateFormName(name))
	{
		addFormToList(name);
		var frm = new EpiCollect.Form();
		frm.name = name;
		frm.num = project.forms.length + 1;
		project.forms[name] = frm;
		
		
	}
	else if(name)
	{
		newForm("The form name must only contain letters, numbers and _ or - and be unique within this project.", name);
	}
}

function addFormToList(name)
{
	$("#formList .control").before("<span class=\"form\">" + name + "</span>");
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
	if(type.trim() == "") return; 
	
	if(type[0] != ".") type = "." + type;
	var jq = $(type, $(".first")).clone();
	$("p.title", jq).text(text);
	jq.attr("id", id);
	
	$(".option", jq).remove();
	
	var opts = currentForm.fields[id].options;
	var l = opts.length;
	for(var i = 0; i < l; i++)
	{
		jq.append("<p class=\"option\">" + opts[i].label + "</p>");
	}
	
	$("#destination").append(jq);
}

function addOption()
{
	var panel = $("#options");
	panel.append('<div class="selectOption"><hr /><a href="http://www.epicollect.net/formHelp.asp#editSelects" target="_blank">Label</a><input name="optLabel" size="12" /><div style="float:right; font-weight:bold;font-size:10pt;"><a href="javascript:void(0)" onclick="popup($(this).parent(),\'Option &gt; Name\', \'The label displayed to the user\')">?</a></div>	<br /><a href="http://www.epicollect.net/formHelp.asp#editSelects" target="_blank">Value</a><input name="optValue" size="12" /><div style="float:right; font-weight:bold;font-size:10pt;"><a href="javascript:void(0)" onclick="popup($(this).parent(),\'Option &gt; Value\', \'The value entered into the database\')">?</a></div>	<br><a href="javascript:void(0);" class="button removeOption" >Remove Option</a> </div>');
	
	$("#options .removeOption").unbind('click').bind('click', removeOption);
}

function removeOption(evt)
{
	var ele = evt.target;
	while(ele.tagName != "DIV") ele = ele.parentNode;
	
	$(ele).remove();
}

function addJump()
{
	var panel = $("#jumps");
	panel.append('<div class="jumpoption"><hr /><form><input type="radio" name="jumpType" value="is" selected="selected"> Jump when value is<br/><input type="radio" name="jumpType" value="not"> Jump when value is not<br/></form><label>Value</label> <select class="jumpvalues"></select><br /><label>Jump to</label> <select class="jumpdestination"></select><br /><a href="javascript:void(0);" class="button remove" >Remove Jump</a></div>');
	
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
				if(f != form.name && fld.id == forms[f].key) cls = "ecplus-fk-element";
			}
		}
		else cls = "ecplus-" + fld.type + "-element";
		
		addControlToForm(fld.id, fld.text, cls);		
	}
	
}

function updateSelected()
{
	var jq = $("#destination .selected");
	
	if(jq == undefined || jq.length == 0) return;
	
	var name = currentControl.id; 

	currentControl.id = $('#inputId').val();
	currentControl.text = $('#inputLabel').val();
	
	if(jq.attr("type").match(/^(text|numeric|date|time|fk)/))
	{
		currentControl.type = "input";
		if(jq.attr("type") == "fk")
		{
			var f = $("#parent").val();
			var frm = project.forms[f]
			currentControl.id = frm.key;
			currentControl.text = frm.fields[frm.key].text;
		}
	}
	else{ currentControl.type = jq.attr("type"); }
	
	var notset = !$("#set").attr("checked");
	//TODO: need to set other params;
	var fk = $("#parent").val();
	
	currentControl.required = !!$("#required").attr("checked");
	currentControl.title = !!$("#title").attr("checked");
	currentControl.isKey = !!$("#key").attr("checked");
	currentControl.regex = $("#regex").val();
	currentControl.verify = !!$("#verify").attr("checked");
	currentControl[(notset ? "date": "setDate")] = $("#date").val(); 
	currentControl[(notset ? "time": "setTime")] = $("#time").val();
	currentControl[(notset ? "setDate" : "date")] = false; 
	currentControl[(notset ? "setTime": "time")] = false;
	currentControl.genkey = !!$("#genkey").attr("checked");
	currentControl.hidden = !!$("#hidden").attr("checked");
	currentControl.isinteger = !!$("#integer").attr("checked");
	currentControl.isdouble = !!$("#decimal").attr("checked");
	currentControl.min = $("#min").val();
	currentControl.max = $("#max").val();
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
	
	//TODO: get and add jumps
	
	var jump = "";
	var jumpCtrls = $(".jumpoption");
	var jn = jumpCtrls.length;
	
	for(var i = jn; i--;)
	{
		jump = $(".jumpdestination", jumpCtrls[i]).val() + ","  + $(".jumpvalues", jumpCtrls[i]).val() + "," + jump;
	}
	
	currentControl.jump = jump.trim(",");
	
	if(name !== currentControl.id)
	{
		delete currentForm.fields[name];
	}
	currentForm.fields[currentControl.id] = currentControl;
	
	jq.attr("id", currentControl.id);
	$("p.title", jq).text(currentControl.text);
	
	$(".option", jq).remove();
	
	var opts = currentControl.options;
	var l = opts.length;
	for(var i = 0; i < l; i++)
	{
		jq.append("<p class=\"option\">" + opts[i].label + "</p>");
	}
}

function updateForm()
{
	updateSelected();	
	
	var fields = {};
	var form = currentForm;
	
	var elements = $("#destination div");
	for(var i = 0; i < elements.length; i++)
	{
		var id = elements[i].id;
		fields[id] = form.fields[id]; 
		fields[id].position;
		if(fields[id].isKey) form.key = id;
	}
	
	currentForm.fields = fields;
}

function updateJumps()
{
	var opts = currentControl.options;
	
	var fieldCtls = $(".jumpvalues");
	fieldCtls.empty();
	fieldCtls.html("<option value=\"any\" style=\"font-style:italic;\">Any Value</option>" + fieldCtls.html());
	for(var i = opts.length; i--;)
	{
		fieldCtls.html("<option value=\"" + i + "\">" + opts[i].label + "</option>" + fieldCtls.html());
	}
	
	fieldCtls = $(".jumpdestination");
	
	for(fld in currentForm.fields)
	{
		var field = currentForm.fields[fld];
		var lbl = currentForm.fields[fld].text;
		if(lbl.length > 25) lbl = lbl.substr(0,22) + "...";
		if(field.type && !field.hidden) fieldCtls.append("<option value=\"" + fld + "\">" + lbl + "</option>");
	}
	
}

function setSelected(jqEle)
{
	if(window["currentControl"])
	{
		updateSelected();
		$(".last input[type=text]").val("");
		$(".last input[type=checkbox]").attr("checked", false);
	}
	
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
		$("[allow=key]").show();
	}
	if(jqEle.hasClass("ecplus-form-element"))
	{
		$("#parent").val("");
		$("#destination .ecplus-form-element").removeClass("selected");
		jqEle.addClass("selected");
		
		$('#inputLabel').val(currentControl.text);
		$('#inputId').val(currentControl.id);
		
		$("#required").attr("checked", (currentControl.required));
		$("#title").attr("checked", (currentControl.title));
		$("#key").attr("checked", (currentControl.isKey));
		$("#decimal").attr("checked", currentControl.isdouble);
		if(! currentControl.isdouble) $("#integer").attr("checked", currentControl.isinteger);
		$("#min").val(currentControl.min);
		$("#max").val(currentControl.max);
		
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
		
		//TODO: Jumps
		$(".jumpoption").remove();
		
		if(!currentControl.jump) return;
		var jumps =  currentControl.jump.split(",");
		var nJumps = jumps.length / 2;
		
		while($(".jumpoption").length < nJumps) addJump();
		
		updateJumps();
		var jumpCtrls = $(".jumpoption");
		var n = jumps.length
		for(var i = 0; i < n; i += 2)
		{
			$(".jumpvalues", jumpCtrls[i/2]).val(jumps[i+1]);
			$(".jumpdestination", jumpCtrls[i/2]).val(jumps[i]);
		}
		
		
		
	}
	else
	{
		throw "div is not a form Element!";
	}
	
	if(currentControl) $(".last").show();
	else $(".last").hide();
	
}

function removeSelected()
{
	var jq = $("#destination .selected")
	delete currentForm.fields[jq.attr("id")];
	jq.remove();
	
	$("[allow]").hide();
	$(".last input[type=text]").val("");
	$(".last input[type=checkbox]").attr("checked", false);
}

function switchToBranch()
{
	$('.form').removeClass("selected");
	
	if(currentForm){
		updateForm();
		project.forms[currentForm.name] = currentForm;
	}
	
	var frm = currentControl.connectedForm;
	if(!project.forms[frm]) project.forms[frm] = new EpiCollect.Form();
	currentForm = project.forms[frm];
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
	updateSelected();
	updateForm();
	
	$.ajax("./updateStructure" ,{
		type : "POST",
		data : {data : project.toXML()},
		success : saveProjectCallback,
		error : saveProjectError
	});
}

function saveProjectCallback(data, status, xhr)
{
	var result = JSON.parse(data);
	
	if(result.result)
	{
		alert("Project Saved");
	}
	else
	{
		alert("Project not saved : " + result.message);
	}
}

function saveProjectError(xhr, status, err)
{
	alert("Project not saved : " + status);
}
