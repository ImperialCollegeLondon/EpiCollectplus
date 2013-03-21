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
                        var dest = $('#destination');
			$("#details").css({
				"position":"fixed",
				"top" : $("#toolbox-inner").height() + 10 + "px",
				"left" : (dest.offset().left + dest.width()+20) + "px"
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
                                setSelected($(ui.item));
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
        $('#destination').append('<img src="../images/editmarker.png" class="editmarker">')
        $('.editmarker').hide();
        $('.last input, .last select').change(function(){ $('#destination .selected').addClass('editing'); });
        $('.last').hide();
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
        }
    
	$("#formList .form").remove();
	
	for(var frm in project.forms)
	{
            if(project.forms[frm].main)
		addFormToList(frm);
	}
        
        if($("#formList .form").length === 0)
        {
            newForm('<p class="msg">Please choose a name for your first form.</p>');
        }
        else
        {
            switchToForm(Object.keys(project.forms)[0]);
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
		message = "" + message;
	}
        
	EpiCollect.prompt({ 
            buttons: {
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
                        newForm("<p class=\"err\">You must give your first form a name</p>", name);
                    }

                }
            },
            content : "<p>What would you like to call the new form?</p>" + (message ? message : '')
        });
	
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
	if(type.trimChars() === "") return; 

	if(!type.match(/\.?ecplus-[a-z]+-element/))
	{
		type = '.ecplus-' + type + '-element';
	}
	
	if( type[0] !== "." ) type = "." + type;
	var jq = $(type, $(".first")).clone();
	
	
	$("p.title", jq).text(text);
	jq.attr("id", id);
	
	$(".option", jq).remove();

	if( type.match(/select1?|radio/) )
	{
		var opts = currentForm.fields[id].options;
		var l = opts.length;
		for( var i = 0; i < l; i++ )
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
			for(f in forms)
			{
				if(f !== form.name && fld.id === forms[f].key && fld.form.num > forms[f].num) cls = "ecplus-fk-element";
			}
		}
		else cls = "ecplus-" + fld.type + "-element";
		
		addControlToForm(fld.id, fld.text, cls);		
	}
	
}

function updateSelected(is_silent)
{
	var jq = $("#destination .selected");
	var cur = currentControl;
        var cfrm = currentForm;
	if(jq === undefined || jq.length === 0) return true;
	
	var name = cur.id; 
	var _type = jq.attr("type");
	
	if(_type === 'fk')
	{
	//	cur.id = project.forms[$('#parent').val()].key;
	}
	else
	{
            cur.id = $('#inputId').val();
            cur.text = $('#inputLabel').val();
            if(_type === "date" || _type === "time")
            {
                var suf = '(' + $('#' + _type).val() + ')';
                var rxsuf = '\\(' + $('#' + _type).val().replace(/\//g, '\\/') + '\\)';

                if(!cur.text.match(new RegExp(rxsuf +'$', 'g')))
                {
                        cur.text = cur.text + ' ' + suf;
                }
            }
	}
	
	if(!cfrm.validateFieldName(cur.id, name))
	{
            EpiCollect.dialog({ content : 'Field name must be unique within the form, not the same as the form name and not one of ' + EpiCollect.KEYWORDS.join(', ') });
            return false;
	}
	
	if(_type.match(/^(text|numeric|date|time|fk)$/))
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
	else{ cur.type = _type; }
	
	var notset = !$("#set").attr("checked");
	//TODO: need to set other params;
	var fk = $("#parent").val();
	
	cur.required = !!$("#required").attr("checked");
	cur.title = !!$("#title").attr("checked");
	if($("#key").attr("checked") === "checked")
	{
            if(cfrm.key !== cur.id && cfrm.key !== "")
            {
                if(confirm("You have chose to make " + cur.id + " the key for this form, but " + cfrm.key + " is already marked as the key. do you want to change the form's key field to be " + cur.id))
                {
                        cur.isKey = true;
                        if( cfrm.fields[cfrm.key]) cfrm.fields[cfrm.key].isKey = false;
                        cfrm.key = cur.id;			
                }
                else
                {
                        $("#key").attr("checked", "");
                }
            }
	}
	else
	{
            if( cfrm.key === cur.id )
            {
                    EpiCollect.dialog("This is currently the form's key field, please set another field as the key");
                    $("#key").attr("checked", "checked");
            }
            else
            {
                    cur.isKey = false;
            }
	}
	
	
	
	cur.regex = $("#regex").val();
	cur.verify = !!$("#verify").attr("checked");
	cur.date = false;
	cur.time = false;
	cur.setDate = false;
	cur.setTime = false;
	cur.isdouble = false;
	cur.isinteger = false;
	cur.min = false;
	cur.max = false;
	
	if(_type === 'date')
	{
		if($("#date").val() === "")
		{
			EpiCollect.dialog({ content : "You must select a date format." });
			//throw "You must select a date format.";
			return false;
		}
		cur[(notset ? "date": "setDate")] = $("#date").val();
	}
	else if(_type === 'time')
	{
            if($("#time").val() === "")
            {
                EpiCollect.dialog({ content : "You must select a time format." });
                return false; //throw "You must select a time format.";
            }
            cur[(notset ? "time": "setTime")] = $("#time").val();
	}
	else if(_type === 'numeric')
	{
            cur.isinteger = !!$("#integer").attr("checked");
            cur.isdouble = !!$("#decimal").attr("checked");

            cur.min = $("#min").val();
            cur.max = $("#max").val();
	}
	cur.genkey = !!$("#genkey").attr("checked");
	cur.hidden = !!$("#hidden").attr("checked");
	
	if( $("#default").val() !== '' && !cur.validate($("#default").val()) ) throw 'Default value does not match the format of the control';
	cur.defaultValue = $("#default").val();
	cur.search = !!$("#search").attr("checked");
	
	//TODO: get and add options
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
	$("p.title", jq).text(cur.text);
	
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
            $('#' + cfrm.name).addClass('unsaved');
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
		//throw "The form " + currentForm.name + " needs a key defined.";
		return false;
	}
	
        $('#' + currentForm.name).addClass('unsaved');
        
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
	//updateForm();
		
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
			if( opts[i].value === cField ) {
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
		hide = hide && fld !== cField;
	}
	fieldCtls.append("<option value=\"END\">END OF SURVEY</option>");
	fieldCtls.val(val);
}

function genID()
{
	var x = $('#destination .ecplus-form-element').length;
	var name= 'ecplus-' + currentForm.name + '-ctrl' + x;
	for(; currentForm.fields[name]; x++)
	{
		name = 'ecplus-' + currentForm.name + '-ctrl' + x;
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
    
            if(jqEle.hasClass("ecplus-form-element"))
            {
                if(window["currentControl"])
                {
                    if(!updateSelected()) return;
                    $(".last input[type=text]").val("");
                    $(".last input[type=checkbox]").attr("checked", false);
                    //$('#inputId').val(genID);
                }

                $("#parent").val("");
                $("#destination .ecplus-form-element").removeClass("selected");
                jqEle.addClass("selected");

                updateEditMarker();
          
                    
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

                   
                        
                    $('#inputLabel').val(currentControl.text);
                    if(currentControl.id && currentControl.id !== '')
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

                    if(jqEle.attr("type") === "fk")
                    {	
                            for(f in forms)
                            {
                                    if(jqEle.attr("id") === forms[f].key){
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

            if(type === 'numeric' && !($('#integer').attr('checked') || $('#decimal').attr('checked')))
            {
                    $('#integer').attr('checked', 'checked');
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
				if( fld === key )
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
	var jq = $("#destination .selected");
	
	if(currentControl.isKey){
		currentForm.key = null;
		askForKey(true);
	}
	
	delete currentForm.fields[jq.attr("id")];
	jq.remove();
	
	$("[allow]").hide();
	$(".last input[type=text]").val("");
	$(".last input[type=checkbox]").attr("checked", false);
        $('.editmarker').hide();
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
	
	if(!currentForm.key)
	{
		askForKey();
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
		content : (!keyDeleted ? "Every EpiCollect Form must have a unique key so it can identify each entry. Do you have a question that is unique to each entry?" : "You have deleted the key for this form, please choose a new key field to generate. " ),
		form:(!keyDeleted ? "<form><div id=\"key_radios\" class=\"toggle\"> <label for=\"key_yes\">Yes, I do have a unique key for this form<br/>&nbsp;</label><input type=\"radio\" id=\"key_yes\" name=\"key\" value = \"yes\"/>"+
			"<label for=\"key_no\">No, I do not have a unique key for this form, <br />please generate a key for me.</label><input type=\"radio\" id=\"key_no\" name=\"key\" value = \"no\" checked=\"checked\" /></div>"+
			"<div id=\"key_details\" style=\"display:none;\"><label for=\"key_type\">My key field is a </label><select id=\"key_type\" name=\"key_type\"><option value=\"text\">Text Field</option><option value=\"numeric\">Integer Field</option><option value=\"barcode\">Barcode Field</option></select><p id=\"key_type_err\" class=\"validation-msg\"></p>" + 
                        "<br /><label for=\"key_name\">Name for the key field</label><input id=\"key_name\" name=\"key_name\" /><p id=\"key_name_err\" class=\"validation-msg\"></p><br />"+
			"<label for=\"key_label\">Label for the key field</label><input id=\"key_label\" name=\"key_label\" /><p id=\"key_label_err\" class=\"validation-msg\"></p></div></form>" :"<form><div id=\"key_radios\" class=\"toggle\">"+ 
                        "<label for=\"key_change\">I want to make another<br /> field the key<br />&nbsp;</label><input type=\"radio\" id=\"key_change\" name=\"key\" value = \"change\"/><label for=\"key_yes\">Yes, I do have a <br />unique key for this form<br/>&nbsp;</label><input type=\"radio\" id=\"key_yes\" name=\"key\" value = \"yes\"/>"+
			"<label for=\"key_no\">No, I do not have a unique <br />key for this form, <br />please generate a key for me.</label><input type=\"radio\" id=\"key_no\" name=\"key\" value = \"no\" checked=\"checked\" /></div>"+
                  
			"<div id=\"key_details\" style=\"display:none;\"><label for=\"key_type\">My key field is a </label><select id=\"key_type\" name=\"key_type\"><option value=\"text\">Text Field</option><option value=\"numeric\">Integer Field</option><option value=\"barcode\">Barcode Field</option></select><p id=\"key_type_err\" class=\"validation-msg\"></p>" + 
                        "<br /><label for=\"key_name\">Name for the key field</label><input id=\"key_name\" name=\"key_name\" /><p id=\"key_name_err\" class=\"validation-msg\"></p><br />"+
			"<label for=\"key_label\">Label for the key field</label><input id=\"key_label\" name=\"key_label\" /><p id=\"key_label_err\" class=\"validation-msg\"></p></div>"+
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
				var fieldNameValid = project.validateFieldName(frm.name, vals.key_name);
                                
                                if(vals.key !== "yes" || (fieldNameValid === true && vals.key_label !== '' && vals.key_type !== ''))
                                {
				
                                    $( this ).dialog("close");
                                    var key_id = '';
                                    if(vals.key === 'yes')
                                    {
                                        key_id = vals.key_name ;
                                        currentForm.fields[key_id] = new EpiCollect.Field();
                                        currentForm.fields[key_id].id = vals.key_name;
                                        currentForm.fields[key_id].text =  vals.key_label;
                                        currentForm.fields[key_id].isKey = true;
                                        currentForm.fields[key_id].title = false;
                                        currentForm.fields[key_id].type = vals.key_type === 'barcode' ? 'barcode' : 'input';
                                        currentForm.fields[key_id].form = frm;
                                        currentForm.fields[key_id].isInt = (vals.key_type === 'numeric');
                                        currentForm.fields[key_id].genkey = false;
                                        addControlToForm(key_id,  vals.key_label, vals.key_type);
                                    }
                                    else if(vals.key ==='no')
                                    {
                                        key_id = default_name ;
                                        currentForm.fields[key_id] = new EpiCollect.Field();
                                        currentForm.fields[key_id].id = key_id;
                                        currentForm.fields[key_id].text = 'Unique ID';
                                        currentForm.fields[key_id].isKey = true;
                                        currentForm.fields[key_id].title = false;
                                        currentForm.fields[key_id].type = 'input';
                                        currentForm.fields[key_id].form = currentForm;
                                        currentForm.fields[key_id].isInt = false;
                                        currentForm.fields[key_id].genkey = true;
                                        addControlToForm(key_id,  'Unique ID', 'text');
                                    }
                                    else
                                    {
                                        key_id = vals.new_key;
                                        currentForm.fields[vals.new_key].isKey = true; 
                                    }
                                    
                                    currentForm.key = key_id;		
                                    setSelected($('#' + key_id));
                                    
                                }
                                else
                                {
                                    if(fieldNameValid !== true) $('#key_name_err').text(fieldNameValid).addClass('err');
                                    if(vals.key_label === '') $('#key_label_err').text("The field must have a a label").addClass('err');
                                    if(vals.key_type === '') $('#key_type_err').text("You must select a key type").addClass('err');
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
