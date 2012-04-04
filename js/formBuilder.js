var currentForm = undefined;
var currentControl = undefined;
var formName = undefined;

$(function()
{
	var url = location.href;
	EpiCollect.loadProject(url.substr(0, url.lastIndexOf("/")) + ".xml", drawProject);
	
	$("[allow]").hide();
	
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
				if(currentForm){
					updateForm();
					project.forms[currentForm.name] = currentForm;
				}
				sp.addClass('selected');
				currentForm = project.forms[sp.text()];
				formName = currentForm.name;
				drawFormControls(currentForm);
			}
		}
	});
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
		frm.name = frm;
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
 * Funtion to add the field representation onto the form
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
	$("p", jq).text(text);
	jq.attr("id", id);
	
	$("#destination").append(jq);
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
	
	if(jq.attr("type").match(/^(text|numeric|date|time)/)) currentControl.type = "input";
	else currentControl.type = jq.attr("type");
	
	//TODO: need to set other params;
	currentControl.required = !!$("#required").attr("checked");
	currentControl.title = !!$("#title").attr("checked");
	currentControl.regex = $("#regex").val();
	currentControl.verify = !!$("#verify").attr("checked");
	currentControl[(!!$("#set").attr("checked") ? "date": "setDate")] = $("#date").val(); 
	currentControl[(!!$("#set").attr("checked") ? "time": "setTime")] = $("#time").val();
	currentControl.genKey = !!$("#genkey").attr("checked");
	currentControl.hidden = !!$("#hidden").attr("checked");
	currentControl.isinteger = !!$("#integer").attr("checked");
	currentControl.isdouble = !!$("#decimal").attr("checked");
	currentControl.min = $("#min").val();
	currentControl.max = $("#max").val();
	currentControl.defaultValue = $("#default").val();
	currentControl.search = !!$("#search").attr("checked");
	
	if(name !== currentControl.id)
	{
		delete currentForm.fields[name];
	}
	currentForm.fields[currentControl.id] = currentControl;
	
	jq.attr("id", currentControl.id);
	$("p", jq).text(currentControl.text);
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
		fields[id].position
	}
	
	currentForm.fields = fields;
}

function setSelected(jqEle)
{
	if(window["currentControl"])
	{
		updateSelected();
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
	$("[allow*=" + type + "]").show();
	
	if(jqEle.hasClass("ecplus-form-element"))
	{
		$("#destination .ecplus-form-element").removeClass("selected");
		jqEle.addClass("selected");
		
		$('#inputLabel').val(currentControl.text);
		$('#inputId').val(currentControl.id);
		
		$("#required").attr("checked", (currentControl.required));
		$("#title").attr("checked", (currentControl.title));
		$("#key").attr("checked", (currentControl.key));
		$("#decimal").attr("checked", (currentControl.isdouble));
		$("#integer").attr("checked", (currentControl.isinteger));
		$("#min").val(currentControl.min);
		$("#max").val(currentControl.max);
		
		$("#date").val(currentControl.date);
		if(currentControl.setDate)
		{
			$("#date").val(currentControl.setDate);
			$("#set").attr("checked", true);
		}
		else
		{
			$("#set").attr("checked", false);
		}
		
		$("#time").val(currentControl.time);
		if(currentControl.setTime)
		{
			$("#time").val(currentControl.setTime);
			$("#set").attr("checked", true);
		}
		else
		{
			$("#set").attr("checked", false);
		}
		$("#defualt").val(currentControl.defaultValue);
		$("#regex").val(currentControl.regex);
		$("#verify").attr("checked", currentControl.verify);
		$("#hidden").attr("checked", currentControl.hidden);
		$("#genkey").attr("checked", currentControl.genkey);
		$("#search").attr("checked", currentControl.search);
		//TODO: options and jumps
	}
	else
	{
		throw "div is not a form Element!";
	}
	
	if(currentControl) $(".last").show();
	else $(".last").hide();
	
}