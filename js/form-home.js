//'use strict';
var formName = '';
var map = null;

var mapData = [];
var markers = [];

var qry = location.search.trimChars('?');
var sortCol = 'created';
var sortDir = 'asc';
var pageNo = 0;
var numEnts = 25;

var filterField = '';
var filterValue = '';

var nextFormName = '';

var markerClusterer = null;

var bubble = null;

var geoField = null;
var defaultColourField = null;

//var dateFormat = ' dd MM yyyy hh:mm:ss';
var dateFormat = ' dd MM yyyy';

var hiddenColumns = {created: false, uploaded: true, lastEdited: true, DeviceID: true};

var totalents = 0;
var mapChunkSize = 1000;
var map_lastupdated = new Date(0);
var mapBounds = null;
var animate_duration = 500;
var mapHeight = 0;
var mapchecker = null;

var colours = [
    '#FF0000',
    '#008000',
    '#C0C0C0',
    '#FFFF00',
    '#FFFFFF',
    '#800080',
    '#808080',
    '#800000',
    '#0000CC',
    '#9FFF9F',
    '#FF9FCC',
    '#B366FF',
    '#784421'
];

var _graphdiv;
var _graphfield;
var _graphtype;
var mapFilterFunction;
var maximised_map_height;

if (localStorage && localStorage.getItem('num_entries')) numEnts = localStorage.getItem('num_entries');

var IE8 = false; //annoying, but we need the SVG to work... or the workaround to work in IE8

$(document).ready(function () {
    //immersive mode
    var fullscreen = false;

    $('.immersive-mode-btn a').on('click', function () {
        if (fullscreen) {
            $('#pageHead').fadeIn();
            $('#breadcrumbs').fadeIn();
            $('.social').fadeIn();
            $('footer').fadeIn();

        }
        else {
            $('#pageHead').fadeOut();
            $('#breadcrumbs').fadeOut();
            $('.social').fadeOut();
            $('footer').fadeOut();
            $('#sidebar').css({'margin-top': '0px'});

            $('#map-container .panel.panel-default').resizable({

                handles: {s: '.map-resize-handle'},


                resize: function () {
                    $('#map-container .panel').css({'padding-bottom': '0px'});

                    $('#map').height($('#map-container .panel.panel-default').height() - 100);

                    console.log($('#map').height());
                    console.log($('#map-container .panel.panel-default').height());

                    google.maps.event.trigger(map, 'resize');
                    // map.fitBounds(new google.maps.LatLngBounds(new google.maps.LatLng(minlat, minlon), new google.maps.LatLng(maxlat, maxlon)));

                },

                start: function () {
                    google.maps.event.trigger(map, 'resize');

                }
            });
        }
        fullscreen = !fullscreen;
    });
});


$(function () {
    var ele = document.createElement('img');
    ele.src = '../images/loading.gif';

    IE8 = $.browser.msie && (parseInt($.browser.version, 10) < 9);

    url = location.href.trimChars('/');
    if (url.indexOf('?') > 0)url = url.substr(0, url.indexOf('?'));
    formName = url.substr(url.lastIndexOf('/') + 1).replace(/#.*$/, '');
    url = url.substr(0, url.lastIndexOf('/'));

    loader = new EpiCollect.LoadingOverlay();


    var form = '<div class="form-inline"> <div class="form-group"><label for="field">Field Name</label><select name="field" class="colourFieldList form-control"></select></div></div>';

    $('.graphPanel').graphPanel(
        {
            form: form

        });


    $('#tabs').tabs({
        activate: function (evt, ui) {
            if (ui.newPanel.attr('id') === 'mapTab') {
                google.maps.event.trigger(map, 'resize');
                if (mapBounds) {
                    map.fitBounds(mapBounds)
                }
                drawGraph($('#graphOne .ecplus-pane'), $('#mapColourField').val(), 'pie');

            }
        }
    });

    $('#csvform').accordion({
        collapsible: true,
        active: false
    });

    $('#num_entries').val(numEnts);

    $('#num_entries').change(function (e) {
        numEnts = $('#num_entries').val();
        getData();
    });

    var toggle_fields = '"#showHideFields"';

    $('#showHideFields').append('<a class="btn btn-default" href="javascript:toggleMenu(' + toggle_fields + ')">Show/Hide Fields</a><div class="ecplus-dropdown-menu" style="display:none;"></div>');

    EpiCollect.loadProject(url.trimChars('/') + '.xml', loadCallback);

    $.fn.disableSelection = function () {
        return this.each(function () {
            $(this).attr('unselectable', 'on')
                .css({
                    '-moz-user-select': 'none',
                    '-webkit-user-select': 'none',
                    'user-select': 'none',
                    '-ms-user-select': 'none'
                })
                .each(function () {
                    this.onselectstart = function () {
                        return false;
                    };
                });
        });
    };

    $(window).resize(function () {


        mapHeight = $(window).height() - 470;


        if ($('#graphOne').hasClass('minimised')) {
            $('#map').height(mapHeight);
            //$('#map').width($('#graphOne').offset().left - $('#map').offset().left - 14)
        }
        else {
            $('#graphOne').height(mapHeight);
            $('#graphOne').width($('#graphOne').offset().left - $('#map').offset().left - 14);
        }
        $('#map-container').height(mapHeight);
    });
    $(window).resize();

    $('#graphbar').resizable({
        handles: 'w',
        resize: function (event, ui) {
            ui.helper.css('left', '');
            $('#map, #bottombar').css('margin-right', ui.helper.width() + 'px');
        }
    });

    if (window['google'] && google['maps']) startMapLoad();
});

function setProgressMessage(msg) {
    $('.progress-label').text(msg);
}


function setProgress(n) {
    if (n < totalents) {
        setProgressMessage('loading... ' + n + '/' + totalents);
    }
    else {
        setProgressMessage('Loaded');
        setTimeout(function () {
            $('#progressbar').hide();
        }, 2000);
    }
    $('#progressbar').progressbar('option', 'max', totalents);
    $('#progressbar').progressbar('value', n);


}

function toggleMenu(selector) {

    var jq = $(selector + ' .ecplus-dropdown-menu');
    var visible = jq.css('display') !== 'none';

    if (visible) jq.hide();
    else {
        $(document.body).click(function (evt) {
            if ($(evt.target).attr("type") !== "checkbox" && $(evt.target)[0].tagName !== "LABEL")
                $(selector + " .ecplus-dropdown-menu").hide();
        });

        jq.show();

    }
}

function createMap(div) {
    var doc = document;
    var _maps = google.maps;

    map = new _maps.Map(doc.getElementById(div), {
        center: new _maps.LatLng(0, 0),
        mapTypeId: _maps.MapTypeId.ROADMAP,
        zoom: 1,
        panControl: false,
        rotateControl: false,
        zoomControl: true
    });
}

function loadCallback(prj) {
    project = prj;

    var hasMap = (project.forms[formName].gpsFlds.length > 0);
    if (hasMap) geoField = project.forms[formName].gpsFlds[0];

    for (nextForm = project.getNextForm(formName); nextForm; nextForm = project.getNextForm(nextForm.name)) {
        if (nextForm.main) {
            nextFormName = nextForm.name;
            break;
        }
    }

    if (hasMap && (!window["google"] || !google.maps)) {
        hasMap = false;
        $('#main h1:first').after('<p class="err">Google maps is not currently available so the mapping interface has been disabled.</p>');
    }

    if (hasMap) {
        createMap("map");
    }
    else {
        $("#tabs li")[1].style.display = "none";
    }


    $("#tableTab .ecplus-toolbar").after("<div class=\"ecplus-data-container\"><table class=\"ecplus-data\"><thead><tr></tr></thead><tbody></tbody></table></div>");

    for (f in project.forms[formName].fields) {
        var fld = project.forms[formName].fields[f];
        var text = fld.text;
        if (text.length > 40) text = text.substr(0, 37) + "...";

        if (hiddenColumns[f] === undefined) {
            hiddenColumns[f] = false;
        }


        $(".ecplus-data tr").append("<th class=\"" + f + "\" >" + text + "</th>");
    }


    if (nextFormName) {
        if (hiddenColumns[nextFormName] === undefined) hiddenColumns[nextFormName] = false;

        $(".ecplus-data tr").append("<th class=\"" + nextFormName + "_entries\">" + nextFormName + " Entries</th>");
    }

    var ecplus_table_header = $(".ecplus-data th");

    ecplus_table_header.resizable({
        handles: "e",
        alsoResize: ".ecplus-data",
        resize: function (ele) {
            $("td." + ele.element[0].className.substring(0, ele.element[0].className.indexOf(" "))).css({"max-width": (ele.element.width() - 5) + "px"});
        }
    });

    ecplus_table_header.append('<img src="../images/uparrow.png" class="asc data-table-arrow-images" width="12" /><img src="../images/downarrow.png" class="desc data-table-arrow-images" width="12" />');
    ecplus_table_header.click(function (evt) {
        var newSortCol = evt.target.className;
        newSortCol = newSortCol.substr(0, newSortCol.indexOf(' '));

        ecplus_table_header.removeClass("desc");
        ecplus_table_header.removeClass("asc");

        window.evt = evt;
        if (newSortCol === sortCol && sortDir === "asc") {
            $(evt.target).addClass("desc");
            sortDir = "desc";
        }
        else {
            $(evt.target).addClass("asc");
            sortDir = "asc";
        }
        sortCol = newSortCol;
        getData();
        evt.preventDefault();
    });

    for (fld in project.forms[formName].fields) {
        var field = project.forms[formName].fields[fld];
        var text = field.text;
        if (text.length > 25) text = text.substr(0, 22) + "...";

        $('#showHideFields .ecplus-dropdown-menu').append("<input class=\"showHideToggle\" type=\"checkbox\" id=\"" + fld + "ShowHide\" value=\"" + fld + "\" " + (hiddenColumns[fld] ? "" : "checked=\"checked\"") + " /><label for=\"" + fld + "ShowHide\">" + text + "</label><br />");

        if (field.type !== "location" && field.type !== "gps" && field.type !== "branch"
            && field.type !== "video" && field.type !== "audio" && field.type !== "photo" && field.type !== "") {
            $('.fieldList').append("<option value=\"" + fld + "\">" + text + "</option>");
            if (field.type === "select" || field.type === "radio" || field.type === "select1"
                || fld === project.getPrevForm(formName).key || fld === "DeviceID") {
                if (!defaultColourField) defaultColourField = fld;
                $('.colourFieldList').append("<option value=\"" + fld + "\">" + text + "</option>");
            }
        }
    }

    getData();

    $('.showHideToggle').button();
    $('.button-set').buttonset();
    $('.btn').button();
    $('#showHideFields a').button({
        icons: {
            secondary: "ui-icon-triangle-1-s"
        }
    });


    $('#showHideFields input').change(function (evt) {
        var ctrl = $(evt.target).val();
        hiddenColumns[ctrl] = !evt.target.checked;
        showHideColumns();
    });

    $('#filter_value').autocomplete(
        {
            source: baseUrl + "/" + $('#filter_fields').val()
        });

    $('#mapFilterValue').autocomplete(
        {
            source: baseUrl + "/" + $('#mapFilterField').val()
        });

    $('#filter_fields').change(function () {
        $('#filter_value').autocomplete("option", "source", baseUrl + "/" + $('#filter_fields').val());
    });

    $('#mapFilterField').change(function () {
        $('#mapFilterValue').val("");
        $('#mapFilterValue').autocomplete("option", "source", baseUrl + "/" + $('#mapFilterField').val());
    });

    $('#filter_value').bind('autocompletechange', function () {
        doFilter($('#filter_fields').val(), $('#filter_value').val());
    });

    var t = new Date().getTime();
    $("#slider-range").slider({
        range: true,
        min: 0,
        max: t,
        values: [0, t],
        slide: function (event, ui) {
            filterMap(function (data) {
                var _created = EpiCollect.toJSTimestamp(data["created"]);
                return _created >= ui.values[0] && _created <= ui.values[1];
            });
            $("#timeFrom").val(new Date(ui.values[0]).format(dateFormat));
            $("#timeTo").val(new Date(ui.values[1]).format(dateFormat));

            $(".filterControl").removeClass("active");
            $("#timeFilter").addClass("active");


        }
    });

    $('.minmax').mouseenter(function () {
        $(this).parent().addClass('hover');
    });
    $('.minmax').mouseleave(function () {
        $(this).parent().removeClass('hover');
    });

    var _gps_f = prj.forms[formName].gpsFlds;
    var _gps_n = _gps_f.length;
    var _gps_o = '';
    var _frm_flds = prj.forms[formName].fields;

    for (var n = _gps_n; n--;) {
        var fld = '<option value="' + _gps_f[n] + '" ' + (n ? '' : 'SELECTED') + '>' + _frm_flds[_gps_f[n]].text + '</option>';
        _gps_o = fld + _gps_o;
    }

    $('#fieldSelector').append(_gps_o);

    $('#fieldSelector').change(function () {
        setLocationField($(this).val());
    });
};

function firstPage() {
    pageNo = 0;
    getData();
}

function lastPage() {

    var total = Number($("#total").text());
    if (total === 0)return;
    var rem = total % numEnts;
    if (rem === 0) rem = numEnts;
    pageNo = (total - rem) / numEnts;
    getData();
}

function turnPage() {
    var total = Number($("#total").text());
    var last = Number($("#end").text());

    if (total > last) {
        pageNo++;
        getData();
    }
}

function turnBackPage() {
    if (pageNo > 0) pageNo--;
    getData();
}

function getData() {
    loader.start();
    offset = pageNo * numEnts;

    var urlStr = url + "/" + formName + ".json?full_paths=false&mode=list&start=" + offset + "&limit=" + numEnts + "&sort=" + sortCol + "&dir=" + sortDir + (location.search ? "&" + location.search.substr(1) : "");
    var statUrl = url + "/" + formName + "/__stats?" + location.search.substr(1);

    if (filterField && filterValue) {
        urlStr += "&" + filterField + "=" + filterValue;
        statUrl += "&" + filterField + "=" + filterValue;
    }


    $.ajax({
        url: urlStr,
        success: dataLoad,
        async: true
    }).error(function () {
        loader.stop();
    }).success(function () {
        $.getJSON(statUrl, statLoad);
    });

    $('#start').text(offset);
}

function startMapLoad() {
    $('#progressbar').progressbar({
        value: false,
        message: "Getting total entries"
    });
    $('#progressbar').show();
    $.getJSON(url + "/" + formName + "/__stats", mapTotalCallback);

    mapData = [];
}

function mapTotalCallback(data, status, xhr) {
    var stats = data;
    totalents = stats.ttl;
    $('[href=#mapTab]').addClass('ec-loading');
    //Init progress bar

    setProgress(0);

    if (markerClusterer) markerClusterer.removeMarkers(markerClusterer.markers_);

    $('#sidebar').empty();
    for (var mkr = markers.length; mkr--;) {
        if (!markers[mkr]) continue;

        markers[mkr].setMap(null);

    }

    markers = [];
    mapData = [];

    getMapData(mapChunkSize, 0);
}

function getMapData(chunkSize, offset) {

    var urlStr = url + "/" + formName + ".json?full_paths=false&mode=list&limit=" + chunkSize + "&start=" + offset;

    if (filterField && filterValue) {
        urlStr += "&" + filterField + "=" + filterValue;
        statUrl += "&" + filterField + "=" + filterValue;
    }
    $.getJSON(urlStr, mapDataCallback);

}

function doFilter(field, value) {
    if (field === filterField && value === filterValue) return;
    filterField = field;
    filterValue = value;
    pageNo = 0;
    getData();
}

function dataLoad(entries) {
    $(".ecplus-data tbody").empty();

    if (entries.length === 0) {
        if (pageNo > 0) pageNo--;
        loader.stop();
        return;
    }

    $(".ecplus-data th, .ecplus-data  td").show();

    window.ecplus_entries = entries;

    $('#end').text(Number($('#start').text()) + Number(entries.length));
    $('#total').text(entries.count);


    var keyfield = project.forms[formName].key;
    var html = "";
    for (var i = 0; i < entries.length; i++) {
        html += "<tr class=\"" + (i % 2 === 1 ? " alt" : "") + "\">";
        for (f in project.forms[formName].fields) {
            var fld = project.forms[formName].fields[f];

            html += "<td class=\"" + f + "\">";


            html += fld.formatValue(entries[i][f], entries[i]);
            html += "</td>";
        }
        if (nextFormName) {
            var par_obj = {};


            for (var p_frm = project.forms[formName]; p_frm; p_frm = project.getPrevForm(p_frm.name, true)) {
                if (entries[i][p_frm.key]) {
                    par_obj[p_frm.key] = entries[i][p_frm.key];
                }
            }

            var ents = entries[i][nextFormName + "_entries"];

            if (!ents || ents === 'null') ents = 0;

            html += "<td>" + ents + " <a href=\"" + nextFormName + "?" + keyfield + "=" + entries[i][keyfield] + "&prevForm=" + formName + "\">View entries</a> | <a href=\"javascript:project.forms[project.getNextForm(formName).name].displayForm({ vertical : false, data : " + JSON.stringify(par_obj).replace(/"/g, '\'') + " });\">Add " + project.getNextForm(formName).name + "</a></td>";
        }
        html += "</tr>";
    }
    $(".ecplus-data tbody").append(html);

    for (f in project.forms[formName].fields) {
        $('.' + f).css({"max-width": $('th.' + f).width() + "px"});
    }

    $(".ecplus-data tr").click(function (evt) {
        $(".ecplus-data tr").removeClass("selected");
        $(evt.target.parentNode).addClass("selected");
    });

    showHideColumns();
    loader.stop();
}

function showHideColumns() {
    for (var fld in hiddenColumns) {
        if (hiddenColumns[fld])
            $("." + fld).hide();
        else
            $("." + fld).show();
    }
}

function statLoad(stats) {
    $("#total").text(stats["ttl"]);

}

function setLocationField(name) {
    var len = markers.length;
    geoField = name;

    for (var i = len; i--;) {
        var mkr = markers[i];
        var pos = getPositionFor(i);

        if (mkr && pos) {
            mkr.setPosition(pos);
            mkr.setMap(map);
        }
        else {
            mkr.setMap(null);
        }

    }

    markerClusterer.repaint();
}

function getMarkerIcon(colour) {
    if (IE8) {
        return "https://chart.googleapis.com/chart?chst=d_map_pin_letter&chld=%20|" + colour.replace('#', '');
    }
    else {
        return "http://" + location.host + SITE_ROOT + "/markers/point?colour=" + colour.replace('#', '');

    }
}

function mapDataCallback(data, status, xhr) {
    mapData = mapData.concat(data);

    var mincreated = (window['_mincreated'] ? _mincreated : new Date().getTime());
    var maxcreated = (window['_maxcreated'] ? _maxcreated : 0);

    var keyField = project.forms[formName].key;

    var dlen = data.length;
    var mlen = markers.length;

    var minlat = 180;
    var maxlat = -180;
    var minlon = 180;
    var maxlon = -180;

    var ttl_fld = project.forms[formName].titleField;
    if (!ttl_fld) ttl = project.forms[formName].key;

    var key_fld = project.forms[formName].key;

    for (var i = 0; i < data.length; i++) {

        var ttl = data[i][ttl_fld];
        if (!ttl || ttl.trimChars(' ') === '') {
            if (ttl_fld != key_fld) {
                ttl = data[i][key_fld];
            }
            else {
                ttl = data[i]['created'];
            }
        }

        if (data[i][geoField] && !data[i][geoField].accuracy) data[i][geoField].accuracy = (data[i][geoField].latitude !== 0 && data[i][geoField].longitude !== 0) ? 100 : -1;
        if (data[i][geoField] && data[i][geoField].latitude >= -90 && data[i][geoField].latitude <= 90 && data[i][geoField].longitude >= -180 && data[i][geoField].longitude <= 180 && data[i][geoField].accuracy > 0
            && data[i][geoField].longitude != null && data[i][geoField].longitude != null) {
            var mkr = new google.maps.Marker({
                id: data[i][keyField],
                position: new google.maps.LatLng(data[i][geoField].latitude, data[i][geoField].longitude),
                icon: new google.maps.MarkerImage(SITE_ROOT + "/markers/point"),
                index: i
            });

            minlat = Math.min(data[i][geoField].latitude, minlat);
            maxlat = Math.max(data[i][geoField].latitude, maxlat);
            minlon = Math.min(data[i][geoField].longitude, minlon);
            maxlon = Math.max(data[i][geoField].longitude, maxlon);

            google.maps.event.addListener(mkr, 'click', function (evt, x) {
                showBubbleFor(getMarkerAt(evt.latLng).index);
            });

            if (!markerClusterer) mkr.setMap(map);

            markers.push(mkr);
            addToSidebar(i, ttl);


        }
        else {
            markers.push(new google.maps.Marker({
                id: data[i][keyField],
                position: new google.maps.LatLng(0, 0),
                icon: new google.maps.MarkerImage(SITE_ROOT + "/markers/point"),
                index: i
            }));
            addToSidebar(i, ttl, false);
        }

        var _cre = EpiCollect.toJSTimestamp(data[i]["created"]);

        mincreated = Math.min(mincreated, _cre);
        maxcreated = Math.max(maxcreated, _cre);

        var uploaded = EpiCollect.parseDate(data[i]["uploaded"]);
        if (map_lastupdated < uploaded) map_lastupdated = uploaded;

        if (data[i]["lastEdited"]) {
            var updated = EpiCollect.parseDate(data[i]["lastEdited"]);
            if (map_lastupdated < updated) map_lastupdated = updated;
        }


    }


    //update progress bar
    setProgress(markers.length);

    if (markers.length < totalents && markers.length % mapChunkSize === 0) {
        _mincreated = mincreated;
        _maxcreated = maxcreated;
        getMapData(mapChunkSize, markers.length);
    }
    else {
        $("#slider-range").slider("option", "min", mincreated);
        $("#slider-range").slider("option", "max", maxcreated);

        $("#timeFrom").val(new Date(mincreated).format(dateFormat));
        $("#timeTo").val(new Date(maxcreated).format(dateFormat));

        colourMarkersBy($('#mapColourField').val());

        map.fitBounds(new google.maps.LatLngBounds(new google.maps.LatLng(minlat, minlon), new google.maps.LatLng(maxlat, maxlon)));

        if (!markerClusterer && !IE8) {
            markerClusterer = new MarkerClusterer(map, markers,
                {
                    maxZoom: 21,
                    gridSize: 40,
                    styles: [
                        {
                            url: SITE_ROOT + "/markers/cluster",
                            height: 50,
                            width: 50,
                            anchor: [24, 24],
                            textColor: 'transparent',
                            textSize: 14
                        },
                        {
                            url: SITE_ROOT + "/markers/cluster",
                            height: 50,
                            width: 50,
                            anchor: [24, 24],
                            textColor: 'transparent',
                            textSize: 14
                        },
                        {
                            url: SITE_ROOT + "/markers/cluster",
                            height: 50,
                            width: 50,
                            anchor: [24, 24],
                            textColor: 'transparent',
                            textSize: 14
                        },
                        {
                            url: SITE_ROOT + "/markers/cluster",
                            height: 50,
                            width: 50,
                            anchor: [24, 24],
                            textColor: 'transparent',
                            textSize: 14
                        }
                    ]
                });
        }

        if (markerClusterer) markerClusterer.addMarkers(markers);


        $('#map-container').height(mapHeight);

        $('#map').css('position', '');

        window.mapBounds = new google.maps.LatLngBounds(new google.maps.LatLng(minlat, minlon), new google.maps.LatLng(maxlat, maxlon));

        $('.minmax', $('#graphOne')).bind('click', function () {
                var div = $('.ecplus-pane', $(this).parent());
                var par = $(this).parent();
                var img = this;


                div.fadeOut(50);

                if (par.hasClass('minimised')) {

                    //maximising graph

                    maximised_map_height = $('#map-container .panel').height();

                    var mapWidth = $('#map').width();
                    $('#legend').hide();


                    par.animate({height: mapHeight, width: mapWidth}, animate_duration, 'swing', function () {
                        par.removeClass('minimised');

                        drawGraph(div, div.attr('graph-field'), div.attr('graph-type'));

                        $(img).attr('src', $(img).attr('src').replace('5_resize_fu', '4_resize_sma'));

                        div.fadeIn();

                    });
//							$('#map').switchClass('', 'minimised', 900, 'swing');
                    $('#map').animate({height: 210, width: 210}, animate_duration, 'swing', function () {
                        google.maps.event.trigger(map, 'resize');
                        //map.fitBounds(new google.maps.LatLngBounds(new google.maps.LatLng(minlat, minlon), new google.maps.LatLng(maxlat, maxlon)));
                    });

                    $('div#bottombar').animate({marginTop: 20}, animate_duration, 'swing', null);
                    $('.map-resize-handle').hide();
                    $('#map-container .panel.panel-default').resizable('disable');
                }

                else {
                    //when minimise graph

                    $('#legend').show();

                    $('.map-resize-handle').show();

                    $('#map-container .panel.panel-default').resizable('enable');


                    var mapWidth = $('#graphOne').width();
                    //par.switchClass('', 'minimised',1000,'swing',function(){
                    par.animate({height: 210, width: 210}, animate_duration, 'swing', function () {
                        par.addClass('minimised');

                        drawGraph(div, div.attr('graph-field'), div.attr('graph-type'));
                        $(img).attr('src', $(img).attr('src').replace('4_resize_sma', '5_resize_fu'));

                        div.fadeIn();
                    });


                    $('#map').animate({height: maximised_map_height - 100, width: mapWidth}, animate_duration, 'swing', function () {

                        $('div#bottombar').animate({marginTop: 20}, 100, 'swing', null);

                        google.maps.event.trigger(map, 'resize');
                        //map.fitBounds(new google.maps.LatLngBounds(new google.maps.LatLng(minlat, minlon), new google.maps.LatLng(maxlat, maxlon)));
                    });
                }
            }
        );


        $('[href=#mapTab]').removeClass('ec-loading');
        mapchecker = setInterval(function () {
            checkForMapUpdates()
        }, 6000)
    }

}

function showBubbleFor(mkrIdx) {
    if (bubble) closeBubble();

    var pos = getPositionFor(mkrIdx);
    if (pos) {
        bubble = new google.maps.InfoWindow({
            content: getContentFor(mkrIdx),
            position: pos
        });

        bubble.open(map);
    }
    else {
        EpiCollect.dialog({content: getContentFor(mkrIdx)})
    }
}

function getMarkerAt(latlng) {
    var mkrs = markers;

    for (mkr in mkrs) {
        if (mkrs[mkr] && mkrs[mkr].getPosition() === latlng) return mkrs[mkr];
    }
}

function addToSidebar(index, text, hasPosition) {

    var link;
    var truncated_text;

    truncated_text = (text.length > 20) ? text.substr(0, 20) + '...' : text;

    if (hasPosition !== false) {


        link = '<a class="list-group-item" href="javascript:showBubbleFor(' + index + ')" index="' + index + '">' + truncated_text + '</a>';
        $("#sidebar").append(link);
    }
    else {
        link = '<a class="list-group-item disabled" href="javascript:showBubbleFor(' + index + ')" index="' + index + '">' + truncated_text + '</a>';
        $("#sidebar").append(link);
    }
}

function closeBubble() {
    bubble.close();
}

function getPositionFor(mkrIdx) {
    if (mapData[mkrIdx][geoField]) {
        return new google.maps.LatLng(mapData[mkrIdx][geoField].latitude, mapData[mkrIdx][geoField].longitude);
    }
    else {
        return false;
    }
}

//build infoWindow
function getContentFor(mkrIdx) {
    var rec = mapData[mkrIdx];
    if (!rec) return '<i>No data</i>';

    var html = '<table class="ibubble ecplus-infowindow table table-striped table-bordered table-condensed">';
    var fields = project.forms[formName].fields;

    for (fld in fields) {
        if ((fields[fld].type !== '' || fld === 'created') && rec[fld]) {
            html += '<tr><th>' + fields[fld].text + '</th> <td> ' + fields[fld].formatValue(rec[fld]) + '</td></tr>';
        }
    }
    html += '</table>';
    return html;
}

function filterMap(filterFunction) {
    markerClusterer.setIgnoreHidden(true);
    mapFilterFunction = filterFunction;

    var len = mapData.length;
    var mkrs = markers;
    var data = mapData;

    for (var i = len; i--;) {
        var d = data[i];
        //TODO: Parrallelise
        var vis = filterFunction(data[i]);
        if (mkrs[i]) mkrs[i].setVisible(vis);

    }
    markerClusterer.repaint();
    redrawGraph();
}

function clearMapFilter() {
    var mkrs = markers;
    var len = markers.length;

    for (var i = len; i--;) {
        if (mkrs[i]) mkrs[i].setVisible(true);
    }
    markerClusterer.repaint();

    mapFilterFunction = false;

    $("#mapFilterField").val('');
    $("#mapFilterValue").val('');

    $(".filterControl").removeClass("active");
    $("#slider-range").slider("values", 0, $("#slider-range").slider("option", "min"));
    $("#slider-range").slider("values", 1, $("#slider-range").slider("option", "max"));

    $('.graphPanel').each(function (idx, ele) {
        if ($('.flot-base', ele).length) {
            $('a', ele).click();
        }
    });
}

function doMapFieldFilter() {
    filterMap(function (data) {
        var field = $("#mapFilterField").val();
        var val = $("#mapFilterValue").val();

        return data[field] === val;
    });

    $(".filterControl").removeClass("active");
    $("#fieldFilter").addClass("active");
}

function colourMarkersBy(fld) {
    var colourGrps = {};
    var colourIdx = 0;

    var len = mapData.length;

    for (var i = len; i--;) {
        var d = mapData[i];
        var mkr = markers[i];

        if (!d[fld]) d[fld] = "<i>No value</i>";

        if (!colourGrps[d[fld]]) {
            colourGrps[d[fld]] = colours[colourIdx++ % colours.length];
        }

        if (!mkr) continue;

        mkr.colour = colourGrps[d[fld]];
        mkr.setIcon(getMarkerIcon(mkr.colour));

    }

    window.colourField = fld;
    window.colourGrps = colourGrps;

    if (markerClusterer) markerClusterer.repaint();
    drawMapLegend();
    redrawGraph();
}

function drawMapLegend() {

    if (!map.controls[google.maps.ControlPosition.RIGHT_BOTTOM].length) {
        legend = document.createElement('div');
        legend.id = "legend";
        legend.style.backgroundColor = '#EEEEEE';
        legend.style.padding = '5px 5px 5px 5px';
        legend.style.border = '1px solid #000000';
        legend.style.maxHeight = '30%';
        legend.style.overflow = 'auto';
        var inner = document.createTextNode('Hello World');
        legend.appendChild(inner);
        map.controls[google.maps.ControlPosition.RIGHT_BOTTOM].push(legend);
    }


    var i = 0;
    for (grp in colourGrps) {
        var legRow = document.createElement('div');
        var img = document.createElement('img');
        img.style.display = 'inline';
        img.src = getMarkerIcon(colourGrps[grp]);
        legRow.appendChild(img);
        $(legRow).append(grp);
        if (i < legend.childNodes.length) {
            legend.replaceChild(legRow, legend.childNodes[i]);
        }
        else {
            legend.appendChild(legRow);
        }
        i++;
    }
    while (i < legend.childNodes.length) legend.removeChild(legend.childNodes[i]);
}

function drawBar(jq, data, field) {
    var len = data.length;
    var d = data;
    var formatedData = formatData(data, field);

    jq.empty();
    jq.attr('id', jq.parent().attr('id') + '_canvas');

    try {
        gfx.drawBarChart(jq.attr('id'), formatedData);

    } catch (err) {
        console.error(err);
    }


}


function formatData(data, field) {
    var s = [];
    var idx = 0;

    if (field === window.colourField) {
        for (var i in data) {
            s[idx++] = [i.replace(/<\/?\w>/gi, ''), data[i], window.colourGrps[i]];
        }
    }
    else {
        for (var i in data) {
            s[idx++] = [i.replace(/<\/?.*>/gi, ''), data[i]];
        }
    }

    return s
}

function drawPie(jq, data, field) {

    var len = data.length;
    var d = data;
    var formatedDate = formatData(data, field);

    jq.empty();
    jq.attr('id', jq.parent().attr('id') + '_canvas');

    gfx.drawPie(jq.attr('id'), formatedDate, (Math.min(jq.width(), jq.height()) / 2));


}

function drawGraph(div, field, type) {
    _graphdiv = div;
    _graphfield = field;
    _graphtype = type;

    var jq = $(div);

    jq.attr('graph-field', field);
    jq.attr('graph-type', type);


    var d = mapData;
    var len = d.length;

    var filter = false;

    if (mapFilterFunction) filter = mapFilterFunction;

    var agg = {};

    for (var i = len; i--;) {
        if (!filter || filter(d[i])) {
            var val = d[i][field];
            if (!agg[val]) {
                agg[val] = 1;
            } else {
                agg[val]++;
            }
        }
    }

    jq.unbind();
    if (type === "pie") {
        drawPie(jq, agg, field);
    }
    else {
        drawBar(jq, agg, field);
    }


}

function redrawGraph() {
    if (_graphdiv && _graphfield && _graphtype) drawGraph(_graphdiv, _graphfield, _graphtype)
}

function checkForMapUpdates() {
    $.ajax(location.pathname + '/__activity', {
        success: function (xhr) {
            var act = xhr;

            if (EpiCollect.parseDate(act['edited']) > map_lastupdated || EpiCollect.parseDate(act['uploaded']) > map_lastupdated) {
                var urlStr = url + "/" + formName + ".json?full_paths=false&gt::modified=" + EpiCollect.server_format(map_lastupdated) + "sort=" + project.forms[formName].titleField + "&limit=" + mapChunkSize + "&start=" + offset;

                if (filterField && filterValue) {
                    urlStr += "&" + filterField + "=" + filterValue;
                    statUrl += "&" + filterField + "=" + filterValue;
                }
                $.getJSON(urlStr, mapDataCallback);

            }
        }
    });
}

function saveNumEntires() {
    if (localStorage) localStorage.setItem('num_entries', $('#num_entries').val());
}

function editSelected() {

    if ($('.ecplus-data tbody tr.selected').length > 0) {
        project.forms[formName].displayForm({data: window.ecplus_entries[$('.ecplus-data tbody tr.selected').index()], edit: true, vertical: true});
    }
    else {
        alert("Please select an entry to edit");
    }
}

function showGPS(gps) {
    content = '<table>';
    for (var x in gps) {
        content += '<tr><th>' + x + '</th><td>' + gps[x] + '</td></tr>';
    }
    content += '</table>';
    new EpiCollect.dialog({content: content});
}





