	var project = new EcSurvey;
	var activeForm = false;
	var activeField = false;
	
	function web()
	{
		try{
			Ext.get("destination").removeClass("android");
			Ext.get("destination").removeClass("iphone");
			Ext.get("middle").set({style : "background-image:none;"});
		}catch(err){alert(err);}
	}

	function android()
	{
		try{
		Ext.get("destination").addClass("android");
		Ext.get("destination").removeClass("iphone");
		Ext.get("middle").set({"style" : "background-image:url('images/android_epi.png');background-repeat: no-repeat;background-position: center 65px;"});
		}catch(err){alert(err);}
	}

	function iphone()
	{
		Ext.get("destination").removeClass("android");
		Ext.get("destination").addClass("iphone");
		Ext.get("middle").set({style : "background-image:url('images/iphone_epi.png');background-repeat: no-repeat;background-position: center 65px;"});
	}
	
	function popup(ele, title, text)
	{
		$(".popup").remove();
		var offset = ele.offset();
		ele.after("<div class=\"popup\" style=\"" + ((offset.left + ele.width() + 200) < document.width ? "top:" + (offset.top) + "px;" + "left:" + (offset.left + ele.width()) : "top:" + (offset.top - 25) /*HACK!*/ + "px;" + "right:" + (document.width - (offset.left + ele.width()))) + "px;\"><span style=\"float:right;cursor:pointer;\">[x]</span><b>" + title + "</b><p>" + text + "</p></div>");
		$("input, textarea, select, a").focus(function(){$(".popup").remove();});
		$(".popup").click(function(){$(".popup").remove();});
	}
	
	function checkTargets()
	{
		var isTarget = false;
		
		Ext.each(Ext.query("#destination .ctrl"), function()
		{
			var nd = Ext.get(this);
			if(!nd.next() || !nd.next().hasClass("target"))
			{
				nd.insertHtml('afterEnd', '<div class="target"></div>');
			}
			if(!nd.prev() || !nd.prev().hasClass("target"))
			{
				nd.insertHtml('beforeBegin', '<div class="target"></div>');
			}
		});
			Ext.each(Ext.query("#destination .target"), function()
		{
			var nd = Ext.get(this);
			if(nd.next() && nd.next().hasClass("target"))
			{
				nd.next().remove();
			}
			if(nd.prev() && nd.prev().hasClass("target"))
			{
				nd.prev().remove();
			}
		});
	}
	
	function updateTable(fld)
	{
		var newflds = {};
		var flds = project.tables[activeForm].fields;
		var ctrls = Ext.get('destination').query('.ctrl');
		for(var c = 0; c < ctrls.length; c++)
		{
			if(flds[ctrls[c].id]){
			   newflds[ctrls[c].id] = flds[ctrls[c].id]
			}
			else if(fld)
			{
				newflds[ctrls[c].id] = fld;	
			}
		}
		project.tables[activeForm].fields = newflds;
	}
	
	function setEditField(fld)
	{
		if(activeField)
		{
			saveField();
		}
		
		while(Ext.query('.selectOption').length > 1) Ext.get(Ext.query('.selectOption')[0]).remove();
		
		var opt = Ext.query('.selectOption')[0];		
		
		activeField = project.tables[activeForm].fields[fld.id];
		Ext.get("textInputEdit").set({style : "display: block"})
		
		fld.radioClass("editing");
		
		Ext.get("textInputLabel").dom.value = activeField.text;
		Ext.get("textInputValue").dom.value = activeField.id;
		document.getElementsByName("required")[0].checked = activeField.required;
		document.getElementsByName('title')[0].checked = activeField.title
		Ext.get("selectEdit").set((project.tables[activeForm].fields[fld.id].type.match(/select|radio/gi)) ? {style : "display: block"}:{style : "display: none"});
		
		if(activeField.options.length > 0)
		{
			while(Ext.query('.selectOption').length < activeField.options.length) addOptionRow();
			setOptionFields(activeField.options);
		}
		
		
	}

	function setOptionFields(optArr)
	{
		var optRows = Ext.query('.selectOption');
		
		for(var r = 0; r < optRows.length; r++){
			optRows[r].getElementsByTagName('input')[0].value = optArr[r][0];
			optRows[r].getElementsByTagName('input')[1].value = optArr[r][1];
		}
		
	}
	
	function getOptionFields()
	{
		var optArr = [];
		var optRows = Ext.query('.selectOption');
		
		for(var r = 0; r < optRows.length; r++){
			optArr[r] = [];
			optArr[r][0] = optRows[r].getElementsByTagName('input')[0].value;
			optArr[r][1] = optRows[r].getElementsByTagName('input')[1].value ;
		}
		return optArr;
	}
	
	function removeField(fld)
	{
		if(confirm("Are you sure you want to remove " + fld + ", this cannot be undone ")){;
			Ext.get("textInputEdit").set({style : "display: none"})
			if(Ext.get(fld))Ext.get(fld).remove();
			newFlds = {};
			for(f in project.tables[activeForm].fields)
			{
				if(f != fld){
					newFlds[f] = project.tables[activeForm].fields[f];
				}
			}
			project.tables[activeForm].fields = newFlds;
			
			checkTargets();
		}
	}
	
	function addTable()
	{
		var name = prompt("Please enter a name for this table");
		var tbl = new EcTable();
		tbl.name = name;
		
		project.tables[name] = tbl;
		drawProject();
	}
	
	function addOptionRow()
	{
		try{
			
			var optpnls = Ext.query('.selectOption');
			
			var newpnl = optpnls[optpnls.length - 1].cloneNode(true);
			newpnl.id = 'selectOneOption' + optpnls.length;
			
			newpnl.getElementsByTagName('input')[0].value = "";
			newpnl.getElementsByTagName('input')[1].value = "";
			newExtpnl = Ext.get(newpnl);
			newExtpnl.insertAfter(optpnls[optpnls.length - 1])
			
			newpnl.getElementsByTagName('input')[2].onclick = function(e)
			{alert(this.id);}
			
		} catch(err){alert(err);}
	}
	
	function removeOptionRow()
	{
		
	}
	
	function saveField()
	{
		
		var main = document.getElementById(activeField.id);
		
		activeField.id = Ext.get('textInputValue').dom.value;
		activeField.text = Ext.get('textInputLabel').dom.value;
	  	if(activeField.type.match(/^(select1?|radio)$/g))activeField.options = getOptionFields();
		activeField.required = document.getElementsByName("required")[0].checked;
		activeField.title = document.getElementsByName("title")[0].checked;
		
		var divs = main.getElementsByTagName('div');
		for(var i = 0; i < divs.length; i++)
		{
			if(divs[i].className.match(/label/))
			{
				 divs[i].replaceChild(document.createTextNode(activeField.text), divs[i].firstChild );
			}
			else if(divs[i].className.match(/control/))	
			{
				divs[i].replaceChild(document.createTextNode(activeField.id), divs[i].firstChild);
			}
		}
		
		//switchForm(activeForm);
	}
	
	function makeField(ctrl, ecField)
	{
		var v = ctrl.cloneNode(true);
		v.id = ecField ? ecField.id : "";
		var c = Ext.get(v);

		c.on('click', function(){
//			alert(this.id);
			setEditField(this);
		});
	
		//add select/Edit handler
		var d = Ext.get(c.query(".i2")[0]);
		d.update("<img src=\"../images/uparrow.png\" />");
		d.insertHtml('beforeEnd', '<img src=\"../images/downarrow.png\" />');
		
		var btns = d.query('img');
		
		Ext.get(btns[0]).on('click', function(){
			this.insertBefore(this.prev().prev());
			updateTable()
			checkTargets();		
		}, d.parent());
		
		Ext.get(btns[1]).on('click', function(){
			this.insertAfter(this.next().next());
			updateTable();
			checkTargets();
		}, d.parent());
		return c;
	}
	
	function loadProject()
	{
		
		Ext.Ajax.request({
			url: location.href.replace(/\/formBuilder(\.html)?/, '.xml'),
			success: function (res, opts)
			{
				project.parse(res.responseXML);
				drawProject();
			}
		});
	}
	
	
	function resetProject()
	{
		Ext.get('formList').update('<span class="label">Select a form to Edit: </span>');
		Ext.get('destination').update('<div class="target"></div>');
	}
	
	function drawProject()
	{
		
		resetProject();
		
		var pnl = document.getElementById('formList')
		for(t in project.tables)
		{
			if(project.tables[t].branchOf != false) continue;
			var tbl = document.createElement('span');
			tbl.id = "tbl~" + project.tables[t].name;
			tbl.className = "table";
			
			pnl.appendChild(tbl);
			tbl.appendChild(document.createTextNode(project.tables[t].name));
			
			var tbl = document.createElement('span');
			tbl.className = "link";
			pnl.appendChild(tbl);
			tbl.appendChild(document.createTextNode(project.tables[t].key));
		}
		pnl.removeChild( pnl.lastChild)
		var tbl = document.createElement('span');
		tbl.className = "control";
		pnl.appendChild(tbl);
		tbl.appendChild(document.createTextNode("add a form"));
		
		pnl.appendChild(tbl);
		
		var tbls = Ext.query('.table');
		for(var i = 0; i < tbls.length; i++)
		{
			Ext.get(tbls[i]).on('click', function(){switchForm(this.id.replace("tbl~", ""))});
		}
		Ext.query('.control')[0].onmousedown =  function(e){
			addTable();
		};
	}
	
	function switchForm(frm)
	{
		
		Ext.get('destination').update('<div class="target"></div>');
		var flds = project.tables[frm].fields;
		for(f in flds)
		{
			if(flds[f].type == "") continue;
			var l = Ext.query('.target');
			var v = createControl(Ext.get(l[l.length-1]), flds[f]);
			checkTargets();
		}
		var ts = Ext.query('.table');
		for(var x = 0; x < ts.length; x++)
		{
			ts[x] = Ext.get(ts[x].id);
			
			if(ts[x].id == "tbl~" + frm) ts[x].radioClass('selected');
		}
		activeForm = frm;
	}
	
	function loadControls()
	{
		Ext.Ajax.request({
			url: "../getControls",
			success: function(res, opts)
			{
				var ctrls = Ext.util.JSON.decode(res.responseText).controlTypes;
				var ctrlPanel = document.getElementById("source");
				
				for(var i = 0; i < ctrls.length; i++)
				{
					var main = document.createElement("div");
					main.className = "ctrl";
					main.id = ctrls[i].name + "template";
					var i1 = document.createElement("span");
					i1.id = ctrls[i].name + "ctrl";
					i1.className="i1";
					main.appendChild(i1);
					var i2 = document.createElement("span");
					i2.className="i2";
					main.appendChild(i2);
					var img = document.createElement('img');
					img.src="../images/rightarrow.png";
					img.alt="&gt;&gt;"
					i2.appendChild(img);
					
					var label = document.createElement("div");
					label.className="label";
					var labelText=document.createTextNode(ctrls[i].formbuilderLabel);
					label.appendChild(labelText);
					
					i1.appendChild(label);
					ctrlPanel.appendChild(main);
					
					ct = document.createElement('div');
					ct.className = ctrls[i].name + " control";
					ct.id = ctrls[i].name + "template";
					
					
					i1.appendChild(ct);
					
					if(ctrls[i].hasOptions == "1" && ctrls[i].name != "group")
					{
						opc  = document.createElement('div');
						opc.className = "optionContainer";
						
						op1  = document.createElement('div');
						op1.className = "option";
						op1.appendChild(document.createTextNode('One'));
						
						opc.appendChild(op1);
						
						op1  = document.createElement('div');
						op1.className = "option";
						op1.appendChild(document.createTextNode('Two'));
						
						opc.appendChild(op1)
						
						ct.appendChild(opc);
					}
					else
					{
						ct.appendChild(document.createTextNode(ct.id));
					}
				}
				init();
			}
		})
	}
	
	function createControl(target, fld)
	{
		var main = document.createElement("div");
		main.className = "ctrl";
		main.id = fld.id;
		target.replaceWith(main);
		
		var i1 = document.createElement("div");
		i1.id = fld.id + "ctrl";
		i1.className="i1";
		main.appendChild(i1);
	
		var i2 = document.createElement("div");
		i2.className="i2";
		main.appendChild(i2);
		var img = document.createElement('img');
		img.src="../images/uparrow.png";
		
		i2.appendChild(img);
		
		img = document.createElement('img');
		img.src="../images/downarrow.png";
		
		i2.appendChild(img);
		
		var label = document.createElement("div");
		label.className="label";
		var labelText=document.createTextNode(fld.text);
		label.appendChild(labelText);
		
		i1.appendChild(label);
		
		ct = document.createElement('div');
		ct.className = fld.type + " control";
		ct.id = fld.id + "ele";
		
		
		i1.appendChild(ct);
		
		if(fld.options.length > 0)
		{
			opc  = document.createElement('div');
			opc.className = "optionContainer";
			for(i = 0; i <  fld.options.length; i++)
			{
				op1  = document.createElement('div');
				op1.className = "option";
				op1.appendChild(document.createTextNode(fld.options[i][1]));
				
				opc.appendChild(op1)
			}
			ct.appendChild(opc);
		}
		else
		{
			ct.appendChild(document.createTextNode(main.id));
		}
		
		
		var btns = Ext.get(i2).query('img');
		
		Ext.get(btns[0]).on('click', function(){
			this.insertBefore(this.prev().prev());
			updateTable();
			checkTargets();		
		}, Ext.get(main));
		
		Ext.get(btns[1]).on('click', function(){
			this.insertAfter(this.next().next());
			updateTable();
			checkTargets();
		}, Ext.get(main));
		
		Ext.get(main).on('click', function(){
			//alert(this.dom.className);
			setEditField(this);
		
		});
		
		//Ext.create(ct);
		return main;
	}
	
	function validateForm(frmName)
	{
		
	}
	
	function saveForm(frmName)
	{
		
	}
	
	function validateProject()
	{
		
	}
	
	function saveProject()
	{
		
	}
	
	function init()
	{
		var tbls = Ext.query('#formList .table');
		for(var i = 0; i < tbls.length; i++)
		{
			var t = Ext.get(tbls[i]);
			t.addClassOnOver('over');
		}
		
		
		tbls = Ext.query('#formList .control');
		for(var i = 0; i < tbls.length; i++)
		{
			//alert(tbls);
			var t = Ext.get(tbls[i]);
			t.addClassOnOver('over');
		}
		new Ext.dd.DragZone('destination',
		{
			dropAllowed : 'ctrl',
			getDragData : function(e){
				var sourceEl = e.getTarget('.ctrl', 10);
					if (sourceEl) {
					d = sourceEl.cloneNode(true);
					d.id = Ext.id("", "ec");
					
					return {
						ddel: d,
						sourceEl: sourceEl,
						repairXY: Ext.fly(sourceEl).getXY()
					}
				}
				else
				{return false;}
			},
			getRepairXY: function() {
				return this.dragData.repairXY;
			}
		});
	
		//var dzEles = Ext.query('.target', Ext.get('destination').dom);
		//for(var i = 0; i < dzEles.length; i++){
		new Ext.dd.DropZone('destination', {
			getTargetFromEvent : function(e)
			{
				return e.getTarget('.target');	
			},
			onNodeEnter : function(target, dd, e, data){ 
				Ext.fly(target).addClass('dd-over');
			},
			onNodeOut : function(target, dd, e, data){ 
				Ext.fly(target).removeClass('dd-over');
			},
			onNodeDrop : function(target, dd, e, data){
				if(!activeForm)
				{
					alert('A form must be selected to add fields to.');
					return;
				}
				if(dd.id === "source"){
					var ne = makeField(data.sourceEl);
					Ext.get(target).replaceWith(new Ext.Element(ne));
					var fld = new EcField();
					fld.id = ne.id;
					fld.type = data.sourceEl.id.replace('template','');
				}
				else
				{
					var ne = Ext.get(data.sourceEl);
					ne.replace(Ext.get(target));
					
				}
				updateTable(fld);
				checkTargets();				
			}
		});
		
		new Ext.dd.DragZone('source',
		{
			dropAllowed : 'ctrl',
			getDragData : function(e){
				var sourceEl = e.getTarget('.ctrl', 10);
				if (sourceEl) {
					d = sourceEl.cloneNode(true);
					d.id = Ext.id();
					d.width = 150;
					return {
						ddel: d,
						sourceEl: sourceEl,
						repairXY: Ext.fly(sourceEl).getXY()
					}
				}
				else
				{return false;}
			},
			getRepairXY: function() {
				return this.dragData.repairXY;
			}
		});
		
		Ext.each(Ext.query('.i2 img'), function()
		{
			
			try{
			Ext.get(this).on('click', function(){
				try{
					var l = Ext.get('destination').last();
					var v = makeField(this.parent().parent().dom);
					v.insertBefore(l);
					var fld = new EcField();
					fld.id = v.id;
					fld.type = this.parent().parent().id.replace('template','');
					updateTable(fld)
					checkTargets();
				}catch(err){alert(err)}
			});
			}catch(err){alert(err)}
		});
		
		Ext.get('addSelectOneOption').on('click', function(){
			addOptionRow();
		});
		Ext.get('delete').on('click', function(){removeField(activeField.id)})
		loadProject();
	}
	var editing;
	Ext.onReady(loadControls);