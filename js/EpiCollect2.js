var map;
var completed;
var succeeded;

function pluralize(str)
{
	if(str[str.length-1] != "s")
	{
		str += "s";
	}
	return str;
}

function getID()
{
	var dat = new Date;
	if(!uid) uid = 'web';
	return uid + "_" + dat.getTime();
}

function createHandler(obj, func)
{
	return (function(e, f){obj[func](e, f);})
}

var EcCheckboxGroup = Ext.extend(Ext.form.CheckboxGroup, {
	setValue:function(val){
		this.eachItem(function(item){ if(item.getXType()=="checkbox")item.setValue(false);})
		if (!val) return;
		var checkedBoxes = val.split(",");
		for(var i = 0; i < checkedBoxes.length; i++)
		{
			box = this.getBox(checkedBoxes[i]); 
			if(box) box.setValue(true);
		}
	},
	getValue:function()
	{	
		var res = "";
		this.eachItem(function(box)
		{
			if(box.getValue() && box.getXType()=="checkbox")
			{
				res += (res == "" ? "" : ",") + box.name;
			}
		});
		return res;
	}
});

var BranchPanel = Ext.extend(Ext.Panel, {
	
	initComponent : function()
	{
		
		Ext.apply(this, {
			items : survey.tables[this.form].getTable(true, this.parentKey, this.parentKeyValue),
			collapsed : true,
			collapsible : true,
			title : "Click the arrow to expand"
		});
		
		BranchPanel.superclass.initComponent.call(this);
	}
});

var mediaPanel = Ext.extend(Ext.Panel, {
	initComponent :function()
	{
		
		Ext.apply(this, {
			id : this.id,
			border: false,
			height : 150
			
		})

		mediaPanel.superclass.initComponent.call(this);
		
	},
	setValue: function(newVal)
	{
		this.value = newVal;
		
		document.getElementById(this.id + "fld").value = this.value;

		document.getElementById(this.id + "_iframe").src = location.pathname + "/uploadMedia?fn=" + newVal;
		
	},
	getValue: function() {
		if(!this.value)
		{
			if(document.getElementById(this.id + "_iframe").contentDocument.getElementsByTagName("img").length > 0)
			{
				var path = document.getElementById(this.id + "_iframe").contentDocument.getElementsByTagName("img")[0].src.replace('~tn~','~');
				var fnstart = path.indexOf("~");
				
				this.value = path.substring(fnstart+1);
				document.getElementById(this.id).value = this.value;
			}
		}
		return this.value;
	},
	render : function(cmp)
	{
		if(location.pathname.indexOf(this.parent) >= 0)
		{
			this.html = "<iframe id=\"" + this.id + "_iframe\" style=\"border:0\" onload=\"if(document.getElementById('" + this.id + "_iframe').contentDocument.getElementsByTagName('img').length > 0) document.getElementById('" + this.id+ "fld').value = document.getElementById('" + this.id + "_iframe').contentDocument.getElementsByTagName('img')[0].src.match(/~.*/).toString().replace('~tn~', '')\" src=\"" + location.pathname + "/uploadMedia" + (this.value ? "?fn=" + this.value : "") +"\" width=\"100%\" height=\"100%\"> </iframe><input type=\"hidden\" id=\"" + this.id + "fld\" name=\"" + this.id+ "\">";
		}
		else
		{
			this.html = "<iframe id=\"" + this.id + "_iframe\" style=\"border:0\" onload=\"if(document.getElementById('" + this.id + "_iframe').contentDocument.getElementsByTagName('img').length > 0) document.getElementById('" + this.id+ "fld').value = document.getElementById('" + this.id + "_iframe').contentDocument.getElementsByTagName('img')[0].src.match(/~.*/).toString().replace('~tn~', '')\" src=\"" + location.pathname + "/" + this.parent + "/uploadMedia" + (this.value ? "?fn=" + this.value : "") +"\" width=\"100%\" height=\"100%\"> </iframe><input type=\"hidden\" id=\"" + this.id + "fld\" name=\"" + this.id+ "\">";
		}
		mediaPanel.superclass.render.call(this,cmp);
	}
});
	
var GPSPanel = Ext.extend(Ext.Panel,{
	initComponent :function()
	{
		Ext.apply(this, {
			border: false,
			html: "<div id=\"" + this.id + "_searchcontatiner\">Search for location<input id=\"" + this.id + "_search\" /><span class=\"button\" onclick=\"Ext.getCmp('" + this.id + "container').locationSearch();\">Search</span></div><div id=\"" + this.id + "_map\" width=\"100%\" style=\"height: 200px;width: 50%;display:inline-block;vertical-align:top;\"></div><div class=\"GPSInputs\" style=\"display: inline-block;vertical-align:top;padding:5px 5px 5px 5px;\">"
				+ "<table>" + " <tr> <td>Accuracy</td><td><input type=\"text\" id=\"" + this.id + "_acc\" name=\"" + this.id+ "_acc\" onchange=\"Ext.getCmp('" + this.id + "').accCircle.setRadius(Number(this.value));\"/></td></tr>"
				+ " <tr> <td>Altitude</td><td><input type=\"text\" id=\"" + this.id + "_alt\" name=\"" + this.id+ "_alt\"></td></tr>"
				+ " <tr> <td>Source</td><td><input type=\"text\" id=\"" + this.id + "_src\" name=\"" + this.id+ "_src\"></td></tr></table><input type=\"hidden\" name=\""+this.id + "\" id=\"" + this.id + "fld\" />",
			height: 150,
			listeners: {
				'afterrender' : function()
				{
					mkr = this.marker;
					
					this.gMap = new google.maps.Map(document.getElementById(this.id + "_map"),{
						backgroundColor: '#FFFFFF',
						center : new google.maps.LatLng(0,0),
						zoom : 1,
						mapTypeId : google.maps.MapTypeId.ROADMAP,
						streetViewControl : false,
						mapTypeControl : false
					});
										
					this.marker = new google.maps.Marker({icon:"../images/mapMarkers/redCircle.png", draggable: true});
					
					d = this;
					
					google.maps.event.addListener(this.marker, 'dragend', function()
						{
							d.accCircle.setCenter(d.marker.getPosition());
							d.updateElevation();
							document.getElementById(d.id + "_src").value = "marker drop";
							d.updateControl();
						}
					)
					
					this.accCircle = new google.maps.Circle({fillColor: "#8888FF", strokeColor: "#8888FF"});
						d = this;
					if(!this.value)
					{
						navigator.geolocation.getCurrentPosition(d.updatePos,null,{enableHighAccuracy: true});
					}
					
					
					document.getElementById(this.id + "_search").onchange = function(e){d.locationSearch()};
					if(this.value) this.setValue(this.value);
				}
			}
		})
		GPSPanel.superclass.initComponent.call(this);
	},
	setValue: function(newVal)
	{
		if(!d) d = this;
		document.getElementById(this.id + "fld").value = newVal;
		var obj = Ext.decode(newVal);
		
		p = new google.maps.LatLng(obj.latitude, obj.longitude);
		
		d.marker.setPosition(p);
		d.marker.setMap(d.gMap);
		
		d.accCircle.setCenter(p);
		d.accCircle.setRadius(Number(obj.accuracy));
		d.accCircle.setMap(d.gMap);
		
		document.getElementById(d.id + "_alt").value = obj.altitude;
		document.getElementById(d.id + "_acc").value = obj.accuracy;
		document.getElementById(d.id + "_src").value = obj.source;
	},
	getValue : function()
	{
		d.updateControl();
		return document.getElementById(this.id + "fld").value;
	},
	updateControl : function()
	{
		if(!d) d = this;
		var val = {};
		
		var pos = d.marker.getPosition();
		val.latitude = pos.lat();
		val.longitude = pos.lng();
		
		val.altitude = Number(document.getElementById(d.id + "_alt").value);
		val.accuracy = Number(document.getElementById(d.id + "_acc").value);
		val.source = document.getElementById(d.id + "_src").value;
		
		Ext.get(d.id  + "fld").dom.value = Ext.encode(val);
		
	},
	updatePos: function(position)
	{
		if(!d) d = this;
		accCtrl = document.getElementById(d.id + "_acc");
		altCtrl = document.getElementById(d.id + "_alt");
		srcCtrl = document.getElementById(d.id + "_src");
		
		accCtrl.value = position.coords.accuracy;
		altCtrl.value = position.coords.altitude;
		srcCtrl.value = "HTML5 geolocation";
	
		d.marker.setPosition(new google.maps.LatLng(position.coords.latitude, position.coords.longitude));
		d.marker.setMap(d.gMap);
		
		d.gMap.setCenter(d.marker.getPosition());
		d.gMap.setZoom(5);
		
		d.accCircle.setCenter(d.marker.getPosition());
		d.accCircle.setRadius(position.coords.accuracy);
		d.accCircle.setMap(d.gMap);
		//d.updateControl();
		if(!position.coords.altitude)
		{
			d.updateElevation()
		}
	},
	updateElevation : function()
	{
		d = this;
		function elevationCallback(result, status)
		{
			if(status == google.maps.ElevationStatus.OK)
			{
			
				document.getElementById(d.id + "_alt").value = result[0].elevation;
				d.updateControl();
			}
		}
		
		var elv = new google.maps.ElevationService();
		elv.getElevationForLocations({locations:[this.marker.getPosition()]}, elevationCallback);
		
		
	},
	locationSearch : function()
	{
		d = this;
		function geocodeCallback(results, status)
		{
			if(status == google.maps.GeocoderStatus.OK)
			{	
				d.marker.setPosition(results[0].geometry.location);
				d.accCircle.setCenter(results[0].geometry.location);
				
				//alert(Ext.encode(results[0]));
				//bnds = results[0].geometry.bounds;
				//d.accCircle.setRadius(Math.min(bnds.getNorthEast().lat() - bnds.getSouthWest().lat(),bnds.getNorthEast().lng() - bnds.getSouthWest().lng()) / 2)
		
				d.gMap.setCenter(d.marker.getPosition());
				d.gMap.setZoom(5);
				document.getElementById(d.id + "_src").value = "geocoder search";
				
				d.updateElevation();
			}
		}
		
		gcoder = new google.maps.Geocoder();
		gcoder.geocode({address : document.getElementById(this.id + "_search").value}, geocodeCallback);
		d.updateControl();
	}
})

var EcSurvey = function()
{
    this.tables = {};
    this.localUrl = '';
    this.remoteUrl = '';
    this.name = '';
    this.id = '';
    this.allowEdits = false;
    this.version = 0.0;
    this.map;
	this.description = "";
	
	this.getNextTable = function(tblName)
	{
		var n = Number(this.tables[tblName].num) + 1;
		var tbl = false;
		
		for(var t in this.tables)
		{
			if(this.tables[t].num == n)
			{
				tbl = this.tables[t];
				break;
			}
		}
		return tbl;
	}
	
	this.getPrevTable = function(tblName)
	{
		var n = Number(this.tables[tblName].num) - 1;
		var tbl = false;
		
		for(var t in this.tables)
		{
			if(this.tables[t].num == n)
			{
				tbl = this.tables[t];
				break;
			}
		}
		return tbl;
	}
	
    this.parse = function(xml)
    {
		keys = {};
		
        mdl = xml.getElementsByTagName('model')[0];
        sub = mdl.getElementsByTagName('submission')[0];
        this.id = sub.getAttribute('id');
        this.name = sub.getAttribute('projectName');
		
        this.allowEdits = sub.getAttribute('allowDownloadEdits') == 'true';
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
		
        tbls = xml.getElementsByTagName('table');
		if (tbls.length == 0)
		{
			tbls = xml.getElementsByTagName('form');
		}
        for(var i = 0 ; i < tbls.length; i++){
            tbl = new EcTable();
            tbl.parse(tbls[i]);
            this.tables[tbl.name] = tbl;
			keys[tbl.key] = tbl.name;
        }
		for(tbl in this.tables)
		{
			var branches = this.tables[tbl].branchForms;
			for(var i = 0; i < branches.length; i ++)
			{
				this.tables[branches[i]].branchOf = tbl;
			}
		}
	}
		
	this.draw = function(div)
	{
		var d = Ext.get(div);
		d.update('<h1>' + this.name + '</h1><p>' + this.description + '</p><div id="ecTables"></div><div id="ecMap" style="width:300px;height:300px;display:none;"></div>');
		
		pnl = new Ext.Panel({
			id:'SearchPanel',
			width: Ext.getBody().getWidth() * 0.9,
			height: Ext.getBody().getHeight() * 0.75,
			border: false,
			layout:{
				type : 'vbox',
				align : 'center',
				defaultMargins: {
					top: 5,
					left: 5,
					right : 5,
					bottom : 5
				}
			}
		});
			
		pnl.add(new Ext.form.Label ({text: 'Select a table to view'}))
		
		for(tbl in survey.tables)
		{
			
			pnl.add(new Ext.Button({
				text:tbl,
				width:250,
				listeners:{
					'click':show
				}
			}));
		}
		pnl.render('ecTables');
	}
}


var EcTable = function(conf)
{
    this.fields = {};
    this.num = 0;
    this.name = '';
    this.key = '';
    this.titleField = '';
    this.cols = [];
	this.main = true;
	
	this.filterField = false;
	this.filterValue = false;
	this.searchValue = false;
	this.branchOf = false;
	this.branchForms = [];
	this.groupForms = [];
    this.store = {};
	this.hasGps = false;
	this.hasMedia = false;
	this.gpsFlds = [];
	
	this.deletedBranches = [];

	Ext.apply(this, conf);
	
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
        
		this.fld = new EcField();
		this.fld.id = "created";
		this.fld.parent = this.name;
		this.fld.text = "Time Created";
		this.fields[this.fld.id] = this.fld;
		this.cols.push("created");
		
		this.fld = new EcField();
		this.fld.id = "uploaded";
		this.fld.parent = this.name;
		this.fld.text = "Time Uploaded";
		this.fields[this.fld.id] = this.fld;
		this.cols.push("uploaded");
		
		this.fld = new EcField();
		this.fld.id = "lastEdited";
		this.fld.parent = this.name;
		this.fld.text = "Last Updated";
		this.fields[this.fld.id] = this.fld;
		this.cols.push("lastEdited");
		
		this.fld = new EcField();
		this.fld.id = "DeviceID";
		this.fld.text = "Device ID";
		this.fld.parent = this.name;
		this.fields[this.fld.id] = this.fld;
		this.cols.push("DeviceID");
		
		
		for(var nd = 0; nd < xml.childNodes.length; nd++)
		{
			if(xml.childNodes[nd].nodeType == 1 && xml.childNodes[nd].nodeName != "table_data")
			{
				field = new EcField();
				field.parse(xml.childNodes[nd]);
				field.parent = this.name;
		
				this.fields[field.id] = field;
				this.cols.push(field.id);
				
				if(keys[field.id])
				{
					field.fkField = field.id;
					field.fkTable = keys[field.id];
				}
				if(field.title)this.titleField = field.id;
				if(field.type == "video" || field.type == "audio" || field.type == "photo")
				{
					this.hasMedia = true;
				}
				else if (field.type == "gps" || field.type == "location")
				{
					this.hasGps = true;
					this.gpsFlds.push(field.id);
				}
				else if (field.type == "branch")
				{
					this.branchForms.push(field.form);
				}
				else if(field.type == "group")
				{
					this.groupForms.push(field.form);
				}
				
				
			}
		}
		this.cols.push("childEntries");
    }

    this.getTable = function(loadData, filterField, filterValue)
    {
		var cols = [];
		var tBtns = [];
		path = location.href;
		var path = location.pathname ;//+ ".json"
		if(path.indexOf(survey.name) < 0) path = path + "/" + survey.name;
		if(path.indexOf(this.name) < 0) path = path + "/" + this.name;
		
		this.store = new Ext.data.JsonStore({
			autoLoad : false,
			baseParams : {
				limit : 25,
				start : 0,
				mode : 'list'
			},
			remoteSort : true,
			storeId : this.name + "_store",
			idProperty : this.key,
			id :this.key,
			fields : this.cols,
			proxy: new Ext.data.HttpProxy({
				method: 'GET',
				url : path + '.json' + location.search
			}),
			root : this.name,
			totalProperty : 'count'
		});
		this.store.setDefaultSort(this.key, 'asc');
	
		for(this.fld in this.fields)
		{
			if(this.fld == "created")
			{
				cols.push({id : this.fields[this.fld].id, header : this.fields[this.fld].text, renderer: function(value, metaData, record, rowIndex, colIndex, store) {
						var d = new Date(Number(value));
						return d.toLocaleString();
				}, dataIndex: this.fields[this.fld].id, sortable:true});
			}
			else if(this.fld == "lastEdited" || this.fld == "uploaded")
			{
				cols.push({id : this.fields[this.fld].id, header : this.fields[this.fld].text, renderer: function(value, metaData, record, rowIndex, colIndex, store) {
					if(value != "" && value != "0" && value != null)
					{
						var d = new Date(value);
						return d.toLocaleString();
					}
					else
					{
						return "";
					}
				}, dataIndex: this.fields[this.fld].id, sortable:true});
			}
			else if(this.fields[this.fld].type == "photo")
			{
				cols.push({id : this.fields[this.fld].id, header : this.fields[this.fld].text, renderer: function(value, metaData, record, rowIndex, colIndex, store) {
					if(value)
						return "<a href=\"../ec/uploads/" + survey.name + "~" + value + "\" target=\"__blank\"><img src=\"../ec/uploads/" + survey.name + "~tn~" + value + "\" height=\"64\" width=\"64\" /></a>";
					else
						return "<i>no picture </i>";
				}, dataIndex: this.fields[this.fld].id, sortable:true});
			}
			else if(this.fields[this.fld].type == "video")
			{
			
				cols.push({id : this.fields[this.fld].id, header : this.fields[this.fld].text, renderer: function(value, metaData, record, rowIndex, colIndex, store) {
					if(value != "" && value != "0" && value != null)
					{
						return "<a href=\"../ec/uploads/"+ survey.name + "~" + value + "\" target=\"__blank\">" + value + "</a>";
					}
					else
					{
						return "<i>no video</i>";
					}
				}, dataIndex: this.fields[this.fld].id, sortable:true});
			}
			else if(this.fields[this.fld].type == "audio" && value != null)
			{
				cols.push({id : this.fields[this.fld].id, header : this.fields[this.fld].text, renderer: function(value, metaData, record, rowIndex, colIndex, store) {
					if(value != "" && value != "0")
					{
						return "<a href=\"../uploads/" + survey.name + "~" + value + "\">" + value + "</a>";
					}
					else
					{
						return "<i>no audio</i>";
					}
				}, dataIndex: this.fields[this.fld].id, sortable:true});
			}
			else if(this.fields[this.fld].type == "gps" ||this.fields[this.fld].type == "location")
			{
				cols.push({id : this.fields[this.fld].id, header : this.fields[this.fld].text, renderer: function(value, metaData, record, rowIndex, colIndex, store) {
					
					var gps = Ext.decode(value);
					if(gps.accuracy == "-1")
					{
						return "<i>no position</i>";
					}
					else
					{
						return gps.latitude + "," + gps.longitude;
					}
					
				}, dataIndex: this.fields[this.fld].id, sortable:true});
			}
			else if(this.fields[this.fld].type == "branch")
			{
				var ff = this.fields[this.fld].form;
				var fk = this.key;
				var fn = this.name;
				cols.push({id : this.fields[this.fld].id, header : this.fields[this.fld].text, sortable:false, menuDisabled: true, renderer: function(value, metaData, record, rowIndex, colIndex, store) {
					
					if(!value ||value == "")
					{
						return "<i>no entries</i>";
					}
					else
					{
						return value + " <a href=\"" + ff + "?" + fk + "=" + record.data[fk] +"&prevForm=" + fn + "\">View entries</a>";
					}
				}, dataIndex: this.fields[this.fld].id, sortable:true});
			}
			else 
			{
				cols.push({id : this.fields[this.fld].id, header : this.fields[this.fld].text, dataIndex: this.fields[this.fld].id, hidden : (this.branchOf && this.fld == survey.tables[this.branchOf].key),sortable:true});
			}
		}
		if(!this.branchOf && survey.getNextTable(this.name).main)
		{
			var ff = survey.getNextTable(this.name).name;
			var fk = this.key;
			var fn = this.name;
			cols.push({
				id: 'childEntries', header : survey.getNextTable(this.name).name + ' Entries', dataIndex : 'childEntries', sortable:true,  menuDisabled: true, renderer: function(value, metaData, record, rowIndex, colIndex, store)
				{		
					if(value == "" || value == null)
					{
						return "<i>no entries</i>";
					}
					else
					{
						return value + " <a href=\"" + ff + "?" + fk + "=" + record.data[fk] +"&prevForm=" + fn + "\">View entries</a>";
					}
				}
			});
		}
	
		tBtns = [{
			//xtype: 'button',
			id:'addEntry_' + this.name,
			text: 'Add Entry',
			listeners :{
				'click' : function(){
					this.getForm();
					this.frm.anchor = "100% 100%";
					this.win = new Ext.Window({
						title: "Add Entry",
						items:[this.frm],
						layout : 'anchor',
						modal:true,
						width: Ext.getBody().getWidth() * 0.95,
						height: Ext.getBody().getHeight() * 0.95
					});
					this.frm.getFooterToolbar().get(this.name + 'cnl').on('click', function(){this.win.close()}, this);
					this.frm.getFooterToolbar().get(this.name + 'sub').on('click', function(){this.addEntry(this.frm.getForm().getValues(),function(scope){scope.win.close()}, this);}, this);
					this.win.show();
				},
				scope : this
			}
		},
		{
			id:'editEntry_' + this.name,
			text: 'Edit Entry',
			disabled: true,
			listeners :{
				'click' : function(){
					rec = grid.getSelectionModel().getSelected();
					this.frm = this.getForm(rec);
					this.frm.anchor = "100% 100%";
					this.win = new Ext.Window({
						title: "Edit Entry",
						items:[this.frm],
						layout : 'anchor',
						modal:true,
						width: Ext.getBody().getWidth() * 0.95,
						height: Ext.getBody().getHeight() * 0.95
					});
					this.frm.getFooterToolbar().get(this.name + 'cnl').on('click', function(){this.win.close()}, this);
					this.frm.getFooterToolbar().get(this.name + 'sub').on('click', function(){this.editEntry(rec.data[this.key],this.frm.getForm().getValues(), function(scope){scope.win.close()}, this);}, this);
					this.win.show();
				},
				scope : this
			}
		},
		{
			id:'deleteEntry_' + this.name,
			text:'Delete Entry',
			disabled : true,
			listeners :{
				'click' : function(){
					if(confirm ("Are you sure you want to delete this entry."))
					{
						this.deleteEntry(grid.getSelectionModel().getSelected().data[this.key]);
					}
				},
				scope: this
			}
		},
		{
			xtype : 'tbseparator'
		},
		{
			xtype: 'tbtext',
			text : 'Search for Entry by ID'
		},
		{
			id: 'idSearchField',
			xtype: 'textfield',
			text : 'Search for Entry by ID',
			value: this.searchValue ? this.searchValue : "",
			listeners: {
				'change' : function(f){
					try{
					if(f.getValue() != "")
					{
						var r = Ext.getCmp(this.name + '_grid').getStore().find(Ext.getCmp(this.name + '_grid').getStore().idProperty, f.getValue(), 0, true,false);
						if(r >= 0)
						{
							Ext.getCmp(this.name + '_grid').getView().focusRow(r);
							Ext.getCmp(this.name + '_grid').getSelectionModel().selectRow(r, false, false);
						}
					}}catch(err){alert(err);}
				},
				scope:this
			}
		},
		{
			xtype: 'button',
			text : 'Find',
			listeners: {
				'click' : function(){
					try{
					if(Ext.getCmp('idSearchField').getValue() != "")
					{
						pars = {start : 0, limit : 25};
						pars[this.key] = Ext.getCmp('idSearchField').getValue();
						grid.getStore().load({params : pars})
					}}catch(err){alert(err);}
				},
				scope:this
			}
		},
		{
			xtype : 'tbseparator'
		},
		{
			xtype: 'button',
			text : 'Clear Filter',
			listeners: {
				'click' : function(){
					
					this.filterField = false;
					this.filterValue = false;
					Ext.getCmp('idSearchField').setValue("");

					grid.getStore().load({params:{start : 0, limit : 25}});
				}
			},
			scope:this
		},
		{
			xtype : 'tbseparator'
		},
		{
			type: 'splitbutton',
			text: 'Download this data as ',
			menu: new Ext.menu.Menu ({
				items : [
					{text: 'Comma separated', handler: function () {
						window.open(Ext.getCmp(this.name + '_grid').getStore().proxy.url.replace(".json", ".csv") + (this.filterValue ? "?" + this.filterField + "=" + this.filterValue : ""));
					}, scope: this},
					{text: 'Tab separated', handler: function () {
						window.open(Ext.getCmp(this.name + '_grid').getStore().proxy.url.replace(".json", ".tsv") + (this.filterValue ? "?" + this.filterField + "=" + this.filterValue : ""));
					}, scope: this}
				]
			})
		
		}];
		
		var grid = new Ext.grid.GridPanel({
			id: this.name + '_grid',
			border: true,
			columns: cols,
			store: this.store,
			tbar: tBtns,
			width: Ext.getBody().getWidth() - 25,
			height: Ext.getBody().getHeight() * 0.65,
			listeners : {
				'contextmenu': function(e)
				{
					e.preventDefault();								
				}
			},
			bbar :(this.hasGps ? null : new Ext.PagingToolbar({
				pageSize: 25,
				store: this.store,
				displayInfo: true,
				displayMsg: 'Displaying ' + this.name + ' {0} - {1} of {2}',
				emptyMsg: "No " + this.name + " to display",
				items:[
					
				],
				listeners : {
					'change' : function(tb, data)
					{
						if(this.hasGps) this.drawPoints();
					},
					scope: this
				}
			}))	
		});
		
		grid.getSelectionModel().on('selectionchange', function(selMdl){
			var dis = grid.getSelectionModel().getCount() != 1;
			if(grid.getTopToolbar().get('deleteEntry_' + this.name)) grid.getTopToolbar().get('deleteEntry_' + this.name).setDisabled(dis);
			if(grid.getTopToolbar().get('editEntry_' + this.name)) grid.getTopToolbar().get('editEntry_' + this.name).setDisabled(dis);
		}, this)
		
		var pars = {start : 0, limit: 25, mode: 'list'};
		pars[filterField] = filterValue;
		this.store.load({params: pars}); // change this one!!<<<<
		
		if(this.hasGps)
		{
			var fldGrpArr = [['DeviceID', 'Device ID', 'input']];
			for(this.fld in this.fields )
			{
				
				if(this.fields[this.fld].name == "created" || this.fields[this.fld].type == "group" || this.fields[this.fld].type == "radio" || this.fields[this.fld].type == "select1" || this.fields[this.fld].fkField != false)
				{
					fldGrpArr.push([this.fld, this.fields[this.fld].text, this.fields[this.fld].type]);
					
				}
				else
				{
					for(tbl in survey.tables)
					{
						if(tbl == this.name) continue;
						if(this.fld == survey.tables[tbl].key)
						{
							fldGrpArr.push([this.fld, this.fields[this.fld].text, this.fields[this.fld].type]);
						}
					}
				}
			}
			
			this.tPanel = new Ext.TabPanel({
				activeTab:0,
				items : [{
					anchor: "100% 100%",
					title: "Table",
					items: [grid]
				},
				{
					title: 'Map',
					width: Ext.getBody().getWidth() * 0.6,
					height: 700,
					items : [{
							id: 'mapPnl',
							region:'center',
							title: "Map",
							contentEl : "ecMap"
						},
						{
							region : 'west',
							title: 'Entries',
							split: true,
							width: 150,
							collapsible : true,
							items : [
								{
									id: 'entryList',
								}
							]
						},{
							region: 'south',
							title : 'Footer',
							split: true,
							collapsible : true,
							height: 100,
							defaults:
							{
								cls: 'cp-item'
							},
							items: [
								{
									border: false,
									items : [
										{
											xtype:'label',
											text : 'Filter By Time'
										},
										{
											style : '',
											xtype: 'multislider',
											id: 'timeSlider',
											minValue : 0,
											maxValue : 100,
											values : [0, 100],
											increment: 1,
											width : 200,
											listeners : {
												'change' : function(slider, newVal, thumb)
												{
													
													if(map){
														map.removeAllFilters();
														map.addFilter('date', 'le', slider.getValue(1));
														map.addFilter('date', 'ge', slider.getValue(0));
														map.doFilter();
													}
													Ext.get('timeText').update("From " + new Date(slider.getValue(0)).toLocaleString() + " to " + new Date(slider.getValue(1)).toLocaleString())
												}
											}
										},
										{
											border: false,
											html: '<p id="timeText">From x to y</p>'
										}
									]
								},
								{
									border: false,
									items: [
									{
										xtype: 'label',
										text : 'Colour points by field'
									},{
										xtype: 'combo',
										id:'fieldCombo',
										lazyRender:true,
										mode: 'local',
										//store:this.store,
										typeAhead: false,
										typeAheadDelay:false,
										triggerAction: 'all',
										
										store: new Ext.data.ArrayStore({
											autoLoad: true,
											//url: location.href + '.json',
											//root: 'fields',
											idField : 'name',
											fields:[
												'name',
												'label',
												'type'
											],
											data: fldGrpArr
										}),
										displayField : 'label',
										valueField : 'name',
										listeners : {
											'select': function(cbo, rec, idx) {
												this.setGroupField(rec.data['name']);
											},
											scope: this	
										},
										value : 'DeviceID'
									}
									]
								}
								]
							}
							],
							
					
					layout: {
						type:'border'
					}
					
				}],
				listeners:{
					render : function(t)
					{
						t.setActiveTab(1);
						t.setActiveTab(0);
					}
				},
				scope : this,
				bbar : new Ext.PagingToolbar({
					pageSize: 25,
					store: this.store,
					displayInfo: true,
					displayMsg: 'Displaying ' + this.name + ' {0} - {1} of {2}',
					emptyMsg: "No " + this.name + " to display",
					items:[
						
					],
					listeners : {
						'change' : function(tb, data)
						{
							if(this.hasGps) this.drawPoints();
						},
						scope: this
					}
				})
			});
			
			this.timeSlider = Ext.getCmp('timeSlider');
			this.tPanel.on('tabchange', createHandler(this, "createMap"), this);
			
			Ext.EventManager.onWindowResize(function (){
				grid.setSize(Ext.getBody().getWidth() - 25, Ext.getBody().getHeight() * 0.65);
			}, this);
			
			return this.tPanel;
				
		}
		else
		{
			
			Ext.EventManager.onWindowResize(function (){
				grid.setSize(Ext.getBody().getWidth() - 25, Ext.getBody().getHeight() * 0.65);
			}, this);
			return grid;
		}
    }
		
		
	this.nextMkr = function()
	{
		this.currentMkr++;
		if(this.currentMkr >= this.mkrs.length) this.currentMkr = 0;
		return this.mkrs[this.currentMkr];
	}
		
	this.setGroupField = function(grpField)
	{
		try{
		this.currentMkr = -1;
		this.grps = {};
		this.groupField = grpField;
	
		for(var i = 0; i < map.markers.length; i++)
		{
			var mkr = "redCircle";
			var gVal = map.markers[i].getAttribute(this.groupField);
			if(this.grps[gVal])
			{
					mkr = this.grps[gVal];
			}
			else
			{
					mkr = this.nextMkr();
					this.grps[gVal] = mkr;
			}
			map.markers[i].proprietary_marker.setIcon("../images/mapMarkers/" + (mkr ? mkr : "redCircle") + ".png");
		}
		this.drawMapLegend();
		}catch(err){alert(err);}
	}
	
	this.drawMapLegend = function()
	{
		
		if(!map.maps['googlev3'].controls[google.maps.ControlPosition.RIGHT_BOTTOM].length)
		{
			this.legend = document.createElement('div');
			this.legend.style.backgroundColor = '#EEEEEE';
			this.legend.style.padding = '5px 5px 5px 5px';
			this.legend.style.border = '1px solid #000000'
			var inner = document.createTextNode('Hello World');
			this.legend.appendChild(inner);
			map.maps['googlev3'].controls[google.maps.ControlPosition.RIGHT_BOTTOM].push(this.legend);
		}
		
		
		var i = 0;
		for(grp in this.grps)
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
	}
	
	this.showAndFocus = function(key)
	{
		if(this.w) this.w.close();
		for(mkr in map.markers)
		{
			if(map.markers[mkr].proprietary_infowindow)map.markers[mkr].proprietary_infowindow.close();
		}
		if(this.markers[key])
		{
			this.markers[key].openBubble();
			map.setCenterAndZoom(this.markers[key].location, 16);
		}
		else
		{
			
			this.w = new Ext.Window({
				width: 215,
				title: 'Entry without position',
				html:this.descs[key],
				layout:{
					type: 'hbox',
					defaultMargins : {
						top : 5,
						left : 5,
						bottom : 5,
						right : 5
					},
					align:'center'								
				}
			});
			this.w.show();
		}
	}
	
	this.createMap = function(pnl, tab)
	{
		
		this.groupField = "DeviceID";
		this.grps = {};
		this.minT = Number.MAX_VALUE;
		this.maxT = -Number.MAX_VALUE;
		this.tPanel;
		this.mkrs = ["redCircle", "greenCircle", "greyCircle", "yellowCircle", "whiteCircle","plumCircle", "darkGreyCircle","maroonCircle", "navyCircle", "mintCircle",  "pinkCircle", "purpleCircle",
							   "redSquare", "greenSquare", "greySquare", "plumSquare", "darkGreySquare","maroonSquare", "navySquare", "mintSquare",  "pinkSquare", "purpleSquare"];
		this.currentMkr = -1;
		
		if(!tab.get('mapPnl')) return;
		
		Ext.get("ecMap").dom.style.display = "";
		Ext.get("ecMap").dom.style.width = tab.get('mapPnl').getWidth() + "px";
		Ext.get("ecMap").dom.style.height = (tab.get('mapPnl').getHeight() - 25) + "px";
		map = new mxn.Mapstraction('ecMap', 'googlev3');
		map.setCenterAndZoom(new mxn.LatLonPoint(0,0), 2);
		//map.setMapType(mxn.Mapstraction.HYBRID);
		
		
		map.maps['googlev3'].setOptions({
			panControl: true,
			rotateControl: true,
			zoomControl: true
		});
		
		this.drawPoints();
	}
	
	this.drawPoints = function()
	{
		var minLat = 180, minLon = 180, maxLat = -180, maxLon = -180;
		var recs = this.store.getRange();
		this.grps = {};
		var sidebarHtml = "";
		this.markers = {};
		this.descs = {};
		
		map.removeAllMarkers();
		
		for(var i = 0; i < recs.length; i++)
		{
			var mkr = "redCircle";
			if(this.grps[recs[i].data[this.groupField]])
			{
				mkr = this.grps[recs[i].data[this.groupField]];
			}
			else
			{
				mkr = this.nextMkr();
				this.grps[recs[i].data[this.groupField]] = mkr;
			}
			
			descHtml = "<table><tr><th class=\"bubbleth\"> " +(this.titleField != "" ? this.titleField : this.key) + " </th><th class=\"bubbleth\"> " + recs[i].data[(this.titleField != "" ? this.titleField : this.key)] + "</th></tr>";
			
			for(this.fld in this.fields)
			{
				if(this.fld == this.key || this.fld == "DeviceID") continue;
				if(this.fld == "created")
				{
					var dat = new Date(Number(recs[i].data[this.fld]));
					 descHtml += "<tr><th class=\"bubbleth\">" + this.fields[this.fld].text  + "</th><td>" + dat.toLocaleString() + "</td></tr>";
				}
				else if(this.fields[this.fld].type == "photo")
				{
					 descHtml += "<tr><th class=\"bubbleth\">" + this.fields[this.fld].text  + "</th>"
					if(recs[i].data[this.fld] != 0 && recs[i].data[this.fld] != "")
					{
						descHtml += "<td><a href=\"../ec/uploads/" + survey.name + "~" + recs[i].data[this.fld] + "\" target=\"__blank\"><img src=\"../ec/uploads/" + survey.name + "~tn~" + recs[i].data[this.fld] + "\" height=\"64\" width=\"64\" /></a></td></tr>";
					}
					else
					{
						descHtml += "<td><i>no picture </i></td></tr>";
					}
				}
				
				else if(!this.fields[this.fld].type.match(/gps|location/i)) descHtml += "<tr><th class=\"bubbleth\">" + this.fields[this.fld].text  + "</th><td>" + recs[i].data[this.fld] + "</td></tr>"
			}
			
			descHtml += "</table>"
			
			var bnds = new google.maps.LatLngBounds();
			
			for(this.fld = 0; this.fld < this.gpsFlds.length; this.fld ++)
			{
				var gps = Ext.decode(recs[i].data[this.gpsFlds[this.fld]])
				
				sidebarHtml += "<p class=\"entry ###\"><a href=\"javascript:void(0)\" onclick=\"table.showAndFocus('"  + recs[i].data[this.key]+ "')\">" +recs[i].data[(this.titleField != "" ? this.titleField : this.key)];
				
				if( gps.accuracy != -1)
				{
					data = {
						label: recs[i].data[(this.titleField != "" ? this.titleField : this.key)],
						infoBubble : descHtml,
						date: recs[i].data["created"],
						icon : "../images/mapMarkers/" + (mkr ? mkr : "redCircle") + ".png",
						iconSize: [12,20],
						iconAnchor: [6, 20],
						marker: 4
					}
					
					for(fld2 in this.fields)
					{
						data[fld2] = recs[i].data[fld2];
					}
					
					var mkr = new mxn.Marker(new mxn.LatLonPoint(gps.latitude, gps.longitude));
					
					maxLat = Math.max(maxLat, gps.latitude);
					minLat = Math.min(minLat, gps.latitude);
					maxLon = Math.max(maxLon, gps.longitude);
					minLon = Math.min(minLon, gps.longitude);
					
					mkr.openInfoBubble.addHandler(function(event_name, event_source, event_args) {
					
						for(mkr in map.markers)
						{
							if(map.markers[mkr].proprietary_infowindow)map.markers[mkr].proprietary_infowindow.close();
						}
					});
					this.markers[recs[i].data[this.key]] = mkr;
					map.addMarkerWithData(mkr,data);
					
					if(bnds.isEmpty() || ! bnds.contains )
					
					sidebarHtml = sidebarHtml.replace(' ###', '');
					
				}
				else
				{
					this.descs[recs[i].data[this.key]] = descHtml;
					sidebarHtml = sidebarHtml.replace('###', 'nolocation') + " (no location)";
				}
				
				sidebarHtml += "</a></p>"
			}
			
			map.maps["googlev3"].fitBounds(new google.maps.LatLngBounds(new google.maps.LatLng(minLat, minLon), new google.maps.LatLng(maxLat, maxLon)));
			//map.autoCenterAndZoom();
			this.minT = Math.min(this.minT, Number(recs[i].data["created"]));
			this.maxT = Math.max(this.maxT, Number(recs[i].data["created"]));
			var slider = Ext.getCmp('timeSlider');
			
			if(slider.minValue > this.minT)
			{
				slider.setMinValue(this.minT);
				slider.setValue(0, this.minT, false);
			}else{
				slider.setValue(0, this.minT, false);
				slider.setMinValue(this.minT);
			}
			
			if(slider.maxValue < this.maxT)
			{
				slider.setMaxValue(this.maxT);
				slider.setValue(1, this.maxT, false);
			}else{
				slider.setValue(1, this.maxT, false);
				slider.setMaxValue(this.maxT);
			}
			Ext.get('timeText').update("From " + new Date(slider.getValue(0)).toLocaleString() + " to " + new Date(slider.getValue(1)).toLocaleString())
			
		}
		Ext.get('entryList').update(sidebarHtml);
		
		this.drawMapLegend();
	}
	
	this.getForm = function(rec)
	{
		this.ctrs = [];
		
		for(this.fld in this.fields)
		{

			ctrl = false;
			
			if(this.fields[this.fld].fkTable)//Detect if the field is a foreign key
			{
				var url = location.href;
				url = url.replace("/" + this.name, "");
				if(url.indexOf(survey.name) < 0)
				{
					if(url.charAt(url.length -1) != "/") url += "/";
					url += survey.name;
				}
				
				
				if(this.branchOf) continue;
				ctrl = {};
				ctrl.id = this.fld;
				ctrl.fieldLabel = this.fields[this.fld].text;
				ctrl.xtype = "combo";
				ctrl.store = new Ext.data.JsonStore({
					autoLoad : false,
					baseParams : {
						limit : 25,
						start : 0,
						mode : 'list',
						sort : 'created',
						dir : 'asc'
					},
					storeId : this.fld + "_store",
					idProperty : this.fld,
					id : this.fld + "_store",
					fields : [this.fld],
					proxy: new Ext.data.HttpProxy({
						method: 'GET',
						url : url + (url.charAt(url.length -1) == "/" ? "" : "/")  + this.fields[this.fld].fkTable + '.json'
					}),
					root : this.fields[this.fld].fkTable,
					totalProperty : 'count'
				});
				ctrl.mode = "remote";
				ctrl.pageSize = 25;
				ctrl.triggerAction = "all";
				ctrl.lazyRender = true;
				ctrl.allowBlank = false;
				ctrl.typeAhead = true;
				ctrl.displayField = this.fld;
				ctrl.valueField = this.fld;
				ctrl.forceSelection = true;
				ctrl.fkParentField = this.fields[this.fld].fkParentField;
				ctrl.fkParentTbl = this.fields[this.fld].fkParentTbl;
				ctrl.fkChildField = this.fields[this.fld].fkChildField;
				ctrl.fkChildTbl = this.fields[this.fld].fkChildTbl;
				
				ctrl.listeners = {
					'expand' : function(cbo)
					{
						if(Ext.getCmp(this.fkParentField))
						{
							
							if(Ext.getCmp(this.fkParentField).getValue()){
								cbo.store.load({
									callback : function (recs, opts, success) {
									
										if(recs.length == 0)
										{
											alert("The " + survey.tables[this.fkParentTbl].key + " that you selected has not had any " + pluralize(this.fieldLabel) + " added to it yet.");
										}
									},
									scope: this
								});
							}
							else
							{
								alert('Please choose the value of ' + Ext.getCmp(this.fkParentField).fieldLabel + ' before you choose the value of this field');
								this.collapse();
							}	
						}
						
					},
					'select' : function(cbo, rec,idx)
					{
						cbo2 = Ext.getCmp(cbo.fkChildField)
						if(cbo2)
						
						{
							cbo2.disable();
							if(cbo.getValue())
							{
								cbo2.setValue("");
								cbo2.store.baseParams[cbo.id] = cbo.getValue();
								cbo2.store.load({
									callback : function (recs, opts, success) {
										if(recs.length == 0)
										{
											alert("The " + this.fieldLabel + " that you selected has not had any " + pluralize(survey.tables[this.fkChildTbl].name) + " added to it yet.");
										}
									},
									scope: cbo
								});
							}
							else
							{
								Ext.getCmp(cbo.fkChildField).store.clearFilter();
							}
						
							Ext.getCmp(cbo.fkChildField).enable();
						}
					},
					scope: ctrl
				};
			}
			else
			{
				var cfg = {id :  this.name + '_' + this.fld, parentKey : this.key};
				if(rec && this.fields[this.fld].type == "branch")  {
					cfg.parentKeyValue = rec.data[this.key];
				};
				
				ctrl =this.fields[this.fld].getControl(cfg);
				
			}
			try{
				if(this.fields[this.fld].hidden && this.key == this.fld)
				{
					ctrl.value = getID();	
				}
				else
				{
					ctrl.readOnly = false;
				}
			}catch(e){alert(ctrl.id)}
			if(rec){
				//if the form is being used for an edit give the control it's value
				ctrl.value = rec.data[this.fld];
				//if the form is being used for an edit and this is the key field make it readonly so the user cannot edit it and break the reference to it's backend object
				ctrl.readOnly = this.fields[this.fld].id == this.key;
				ctrl.parentID = this.fields[this.key].value;
			}
			else
			{
				
				if(ctrl.id == this.name + "_DeviceID") ctrl.value = uid;
				if(ctrl.id == this.name + "_created")
				{
					var date = new Date();
					ctrl.value = date.getTime();
				}
			}
			
			this.ctrs.push(ctrl);
		}
		
		this.frm = new Ext.form.FormPanel({
			//title: this.name,
			id: this.name + "form",
			autoScroll : true, 
			monitorValid: true,
			padding: 10,
			items:this.ctrs,
			labelSeparator: "",
			buttons:[{
				id:this.name + 'sub',
				text:'Submit',
				formBind: true
			},{
				id: this.name + 'cnl',
				text:'Cancel'
			}],
			defaults:{
				anchor : "95%"
			}
		});
		
		return this.frm;
	}

	this.deleteEntry = function(key)
	{
		if(this.branchOf)
		{
			if(!this.deletedBranches) this.deletedBranches = [];
			this.store.remove(this.store.getById(key));
			this.deletedBranches.push(key);
		}
		else
		{
			Ext.Ajax.request({
				url: "./" + this.name + "/" + key,
				method: 'DELETE',
				success: function(res, opts){
					if(res.responseText.match(/^Message\s?:/))
					{
						alert(res.responseText.replace(/^Message\s?:/, ""));
					}
					else
					{
						this.store.load();
					}
				},
				failure : function(res, opts)
				{
					if(res.responseText.match(/^Message\s?:/))
					{
						alert(res.responseText.replace(/^Message\s?:/, ""));
					}
				},
				scope: this
			});
		}
	}
	
	this.addEntry = function(args, callback, scope)
	{
		newArgs = {};
		for(a in args)
		{
			newArgs[a.replace(this.name + "_", "")] = args[a];
		}
		newArgs["created"] = new Date().getTime();
		newArgs["DeviceID"] = uid;
 		
		if(this.branchOf)
		{
			newArgs[survey.tables[this.branchOf].key] = Ext.getCmp(this.branchOf + "_" + survey.tables[this.branchOf].key).getValue();
			var recT = Ext.data.Record.create(this.store.fields);
			var rec = new recT(newArgs, newArgs[this.key]);
			this.store.add(rec);
			callback(scope);
		}
		else
		{
			pars = {}
			pars["mode"] = "list";
			pars["sort"] = "created";
			pars["dir"] = "asc";
			pars[this.key] = newArgs[this.key]; 
			
			var ct = this;
			
			var path = location.pathname ;//+ ".json"
			if(path.indexOf(survey.name) < 0) path = path + "/" + survey.name;
			if(path.indexOf(this.name) < 0) path = path + "/" + this.name;
			path += ".json";
			
			var sx = this;
			
			Ext.Ajax.request({
				url: path,
				method: 'GET',
				params : pars,
				success : function(res, opts)
				{
					
					var at = Ext.decode(res.responseText);
						
					if(at.count == 0 || confirm("You have entered the same primary key as another record. Do you wish to overwrite the previous record"))
					{
						try
						{
							Ext.Ajax.request({
								url: path,
								method: 'POST',
								params: newArgs,
								success: function(res, opts){
																		
									if(this.branchForms.length > 0)
									{
										this.saveBranches(false, callback, scope);
									}
									else
									{
										if(this.store.load) this.store.load({params: {start : 0, limit: 25, mode: 'list'}});
										callback(this);
									}
									//callback(scope);
								},
								failure : function(res, opts)
								{
									alert(res.responseText.replace(/Message\s?:/,""));
								},
								scope: this
							});
						}
						catch(e) {alert(e);}
					}
				},
				scope: sx
			});
		}
	}
	
	this.saveBranches = function(edit, callback, scope)
	{
		completed = 0; succeeded = 0;
		for(var i = 0; i < this.branchForms.length; i++)
		{
			var dbs =  survey.tables[this.branchForms[i]].deletedBranches;
			if(dbs && dbs.length > 0)
			{
				for(var j = 0; j < dbs.length; j++)
				{
					Ext.Ajax.request({
						url: "./" + this.branchForms[i] + "/" + dbs[j],
						method: 'DELETE',
						success: function(res, opts){
							completed++;
							succeeded++;
							if(succeeded == bfs.length+ dbs.length)
							{
								scope.store.load({params: {start : 0, limit: 25, mode: 'list'}});
								callback(scope);
							}
							else if(completed == bfs.length+ dbs.length)
							{
								alert("oops");
							}
						},
						failure : function(res, opts)
						{
							completed++;
							if(completed == bfs.length+ dbs.length)
							{
								alert("oops");
							}
						}
					});
				}
			}
			
			var bfs = survey.tables[this.branchForms[i]].store.getRange();
			if(bfs.length > 0)
			{
				for(var j = 0; j < bfs.length; j++)
				{
					
					bfs[j].data[this.key] = newArgs[this.key];
					Ext.Ajax.request({
						url: "./" + this.branchForms[i] + (edit && bfs[j].dirty ? "/" + bfs[j].data[survey.tables[this.branchForms[i]].key] : ""),
						method: edit && bfs[j].dirty ? 'PUT' : 'POST',
						params: bfs[j].data,
						success: function(res, opts){
							completed++;
							succeeded++;
							if(succeeded == bfs.length + dbs.length)
							{
								this.store.load({params: {start : 0, limit: 25, mode: 'list'}});
								callback(scope);
							}
							else if(completed == bfs.length+ dbs.length)
							{
								alert("oops");
							}
						},
						failure : function(res, opts)
						{
							completed++;
							if(completed == bfs.length+ dbs.length)
							{
								alert("oops");
							}
						}
					});
				}
			}
			else
			{
				//this.store.load({params: {start : 0, limit: 25, mode: 'list'}});
				callback(scope);			
			}
		}
	}
		
	this.editEntry = function(key, args, callback, scope)
	{
		newArgs = {};
		for(a in args)
		{
			newArgs[a.replace(this.name + "_", "")] = args[a];
		}
		newArgs["lastEdited"] = new Date().getTime();

		if(this.branchOf)
		{
			var rec = this.store.getById(key);
			for(a in newArgs)
			{
				rec.data[a] = newArgs[a];
			}
			//this.store.add(rec);
			rec.markDirty();
			Ext.getCmp(this.name + "_grid").getView().refresh();
			callback(scope);
		}
		else
		{
			
			Ext.Ajax.request({
				url: "./" + this.name + "/" + key ,
				method: 'PUT',
				params: newArgs,
				success: function(res, opts){
					if(scope.branchForms.length > 0)
					{
						scope.saveBranches(true, callback, scope);
					}
					else
					{
						scope.store.load({params: {start : 0, limit: 25, mode: 'list'}});
						callback(scope);
					}
				},
				scope: this
			});
		}
	}
	

}

var EcField = function()
{
	 this.parent = false;
	
    this.text = '';
    this.id = '';
    this.required = false;
    this.type = '';
    this.isinteger = false;
	this.isdouble = false;
    this.options = [];
    this.local = false;
    this.title = false;
	this.regex = false;
	this.verify = false;
	this.date = false;
	this.time = false;
	this.genKey = false;
	this.display = true;
	this.edit = true;
	
	this.date = false;
	this.time = false;
	this.setdate = false;
	this.settime = false;
	
	this.min = undefined;
	this.max = undefined;
	this.defaultValue = undefined;
	
	this.match = false;
	this.crumb = false;
	
	this.hidden = false;
	this.search = false;
	
	this.form = false;
	
	this.fkTable = false;
	this.fkField = false;
	
	this.jump = false;

    this.parse = function(xml)
    {
		this.type = xml.tagName;
		this.id = xml.getAttribute('name');
		if(!this.id) this.id = xml.getAttribute('ref');
		this.title = Boolean(xml.getAttribute('title'));
		this.required = xml.getAttribute('required') == "true";
		this.integer = xml.getAttribute('integer') == "true";
		this.isdouble = xml.getAttribute('decimal') == "true";
		this.local = xml.getAttribute("local") == "true";
		this.regex = xml.getAttribute('regex');
		this.verify = xml.getAttribute('verify')=="true";
		this.genkey = xml.getAttribute('genkey') == "true";
		this.hidden = xml.getAttribute('display') == "false";
		this.search = xml.getAttribute('search') == "true";
		
		this.date = xml.getAttribute('date');
		this.time = xml.getAttribute('time');
		this.setDate = xml.getAttribute('setdate');
		this.setTime = xml.getAttribute('settime');
		
		this.edit = Boolean(xml.getAttribute('edit'));
		this.min = Number(xml.getAttribute('min'));
		this.max = Number(xml.getAttribute('max'));
		this.defaultValue = xml.getAttribute('default');
		
		this.jump = xml.getAttribute('jump');
		
		this.match = xml.getAttribute('match');
		this.crumb = xml.getAttribute('crumb');
		
		if(this.type == "branch")
		{
			this.form = xml.getAttribute("branch_form");
		}
		else if(this.type == "group")
		{
			this.form = xml.getAttribute("group_form");
		}
		else
		{
			for(t in survey.tables)
			{
				if(survey.tables[t].key == this.id)
				{
					//FUTURE-PROOF : if we want to allow the foreign key field to have a differnt name to the primary key field
					this.fkParentTbl = survey.getPrevTable(survey.tables[t].name).name;
					this.fkParentField = survey.getPrevTable(survey.tables[t].name).key;
					this.fkChildTbl = survey.getNextTable(survey.tables[t].name).name;
					this.fkChildField = survey.getNextTable(survey.tables[t].name).key;
					
					this.fkTable = survey.tables[t].name;
					this.fkField = survey.tables[t].key;
				}
			}
		}
		
		this.text = xml.getElementsByTagName('label')[0].firstChild.data;
		var opts = xml.getElementsByTagName('item');
		for(var o = 0; o < opts.length; o++)
		{
			this.options.push([opts[o].getElementsByTagName('value')[0].firstChild.data,opts[o].getElementsByTagName('label')[0].firstChild.data]);  
		}
    }
    
    this.getControl = function(cfg)
    {
		var xtypes = {
			"input" : Ext.form.TextField,
			"textarea": Ext.form.TextArea,
			"select": EcCheckboxGroup,
			"select1": Ext.form.ComboBox,
			"barcode" : Ext.form.TextField,
			"photo" : mediaPanel,
			"video" : mediaPanel,
			"audio" : mediaPanel,
			"gps": GPSPanel,
			"location" : GPSPanel,
			"radio" :  Ext.form.ComboBox,
			"branch" : BranchPanel,
			"group" : Ext.form.ComboBox,
			"" : Ext.form.TextField
		}
		
		
		
		var ctrl = {
			id: this.id,
			fieldLabel : this.text,
			readOnly:  this.hidden,
			parent : this.parent
		};
		
		if(this.type == "")
		{
			ctrl.hidden = true;
		}
		else
		{
			ctrl.hidden = false;
		}
		
		if(this.integer){
			ctrl.regex = /^-?[0-9]*?$/;
			ctrl.regexText = this.text + " must have an integer value";
		}
		
		if(this.isdouble)
		{
			ctrl.regex = /^-?[0-9]*(\.[0-9]+)?$/;
			ctrl.regexText = this.text + " must have a decimal value";
		}
		
		if(this.regex)
		{
			ctrl.regex = new RegExp(this.regex);
			ctrl.regexText = this.text + " must conform to the format specified by the form designer";
		}
		
		if(this.options.length > 0 && (this.type == "select1" || this.type == "radio")){
			ctrl.store = this.options;
			
		}
		
		if(this.options.length > 0 && (this.type == "select")){
			ctrl.items = [{columnWidth : 1, items: []}];
			ctrl.columns = ["one"];
			
			for (var o = 0; o < this.options.length; o++)
			{
				ctrl.items[0].items.push({
					fieldLabel : this.options[o][0], value : this.options[o][1]
				});
			}
		}
		
		if(this.type == "radio" || this.type == "select1" || this.fkField)
		{
			ctrl.mode = "local";
			ctrl.triggerAction = "all";
			ctrl.lazyRender = "true"
		}
		
		if(this.type == "group" || this.type == "branch")
		{
			ctrl.form = this.form;
		}
		
		
		if(this.match)
		{
			ctrl.match = this.match;
			ctrl.validator = function(value)
			{
				if(value == "") return false;
				mParts = this.match.split(",");
				var pVal = Ext.getCmp(mParts[1]).getValue();
				if(!pVal)
				{
					//alert("Please select a value for " + Ext.getCmp('Person_ID').label.dom.firstChild.data + " before filling out " + this.label.dom.firstChild.data)
					return false;
				}
				var rex = new RegExp(mParts[2]);
				
				if(pVal.match(rex) && value.match(rex) && pVal.match(rex)[0] == value.match(rex)[0])
				{
					return true;
				}
				else
				{
					return false;
				}
			}
		}
		
		ctrl.listeners = {};
		if(this.verify)
		{
			ctrl.listeners['change'] = function(fld, newVal, oldVal)
			{
				if(!this.isValid()) return false;
				if(prompt("Please re-enter the value for " + fld.label.dom.firstChild.data) != newVal)
				{
					alert("Values did not match, data has not been changed");
					fld.setValue(oldVal);
				}
			}
			
		}
		else if(this.jump)
		{
			ctrl.jump = this.jump;
			ctrl.listeners['select'] = function(fld, rec, idx)
			{
				var jumpParts = this.jump.split(",");
				var jField = false;
				
				idx++;//idx is zero indexed, jump is 1 indexed

				for(var i = 0; i < jumpParts.length ; i += 2)
				{
					if(jumpParts[i+1] == idx || (jumpParts[i+1].match(/^!.*/) && jumpParts[i+1] != "!" + idx))
					{
						jField = jumpParts[i];
						break;
					}
				}
				
				var start = false;
				for(var f in table.fields)
				{
					if(table.name + "_" + f == fld.id) 
					{
						start = true
					}
					else if(start && f == jField)
					{
						return;
					}
					else if(start && jField)
					{
						Ext.getCmp(table.name + "_" + f).disable();
					}
					else
					{
						if(Ext.getCmp(table.name + "_" + f))Ext.getCmp(table.name + "_" + f).enable();
					}
				}	
			}
			
		}
		
	
		
		Ext.apply(ctrl, cfg);
		return new xtypes[this.type](ctrl);
	}
	
	this.formatValue = function(value)
	{
		switch(this.type)
		{
			case "photo" : return "<img src=\""+value+"\" alt=\""+value+"\"/>";
			default : return value;
		}
	}
}
