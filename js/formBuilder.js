var currentForm = undefined;
var currentControl = undefined;
var formName = undefined;
var dirty = false;

/**
 * A widget to display Errors and warnings
 * @constructor
 * @param id {String} The id of the element containing the error List
 *
 */
function ErrorList(id) {

    var html = '';

    this.errors = [];
    this.warnings = [];

    this.div = $('#' + id);

    this.div.empty();

    html += '<h5>Project Validation</h5>';
    html += '<div class="body"></div>' ;
    html += '<div class="errorList-footer">';
    html += '<span class="index"></span> of <span class="total"></span>';
    html +=  '<a class="next">next</a><a class="prev">previous</a>';
    html += '</div>';

    this.div.append(html);

    $('.next', this.div).bind('click', {ctx: this}, function (evt) {
        evt.data.ctx.next();
    });

    $('.prev', this.div).bind('click', {ctx: this}, function (evt) {
        evt.data.ctx.previous();
    });

    this.reset();
}

/**
 * Add an error to the list
 * @param error {Object} A dictionary containing the error Message, the form name and the control name
 */
ErrorList.prototype.addError = function (error) {
    this.errors.push(error);

    this.setTotal();
    this.showError(0);

};

/**
 * Add a warning to the list
 * @param warning {Object} A dictionary containing the warning Message, the form name and the control name
 */
ErrorList.prototype.addWarning = function (warning) {
    this.warnings.push(warning);

    this.setTotal();
    this.showError(0);

};

/**
 * Add several errors to the errorlist
 *
 * @param errors {Array}
 */
ErrorList.prototype.addErrors = function (errors) {
    for (var e = 0; e < errors.length; e++) {
        this.addError(errors[e]);
    }
};

/**
 * Update the total and either move to the first error or show the no error message
 */
ErrorList.prototype.setTotal = function () {
    this.total = this.errors.length + this.warnings.length;
    $('.total', this.div).text(this.total);
    $('.index', this.div).text('0');

    if (this.total > 0) {
        this.showError(0);
    }
    else {
        this.noErrors();
    }
};

/**
 * Show the error at idx. If idx is greater than the number of errors show the warning at (idx - errors.length)
 *
 * @param idx
 */
ErrorList.prototype.showError = function (idx) {
    var err = {};
    var cls = '';

    if (this.errors.length > 0 && idx < this.errors.length) {
        err = this.errors[idx];
        cls = 'error';
    }
    else if (idx < this.total) {
        if (this.errors.length === 0) {
            err = this.warnings[idx];
        }
        else {
            err = this.warnings[idx - this.errors.length];
        }

        cls = 'warning';
    }

    $('.body', this.div).html('<h4>Form : ' + err.form + ', Component : ' + err.control + '</h4><p>' + err.message + '</p>');
    $('.body', this.div).removeClass('error').removeClass('warning').addClass(cls);

    this.index = idx;
    $('.index', this.div).text(idx + 1);
};

/**
 * Display the no errors message
 */
ErrorList.prototype.noErrors = function () {
    //show message saying there are no error and a save and preview button
    $('.body', this.div).html('<h4>Project Valid</h4><p>This project has no errors.</p>');
};

/**
 * remove all errors and warnings ready for the next validation run
 */
ErrorList.prototype.reset = function () {
    this.errors = [];
    this.warnings = [];

    $('.body', this.div).empty().removeClass('warning').removeClass('error');

    this.setTotal();
};

/**
 * show the next error if there are more to show
 */
ErrorList.prototype.next = function () {
    if (this.index < (this.total - 1)) {
        this.showError(this.index + 1);
    }
};

/**
 * Show the previous error
 */
ErrorList.prototype.previous = function () {
    if (this.index > 0) {
        this.showError(this.index - 1);
    }
};
//END OF ERROR LIST

/**
 *
 * FormList : Displays the list of forms and which one is currently display
 */
function FormList(div_id) {
    this.forms = [];

    this.div = $('#' + div_id);

    this.div.empty();

    this.addNewButton();
}

/**
 * Draw the div for the form.
 * @param formName The name of the form
 */
FormList.prototype.drawForm = function (formName) {
    $('.form', this.div).removeClass('last');

    $('.add', this.div).before('<span id="' + formName + '" class="form last">' + formName + '<div class="ctls"><span class="formctl preview" title="Preview form">&nbsp;</span>' +
    '<span class="formctl rename" title="Rename form">R</span><span class="formctl delete" title="Delete Form">&nbsp;</span></div></span>');

    var fl = this;

    $('#' + formName, this.div).bind('click', function (evt) {
        fl.setSelected(this.id);
    });

    $('#' + formName + ' .preview', this.div).bind('click', function (evt) {
        previewForm($(this).parents('.form').prop('id'));
    });

    $('#' + formName + ' .delete', this.div).bind('click', function (evt) {
        removeForm($(this).parents('.form').prop('id'));
    });

    $('#' + formName + ' .rename', this.div).bind('click', function (evt) {
        renameForm($(this).parents('.form').prop('id'));
    });

};


FormList.prototype.addForm = function (formName) {
    this.forms.push(formName);
    this.drawForm(formName);
};

FormList.prototype.setSelected = function (formName) {
    $('.form', this.div).removeClass('selected');
    $('#' + formName, this.div).addClass('selected');
    switchToForm(formName);
};

FormList.prototype.setError = function (formName) {
    $('#' + formName, this.div).addClass('error');
};

FormList.prototype.setWarning = function (formName) {
    $('#' + formName, this.div).addClass('warning');
};

FormList.prototype.resetValidation = function () {
    $('.form', this.div).removeClass('warning')
        .removeClass('error');
};

/**
 * draw the "Add Form Button"
 */
FormList.prototype.addNewButton = function () {
    this.div.append('<div class="formctl add button btn btn-default" title="Add a new form"><i class="fa fa-plus fa-fw fa-2x"></i>Add a form</div>');

    $('.add', this.div).click(function (evt) {
        newForm();
    });
};
// END FORMLIST

function PropertiesForm(div_id) {
    this.settings = {
        "text": {"label": "", "id": "", "required": "", "title": "", "key": "", "searchable": "", "default": "", "regex": "", "verify": "", "jumps": ""},
        "numeric": {
            "label": "",
            "id": "",
            "required": "",
            "title": "",
            "key": "",
            "jumps": "",
            "default": "",
            "regex": "",
            "verify": "",
            "integer": true,
            "decimal": "",
            "min": "",
            "max": ""
        },
        "date": {"label": "", "id": "", "required": "", "title": "", "verify": "", "jumps": "", "date": "", "set": ""},
        "time": {"label": "", "id": "", "required": "", "title": "", "verify": "", "jumps": "", "time": "", "set": ""},
        "select1": {"label": "", "id": "", "required": "", "title": "", "options": "", "jumps": "", "default": ""},
        "radio": {"label": "", "id": "", "required": "", "title": "", "options": "", "jumps": "", "default": ""},
        "select": {"label": "", "id": "", "required": "", "title": "", "options": "", "jumps": "", "default": ""},
        "textarea": {"label": "", "id": "", "required": "", "title": "", "key": "", "jumps": "", "searchable": "", "default": "", "regex": "", "verify": ""},
        "location": {"label": "", "id": "", "required": "", "jumps": ""},
        "photo": {"label": "", "id": "", "required": "", "jumps": ""},
        "video": {"label": "", "id": "", "required": "", "jumps": ""},// Quality/format settings?
        "audio": {"label": "", "id": "", "required": "", "jumps": ""}, // Quality/format settings?
        "barcode": {"label": "", "id": "", "required": "", "title": "", "key": "", "searchable": "", "jumps": "", "default": "", "regex": "", "verify": ""},
        "branch": {"label": "", "id": "", "branch": "", "jumps": ""},
        "fk": {"fk": "", "hidden": "", "jumps": ""}
    };

    this.div = $('#' + div_id);


    $('#addOption', this.div).click({ctx: this}, function (evt) {
        evt.data.ctx.addOption();
    });

    $('#addJump', this.div).click({ctx: this}, function (evt) {
        evt.data.ctx.addJump();
    });
}

/**
 * Show the options for a field that is a key field
 */
PropertiesForm.prototype.setForKey = function () {

    //show genkey
    $('.genkey', this.div).show();

    // if we don't have a generated key
    // disable and uncheck the "hidden" field
    if (!$('#genkey').prop("checked")) {
        $('#hidden').prop('disabled', true).prop('checked', false);
    }

    // bind change event listener to "generate key" field
    $(document).on("change", '#genkey', function() {

        if (!this.checked) {
            // if we don't have a generated key
            // disable and uncheck the "hidden" field
            $('#hidden').prop('disabled', true).prop('checked', false);
        } else {
            // otherwise enable
            $('#hidden').prop('disabled', false);
        }

    });

    //disable Id
    $('.id input', this.div).prop('disabled', true);

    //check required and disable
    $('.required input, .key input').prop('checked', true).prop('disabled', true);

    $('.ecplushidden', this.div).show();
};

/**
 * Show the options for a field that is a foreign key field
 */
PropertiesForm.prototype.setForForeignKey = function () {
    //disable ID
    $('.id input', this.div).prop('disabled', true);

    //check required and disable
    $('.required input').prop('checked', true).prop('disabled', true);
};

/**
 * Show the options for a controls
 * @param ctrl {EpiCollect.Field} The control being edited
 */
PropertiesForm.prototype.setForCtrl = function (ctrl) {
    //get control type
    var _type = $('#destination #' + ctrl.id).attr('type');
    this.reset();

    //show relevant controls, hide others
    $('.ctrl', this.div).hide();

    var show_ctls = this.settings[_type];

    for (var ctl in show_ctls) {
        $('.ctrl.' + ctl).show();
        //set to default
        if (ctl == 'time') {
            var timectl = $('.time select', this.div);
            timectl.val(timectl[0].options[0].value);
        } else if (ctl == 'time') {
            var timectl = $('.date select', this.div);
            timectl.val(timectl[0].options[0].value);
        }
    }

    this.setValuesFor(ctrl);

    if (show_ctls['options']) $('.accordian', this.div).accordion('option', 'active', 0);
};

PropertiesForm.prototype.setValuesFor = function (ctrl) {
    $('.label input', this.div).val(ctrl.text);
    $('.id input', this.div).val(ctrl.id);
    $('.required input', this.div).prop('checked', ctrl.required);
    $('.title input', this.div).prop('checked', ctrl.title);
    if (ctrl.date || ctrl.setDate) $('.date select', this.div).val(ctrl.date || ctrl.setDate);
    if (ctrl.time || ctrl.setTime) $('.time select', this.div).val(ctrl.time || ctrl.setTime);
    $('.set input', this.div).prop('checked', ctrl.setDate || ctrl.setTime);
    $('.default input', this.div).val(ctrl.defaultValue);
    $('.regex input', this.div).val(ctrl.regex);
    $('.verify input', this.div).prop('checked', ctrl.verify);
    $('.genkey input', this.div).prop('checked', ctrl.genkey);
    $('.integer input', this.div).prop('checked', ctrl.isinteger);
    $('.decimal input', this.div).prop('checked', ctrl.isdouble);
    $('.ecplushidden input', this.div).prop('checked', ctrl.hidden);


    $('.min input', this.div).val(ctrl.min);
    $('.max input', this.div).val(ctrl.max);

    if (ctrl.isKey) this.setForKey();

    for (var i = 0; i < ctrl.options.length; i++) {
        this.addOption(ctrl.options[i].label, ctrl.options[i].value);
    }

    if (ctrl.jump) {
        var jump_def = ctrl.jump.split(',');
        for (var i = 0; i < jump_def.length; i += 2) {
            this.addJump(jump_def[i], jump_def[i + 1]);
        }
    }

};

PropertiesForm.prototype.reset = function () {
    $('input', this.div).prop('disabled', false);
    $('input[type=checkbox], input[type=radio]', this.div).prop('checked', false);
    $('input[type=text], input[type=number]').val('');
    $('.accordian').accordion('option', 'active', false);
    this.clearOptions();
    this.clearJumps();
};

/**
 * Hide the properties form
 */
PropertiesForm.prototype.hide = function () {
    this.reset();
    this.div.hide();
};

/**
 * Hide the properties form
 */
PropertiesForm.prototype.show = function () {
    this.setForCtrl(currentControl);
    this.div.show();
    this.setHandlers();
};

PropertiesForm.prototype.clearOptions = function () {
    $('.selectOption', this.div).remove();
};

PropertiesForm.prototype.clearJumps = function () {
    $('#jumps .jumpoption', this.div).remove();
};

PropertiesForm.prototype.setHandlers = function () {
    $('input, select', this.div).unbind('change').bind('change', function () {
        dirty = true;
    });

    $("#options .removeOption", this.div).unbind('click').bind('click', this.removeOptionHandler);
    $("#jumps .remove").unbind('click').bind('click', this.removeJumpHandler);

    $("#options input", this.div).unbind();
    $("#options input", this.div).change(function () {
        updateSelected(true);
        updateJumps();
    });

    $('#jumps .jumpType').bind('change', function (evt) {
        $('.jumpvalues', $(this).parents('.jumpoption')).toggle(!$(this).val().match(/^(NU|A)LL$/));
    });

    $('#inputId', this.div).bind('change', function (evt) {

        if (currentControl) {
            var msg = project.validateFieldName(currentForm, currentControl, $(this).val());

            if (msg !== true) {
                EpiCollect.dialog({content: msg});
                $(this).val(currentControl.id); //If the new name is not valid revert to the old one
            }
        }
    });
};

/**
 * Add an options to the options pane
 */
PropertiesForm.prototype.addOption = function (label, value) {
    if (!label) label = "";
    if (!value) value = "";

    var panel = $('div#options', this.div);
    panel.append('<div class="selectOption"><label title="The text displayed to the user">Label</label><input title="The text displayed to the user" name="optLabel" size="12" value="' + label + '" />'
    + '<label title="The value stored in the database"l>Value</label><input title="The value stored in the database" name="optValue" size="12" value="' + value + '" />'
    + '<a href="javascript:void(0);" title="Remove Option" class="button removeOption" >&nbsp;</a> </div>');

    this.setHandlers();
};

/**
 * Check the number of jumps and number of options and if there are still less jumps than options add another jump
 */
PropertiesForm.prototype.addJump = function (destination, condition) {
    var panel = $("#jumps", this.div);

    if (($('.selectOption', this.div).length + 1) <= $('.jumpoption', this.div).length) {
        EpiCollect.dialog({content: 'You cannot have more jumps than options.'});
        return;
    }

    var sta = '<div class="jumpoption"><label>When</label><select class="jumpType"><option value="">value is</option><option value="!">value is not</option><option value="NULL">field is blank</option><option value="ALL">always</option></select>';

    if (currentControl.type === 'input') {
        panel.append(sta + '<label class="jumpvalues">Value</label><input type="text" class="jumpvalues" /><br /><label>Jump to</label> <select class="jumpdestination"></select><br /><a href="javascript:void(0);" class="button remove" >&nbsp;</a></div>');
    }
    else {
        panel.append(sta + '<label class="jumpvalues">Value</label> <select class="jumpvalues"></select><br /><label>Jump to</label> <select class="jumpdestination"></select><br /><a href="javascript:void(0);" class="button remove" >&nbsp;</a></div>');
    }

    updateJumps();

    if (!currentControl.type.match(/^(select1?|radio)$/i)) {
        $('.jumpoption .jumpType').val('ALL');
        $('.jumpoption .jumpType').prop('disabled', true);
        $('.jumpoption .jumpvalues').hide();
    }

    if (condition) {
        if (condition.match(/^![0-9]+$/)) {
            $('#jumps .jumpoption:last-child .jumpType').val('!');
            $('#jumps .jumpoption:last-child .jumpvalues').val(condition.substr(1));
        }
        else if (condition.match(/^all$/i)) {
            $('#jumps .jumpoption:last-child .jumpType').val('ALL');
        }
        else if (condition.match(/^null$/i)) {
            $('#jumps .jumpoption:last-child  .jumpType').val('NULL');
        }
        else {
            $('#jumps .jumpoption:last-child .jumpType').val('');
            $('#jumps .jumpoption:last-child .jumpvalues').val(condition);
        }
    }

    $('#jumps .jumpoption:last-child .jumpdestination').val(destination);


    this.setHandlers();
    $('.jumpoption .jumpType').change();

};

/**
 * Remove option from the options pane
 *
 * @param idx {Integer} the index of the option to remove
 */
PropertiesForm.prototype.removeOptionHandler = function (evt) {
    $(this).parents('.selectOption').remove();
};

/**
 * Remove Jump from the Jumps Pane
 *
 * @param idx {Integer} the index of the jump to remove
 */
PropertiesForm.prototype.removeJumpHandler = function (evt) {
    $(this).parents('.jumpoption').remove();
};


var errorList;
var formList;
var propertiesForm;

$(function () {
    var url = location.href;

    EpiCollect.loadProject(url.substr(0, url.lastIndexOf("/")) + ".xml", drawProject);

    var details_top = $("#details").offset().top;


    $(document.body).unload(function () {
        if ($('.unsaved').length > 0) {
            localStorage.setItem(project.name + '_xml', project.toXML());
        }
    });

    $('.first').accordion({collapsible: true});
    $('.accordian').accordion({collapsible: true, active: false});

    propertiesForm = new PropertiesForm('details');
    propertiesForm.hide();

    $('#destination').sortable({
        revert: 50,
        tolerance: 'pointer',
        items: '> .ecplus-form-element',
        start: function (evt, ui) {
            ui.placeholder.css("visibility", "");
            ui.placeholder.css("background-color", "#CCFFCC");
        },
        stop: function (evt, ui) {
            if (!currentForm) {
                EpiCollect.dialog({content: "You need to choose a form in order to change the controls on it."});
                $("#destination div").remove();
            }
            else {
                var jq = $('#destination .end').remove();
                var new_jq = $(ui.item);

                if (!new_jq.prop('id')) {
                    new_jq.prop('id', genID());
                }

                dirty = true;
                setSelected(new_jq);
                updateStructure();
                updateJumps();

            }
        }
    });

    $(".ecplus-form-element").draggable({
        connectToSortable: "#destination",
        helper: 'clone',
        revert: "invalid",
        revertDuration: 100,
        appendTo: 'body',
        scroll: true
    });

    $('#destination').click(function (evt) {

        var div = evt.target;
        while (div.tagName !== "DIV") {
            div = div.parentNode;
        }

        var jq = $(div);
        if (jq.hasClass("ecplus-form-element")) {
            setSelected(jq);
        }
    });

    errorList = new ErrorList('errorList');
});

function drawProject(prj) {
    project = prj;
    var temp_xml = localStorage.getItem(project.name + '_xml');
    if (temp_xml) {
        if (confirm("There is an unsaved version of this project stored locally. Do you wish to load it?")) {
            project = new EpiCollect.Project();
            project.parse($.parseXML(temp_xml));
        }
        else {
            localStorage.removeItem(project.name + '_xml');
        }
    }

    /*$("#formList .form").remove();

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
     validateProject();
     }*/
    formList = new FormList('formList');
    for (var frm in project.forms) {
        formList.addForm(frm);
    }

    if (formList.forms.length === 0) {
        newForm('Please choose a name for your first form - this should only consist of letters, numbers and underscores.');
    }
    else {
        formList.setSelected(Object.keys(project.forms)[0]);
        validateProject();
    }
}

/**
 *
 * @param message
 * @param name
 */
function newForm(message, name, closeable) {
    if (!message) {
        message = "Enter the new form name below. Form names must contain only letters, number and underscores.";
    }

    buttons = {
        'OK': function () {
            var name = $('input', this).val();

            $(this).dialog('close');
            var valid_name = project.validateFormName(name);

            if (name !== '' && valid_name === true) {
                var frm = new EpiCollect.Form();
                frm.name = name;
                frm.num = $('.form').length + 1;
                project.forms[name] = frm;

                formList.addForm(name);

                var par = project.getPrevForm(name);

                if (par && frm.main) {
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
            else if (name) {
                newForm("<p class=\"err\">" + valid_name + "</p>", name);
            }
            else {
                newForm(message + "<p class=\"err\">The form name cannot be blank</p>", name);
            }

        }
    };

    if (closeable) {
        buttons['Cancel'] = function () {
            $(this).dialog('close');
        };
    }

    EpiCollect.prompt({
        closeable: closeable,
        buttons: buttons,
        content: "<p>" + message + "</p>"
    });

}

function addFormToList(name) {
    $("#formList .control").before("<span id=\"" + name + "\" class=\"form\">" + name + "</span>");
}

/**
 * Function to add the field representation onto the form
 *
 * @param id the id of the element
 * @param text the text for the element
 * @param type the css class of the template in the left bar
 */
function addControlToForm(id, text, type, _jq) {
    if (type.trimChars() === "") return;

    if (!type.match(/\.?ecplus-[a-z0-9]+-element/)) {
        type = '.ecplus-' + type + '-element';
    }

    if (type[0] !== ".") type = "." + type;
    var jq;
    if (!_jq)
        jq = $(type, $(".first")).clone();
    else
        jq = _jq;


    $("p.title", jq).text(text.decodeXML());
    jq.prop("id", id);

    $(".option", jq).remove();

    if (type.match(/select1?|radio/)) {
        var opts = currentForm.fields[id].options;
        var l = opts.length;
        for (var i = 0; i < l; i++) {
            jq.append("<p class=\"option\">" + opts[i].label.decodeXML() + "</p>");
        }
    }

    if (!_jq) $("#destination").append(jq);
    if (currentForm.key == id) {
        jq.addClass('key');
    }
}

function drawFormControls(form) {
    $("#destination div").remove();

    var fields = form.fields;

    for (var f in fields) {
        var fld = fields[f];
        var cls = undefined;
        //var suffix = '';

        if (fld.type === "input") {

            if (fld.isinteger || fld.isdouble) {
                cls = "ecplus-numeric-element";
            }
            else if (fld.date || fld.setDate) {
                cls = "ecplus-date-element";

            }
            else if (fld.time || fld.setTime) {
                cls = "ecplus-time-element";

            }
            else {
                cls = "ecplus-text-element";
            }

            var forms = project.forms;

            for (var fm in forms) {
                if (fm !== form.name && fld.id === forms[fm].key && fld.form.num > forms[fm].num) {
                    cls = "ecplus-fk-element";
                }
            }
        }
        else {
            cls = "ecplus-" + fld.type + "-element";
        }

        addControlToForm(fld.id, fld.text + fld.getSuffix(), cls);
    }

}

function validateProject() {
    errorList.reset();
    formList.resetValidation();
    $('.ecplus-form-element').removeClass('warning').removeClass('error');

    for (var frm in project.forms) {
        validateForm(project.forms[frm]);
    }
}

function validateCurrentForm() {
    errorList.reset();

    validateForm(currentForm);
}

function validateForm(v_form) {
    var titleFields = [];

    for (var fld in v_form.fields) {
        var jq = $('#destination #' + fld);
        var _type = jq.attr("type");

        validateControl(v_form.fields[fld], _type, function () {
        });

        if (v_form.fields[fld].title) {
            titleFields.push(fld);
        }

        if (!!v_form.fields[fld].jump) {
            var jump_arr = v_form.fields[fld].jump.split;
            //TODO: Jump Validation
        }
    }

    $('#btn_save, #btn_preview').toggle(errorList.errors.length === 0);
    $('.saveError').toggle(errorList.errors.length !== 0);

    if (titleFields.length === 0) {
        formList.setWarning(v_form.name);
        errorList.addWarning({
            form: v_form.name, control: 'form', message: "There is no title field selected, it is advisable to set a field as a title " +
            "to help users quickly distinguish between entries"
        });
    }
}

/**
 * Added in 1.5 - fire validation asynchronously and generate and error list. If the list finishes empty enable the save button
 *
 * @param ctrl
 * @param callback
 */
function validateControl(ctrl, _type, callback) {
    //validate control name
    var nameValid = project.validateFieldName(ctrl.form, ctrl);
    var messages = [];

    ctrl.fb_voter = {};

    if (!ctrl.text) {
        console.debug('label fail');
        messages.push({form: ctrl.form.name, control: ctrl.id, message: "Every field must have a label"});
    }

    if (nameValid !== true) {
        messages.push({form: ctrl.form.name, control: ctrl.id, message: nameValid});
    }

    if (_type === 'date') {
        if (!ctrl.date && !ctrl.setDate) {
            messages.push({form: ctrl.form.name, control: ctrl.id, message: "You must select a date format."});
            //throw "You must select a date format.";
            success = false;
        }

    }
    else if (_type === 'time') {
        if (!ctrl.time && !ctrl.setTime) {
            messages.push({form: ctrl.form.name, control: ctrl.id, message: "You must select a time format."});
            success = false; //throw "You must select a time format.";
        }

    }
    else if (_type === 'numeric') {
        // if( isNaN(Number(ctrl.min)) ) messages.push({ control : ctrl.id, message : "Minimum value is not a number" });
        // if( isNaN(Number(ctrl.max)) ) messages.push({ control : ctrl.id, message : "Maximum value is not a number" });

        if (ctrl.min !== '') {
            var validators = ctrl.getValidators(['key', 'fk', 'min', 'max', 'required', 'verify']);
            for (var v = 0; v < validators.length; v++) {
                var vali = validators[v];

                var res = EpiCollect.Validators[vali.name](ctrl.min, vali.params, null, ctrl.id);

                if (!res.valid) {
                    messages.push({form: ctrl.form.name, control: ctrl.id, message: '<em>Minimum</em> ' + res.messages[0]});
                }
            }
        }
        if (ctrl.max !== '') {
            var validators = ctrl.getValidators(['key', 'fk', 'max', 'min', 'required', 'verify']);
            for (var v = 0; v < validators.length; v++) {
                var vali = validators[v];

                var res = EpiCollect.Validators[vali.name](ctrl.max, vali.params, null, ctrl.id);

                if (!res.valid) {
                    messages.push({form: ctrl.form.name, control: ctrl.id, message: '<em>Maximum</em> ' + res.messages[0]});
                }
            }
        }
        if ((!!ctrl.min || ctrl.min === 0) && (!!ctrl.max || ctrl.max === 0) && Number(ctrl.min) >= Number(ctrl.max)) {
            messages.push({form: ctrl.form.name, control: ctrl.id, message: "<em>Minimum</em> must be smaller than the <em>Maximum</em>"});
        }
    }

    if (_type && _type.match(/^select1?|radio$/)) {
        if (ctrl.options.lenghth == 0) {
            messages.push({form: ctrl.form.name, control: ctrl.id, message: "Multiple choice question does not have any options"});
        }

        var optvals = [];

        for (var i = 0; i < ctrl.options.length; i++) {
            if (ctrl.options[i].label == '') messages.push({form: ctrl.form.name, control: ctrl.id, message: "Option " + i + " does not have a label"});
            if (ctrl.options[i].value == '') messages.push({form: ctrl.form.name, control: ctrl.id, message: "Option " + i + " does not have a value"});

            for (var j = 0; j < optvals.length; j++) {
                if (ctrl.options[i].value == optvals[j]) {
                    messages.push({form: ctrl.form.name, control: ctrl.id, message: "More than one option with the value " + optvals[j] + " each value must be unique."});
                }
            }
            optvals.push(ctrl.options[i].value);
        }
    }

    //Validate Options


    if (ctrl.jump && ctrl.jump !== '') {
        var jump_def = ctrl.jump.split(',');

        for (var i = 0; i < jump_def.length; i += 2) {
            var opt_len = ctrl.options.length;
            var conditional = jump_def[i + 1].replace(/!/, '');
            //check that the condition is either between 1 and the number of options inclusive, the same with an exclamation mark

            if (conditional !== 'ALL' && conditional !== 'NULL')//instant pass
            {
                var n = Number(conditional);
                if (ctrl.type == 'select') {
                    var j_valid = false;
                    for (var i = ctrl.options.length; i--;) {
                        if (ctrl.options[i].value == conditional) {
                            j_valid = true;
                            break;
                        }
                    }
                    if (!j_valid) messages.push({
                        form: ctrl.form.name,
                        control: ctrl.id,
                        message: '<em>Jump ' + (i / 2) + '</em> Jump condition is not valid, please make sure you have set the value the jump works on to a valid option'
                    });
                }
                else {
                    if (isNaN(n) || n < 1 || n > opt_len) {
                        messages.push({
                            form: ctrl.form.name,
                            control: ctrl.id,
                            message: '<em>Jump ' + (i / 2) + '</em> Jump condition is not valid, please make sure you have set the value the jump works on to a valid option'
                        });
                    }
                }

            }

            //check the destination is at least one question after the jump
            var des = jump_def[i];

            if (des !== 'END') {
                if (!des || des === 'null') {
                    messages.push({form: ctrl.form.name, control: ctrl.id, message: '<em>Jump ' + (i / 2) + '</em> Jump has no destination.'});
                }
                else {
                    var c_idx = ctrl.index;
                    var j_idx = ctrl.form.fields[des].index;

                    if (j_idx < c_idx) {
                        messages.push({
                            form: ctrl.form.name,
                            control: ctrl.id,
                            message: '<em>Jump ' + (i / 2) + '</em> Jump destination is earlier in the form then the field the jump is set on. You cannot jump backwards.'
                        });
                    }
                    else if (j_idx == (c_idx + 1)) {
                        errorList.addWarning({
                            form: ctrl.form.name,
                            control: ctrl.id,
                            message: '<em>Jump ' + (i / 2) + '</em> the jump destination is the next question, the jump is redundant'
                        });
                    }
                }
            }
        }
    }


    // All that's left is to validate the default, to do this we need to store the current message array
    // If we hit errors
    var df_val = ctrl.defaultValue;

    if (!!df_val) {
        var validators = ctrl.getValidators(['key', 'fk', 'verify', 'required']);
        for (var v = 0; v < validators.length; v++) {
            var vali = validators[v];

            var res = EpiCollect.Validators[vali.name](df_val, vali.params, null, ctrl.id);

            if (!res.valid) {
                messages.push({form: ctrl.form.name, control: ctrl.id, message: '<em>Default</em> ' + res.messages[0]});
            }
        }

    }

    validateCallback({control: ctrl, messages: messages}, ctrl.fb_voter !== {});

}

/**
 * Added in 1.5
 *
 * @param info
 */
function validateCallback(info, wait) {
    // check list length and enable/disable save button
    //var errorList = $('#errorList');
    //errorList.empty();
    //$('.' + info.control.id, errorList).remove();

    // populate list
    for (var m = 0; info.messages && m < info.messages.length; m++) {
        if (typeof info.messages[m] == 'object') {
            errorList.addError(info.messages[m]);
            formList.setError(info.messages[m].form);
            if (currentForm.name === info.messages[m].form) {
                $('#' + info.messages[m].control).addClass('error');
            }
        }
        else {
            errorList.addError(info);
        }
    }

    if (wait) return;
}

/**
 * @param is_silent
 * @returns {Boolean}
 */
function updateSelected(is_silent) {

    //if(!dirty){ return true;}

    var jq = $("#destination .selected");
    var cur = currentControl;
    var cfrm = currentForm;
    if (jq === undefined || jq.length === 0) return true;

    var name = cur.id;
    var _type = jq.attr("type");

    if (_type === 'fk') {
        //	cur.id = project.forms[$('#parent').val()].key;
    }
    else {
        cur.id = $('#inputId').val();
        cur.text = $('#inputLabel').val();
    }

    if (_type.match(/^(text|numeric|date|time|fk)$/)) {
        cur.type = "input";
        if (_type === "fk") {
            /*var f = cur.
             var frm = project.forms[f]
             cur.id = frm.key;
             cur.text = frm.fields[frm.key].text;*/
        }
    }
    else {
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

    if (_type === "time") {
        cur[(notset ? "time" : "setTime")] = $("#time").val();
    }
    if (_type === "date") {
        cur[(notset ? "date" : "setDate")] = $("#date").val();
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
    for (var i = 0; i < n; i++) {
        options[i] = {label: $("input[name=optLabel]", optCtrls[i]).val(), value: $("input[name=optValue]", optCtrls[i]).val()};
    }
    cur.options = options;

    var jump = "";
    var jumpCtrls = $(".jumpoption");
    var jn = jumpCtrls.length;

    for (var i = jn; i--;) {
        var jumpType = $('.jumpType', jumpCtrls[i]).val();
        var jval = (jumpType.length > 1 ? jumpType : (jumpType + $("select.jumpvalues", jumpCtrls[i]).val()));

        jump = $(".jumpdestination", jumpCtrls[i]).val() + "," + jval + (jump === "" ? "" : "," + jump);
    }

    cur.jump = jump.trimChars(",");

    jq.attr("id", cur.id);
    $("p.title", jq).text(cur.text + cur.getSuffix());
    $(".option", jq).remove();

    if (cur.type.match(/select1?|radio/)) {
        var opts = cur.options;
        var l = opts.length;
        for (var i = 0; i < l; i++) {
            jq.append("<p class=\"option\">" + opts[i].label + "</p>");
        }
    }
    else {
        cur.options = [];
    }

    if (name !== cur.id) {
        var newFlds = {};
        var prevFlds = cfrm.fields;

        $('#destination .ecplus-form-element').each(function (idx, ele) {
            if ($(ele).hasClass('selected')) {
                newFlds[ele.id] = cur;
            }
            else {
                newFlds[ele.id] = prevFlds[ele.id];
            }
        });

        cfrm.fields = newFlds;
    }


    if (!is_silent) {
        unselect();
    }
    else {
        //  updateEditMarker();
    }

    currentControl = cur;
    currentForm = cfrm;

    validateProject();
    return true;
}

function updateSelectedCtl() {
    updateSelected();
}

function updateForm() {
    var success = true;

    if (!currentForm) return true;
    if (!updateSelected()) success = false;
    if (!currentForm.key) {
        EpiCollect.dialog({content: "The form " + currentForm.name + " needs a key defined."});
        //throw "The form " + currentForm.name + " needs a key defined.";
        return false;
    }

    //if(dirty) $('#' + currentForm.name).addClass('unsaved');

    updateStructure();

    return success;
}

function updateStructure() {
    var fields = {};
    var form = currentForm;

    var elements = $("#destination div");
    for (var i = 0; i < elements.length && form; i++) {
        var id = elements[i].id;

        if (form.fields[id]) {
            fields[id] = form.fields[id];
            form.fields[id].index = i;
        }

        if (fields[id].isKey) form.key = id;
    }
    currentForm.fields = fields;
}

function updateJumps() {
    try {
        //updateForm();

        var opts = currentControl.options;

        var fieldCtls = $("select.jumpvalues");

        var vals = [];


        fieldCtls.each(function (idx, ele) {
            vals[idx] = $(ele).val();
        });

        fieldCtls.empty();

        var get_val = function (i) {
            return i + 1;
        };

        if (currentControl.type == 'select') {
            get_val = function (i) {
                return this.options[i].value;
            }.bind(currentControl);
        }


        for (var i = 0; i < opts.length; i++) {
            fieldCtls.append("<option value=\"" + get_val(i) + "\" >" + opts[i].label + "</option>");
        }

        $("select.jumpvalues").each(function (idx, ele) {
            $(ele).val(vals[idx]);
        });

        fieldCtls = $(".jumpdestination");

        vals = [];

        fieldCtls.each(function (idx, ele) {
            vals[idx] = $(ele).val();
        });


        fieldCtls.empty();

        var ctrls = $('#destination .ecplus-form-element');

        ctrls.each(function (idx, ele) {
            var fld = ele.id;
            var field = currentForm.fields[fld];
            var lbl = currentForm.fields[fld].text;
            if (lbl.length > 25) lbl = lbl.substr(0, 22) + "...";
            if (field.type && !field.hidden) fieldCtls.append("<option value=\"" + fld + "\">" + lbl + "</option>");
        });

        fieldCtls.append("<option value=\"END\">END OF FORM</option>");

        $(".jumpdestination").each(function (idx, ele) {
            var jq = $(ele);
            var opts = $('option', jq);
            var len = opts.length;

            var show = false;
            var cField = $('.ecplus-form-element.selected').attr('id');
            //var fidx;

            for (var i = 0; i < len; i++) {
                $(opts[i]).prop('disabled', !show);
                if (opts[i].value === cField) {
                    // hide the next + 1 element as there's no point jumping to the next question
                    if (i < len - 2) $(opts[++i]).prop('disabled', true); // but don't disable the END OF FORM option
                    show = true;
                }
            }
            if (vals.length > idx) jq.val(vals[idx]);
        });

        $('.jumpType option[value=NULL]').toggle(!$('#required').prop('checked'));

    } catch (err) {
        /*alert(err)*/
        ;
    }
}

function genID() {
    var x = $('#destination .ecplus-form-element').length;
    var name = 'ecplus_' + currentForm.name + '_ctrl' + x;
    for (; currentForm.fields[name]; x++) {
        name = 'ecplus_' + currentForm.name + '_ctrl' + x;
    }
    return name;
}

/*function updateEditMarker()
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
 }*/

function setSelected(jq) {
    var jqEle = jq;
    dirty = false;

    if (jqEle.hasClass("ecplus-form-element")) {
        if (window["currentControl"]) {
            if (!updateSelected()) return;
            // $(".last input[type=text]").val("");
            //$(".last input[type=checkbox]").prop("checked", false);
        }

        if (currentForm.fields[jqEle.prop("id")]) {
            currentControl = currentForm.fields[jqEle.prop("id")];
        }
        else {
            currentControl = new EpiCollect.Field(currentForm);
            currentControl.form = currentForm;
            currentControl.id = jqEle.prop("id");
            if (jqEle.attr('type') == 'numeric') {
                currentControl.isinteger = true;
            }

            currentForm.fields[jqEle.prop("id")] = currentControl;


        }

        $("#destination .ecplus-form-element").removeClass("selected");
        jqEle.addClass("selected");

        propertiesForm.show();

    }
    else {
        throw "div is not a form Element!";
    }
}

function previewForm(name) {
    project.forms[name].displayForm({debug: true});
}

function removeForm(name) {
    if (project.getNextForm(name)) {
        EpiCollect.dialog({content: "You can only delete the last form in the project."});
        return;
    }

    if (confirm('Are you sure you want to remove the form ' + name + '?')) {
        currentForm = false;
        $('#' + name, $("#formList")).remove();
        project.forms[name].num = -1;


        for (frm in project.forms) {
            if (!currentForm) switchToForm(frm); // switch to the first form
            break;
        }

        delete project.forms[name];

        $('.form:last').addClass('last');
    }
}

/**
 * Remove the currently selected form
 */
function removeSelected() {
    var jq = $("#destination .selected");

    if (currentControl.isKey) {
        currentForm.key = null;
        askForKey(true);
    }

    delete currentForm.fields[jq.prop("id")];
    jq.remove();
    unselect();

    //TODO : Neaten? MAybe have validate project attached to a "changed" event on the form?
    validateProject();
    if (formList.forms.length == 0) {
        newForm("You have deleted all of your forms, please choose a name for your new first form.");
    }
}


function unselect() {
    propertiesForm.reset();
    propertiesForm.hide();

    $('#destination .ecplus-form-element').removeClass("selected");
}

function renameForm(name) {
    EpiCollect.prompt({
        content: 'What would you like to rename the form ' + name + ' to?', callback: function (newName) {
            var forms = project.forms;
            var form = forms[name];
            var newForms = {};

            var valid = project.validateFormName(newName);

            if (valid === true) {
                form.name = newName;

                for (frm in forms) {
                    if (frm === name) {
                        newForms[newName] = form;
                    }
                    else {
                        newForms[frm] = forms[frm];
                    }
                }

                project.forms = newForms;
                drawProject(project);
            }
            else {
                EpiCollect.dialog({content: valid});
            }
        }
    });
}

function switchToBranch() {
    //var ctrlname = $('destination .selected').attr('id')

    updateSelected();
    unselect();

    var frm = currentControl.connectedForm;
    //var par_frm = currentForm.name;

    if (!frm || frm === '') {
        frm = currentControl.id + "_form";
        currentControl.connectedForm = frm;
    }

    if (currentForm) {
        updateForm();
        project.forms[currentForm.name] = currentForm;
    }


    if (!project.forms[frm]) {
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
    else {
        currentForm = project.forms[frm];
    }
    formName = currentForm.name;
    drawFormControls(currentForm);

    $('#source .ecplus-branch-element').hide();
    $('#source .ecplus-fk-element').hide();
}

function switchToForm(name) {

    if (currentForm) {
        updateForm();
        project.forms[currentForm.name] = currentForm;
    }

    $("#parent").empty();
    for (frm in project.forms) {
        if (frm === name) break;

        if (project.forms[frm].main) $("#parent").append("<option value=\"" + frm + "\">" + frm + " (" + project.forms[frm].key + ")</option>");
    }


    if (!project.forms[name]) project.forms[name] = new EpiCollect.Form();
    currentForm = project.forms[name];
    formName = name;

    if (!currentForm.key) {
        askForKey();
    }
    $('#source .ecplus-branch-element').show();

    if (project.getPrevForm(currentForm.name)) {
        $('#source .ecplus-fk-element').show();
    }
    else {
        $('#source .ecplus-fk-element').hide();
    }
    drawFormControls(currentForm);

}

function askForKey(keyDeleted) {
    var default_name = currentForm.name + '_key';
    var frm = currentForm;

    var possibleFields = '';

    for (var f in frm.fields) {
        var fld = frm.fields[f];
        if (fld.type == 'input' && !(fld.date || fld.setDate || fld.time || fld.setTime || fld.isKey)) {
            possibleFields += '<option value="' + fld.id + '">' + fld.text + '</option>';
        }
    }

    var form_html = '';
    form_html += '<form>';
    form_html += '<div id="key_radios" class="toggle choice">';
    form_html += '<div class="radio">';
    form_html += '<label for="key_no">';
    form_html += '<input type="radio" value="no" id="key_no" name="key" data-value="no" checked="checked">';
    form_html += '<strong>No, </strong> I do not have a unique key question for this form, please generate one for me';
    form_html += '</label>';
    form_html += '</div>';
    form_html += '<div class="radio">';
    form_html += '<label for="key_yes">';
    form_html += '<input type="radio" id="key_yes" name="key" value="yes" data-value="yes">';
    form_html += '<strong>Yes</strong> I have a unique key question for this form.';
    form_html += '</label>';
    form_html += '</div>';
    form_html += '</div>';
    form_html += '<div id="key_details" style="display:none;">';
    form_html += '<label for="key_type">My key field is a </label>';
    form_html += '<select id="key_type" name="key_type">';
    form_html += '<option value="text">Text Field</option>';
    form_html += '<option value="numeric">Integer Field</option>';
    form_html += '<option value="barcode">Barcode Field</option>';
    form_html += '</select>';
    form_html += '<p id="key_type_err" class="validation-msg"></p>';
    form_html += '<label for="key_label">Label for the key field (the question a user is asked e.g. what is your name?)</label>';
    form_html += '<input id="key_label" name="key_label" /><p id="key_label_err" class="validation-msg"></p>';
    form_html += '<label for="key_name">ID for the key field (a name used to identify the question. e.g. name)</label>';
    form_html += '<input id="key_name" name="key_name" />';
    form_html += '<p id="key_name_err" class="validation-msg"></p>';
    form_html += '</div>';
    form_html += '</form>';

    var form_html_deleted_key = '';
    form_html_deleted_key += '<form>';
    form_html_deleted_key += '<div id="key_radios" class="toggle choice">';
    form_html_deleted_key += '<label for="key_change">I want to make another<br /> field the key<br />&nbsp;</label>';
    form_html_deleted_key += '<input type="radio" id="key_change" name="key" value="change"/>';
    form_html_deleted_key += '<label for="key_yes"><b>Yes</b> I have a unique key question for this form.</label>';
    form_html_deleted_key += '<input type="radio" id="key_yes" name="key" value="yes"/>';
    form_html_deleted_key += '<label for="key_no"><b>No</b> I do not have a unique key question for this form, please generate one for me</label>';
    form_html_deleted_key += '<input type="radio" id="key_no" name="key" value="no" checked="checked"/></div>';
    form_html_deleted_key += '<div id="key_details" style="display:none;">';
    form_html_deleted_key += '<label for="key_type">My key field is a </label>';
    form_html_deleted_key += '<select id="key_type" name="key_type">';
    form_html_deleted_key += '<option value="text">Text Field</option>';
    form_html_deleted_key += '<option value="numeric">Integer Field</option>';
    form_html_deleted_key += '<option value="barcode">Barcode Field</option>';
    form_html_deleted_key += '</select>';
    form_html_deleted_key += '<p id="key_type_err" class="validation-msg"></p>';
    form_html_deleted_key += '<label for="key_label">Label for the key field (the question a user is asked e.g. what colour are your eyes?)</label>';
    form_html_deleted_key += '<input id="key_label" name="key_label" />';
    form_html_deleted_key += '<p id="key_label_err" class="validation-msg"></p>';
    form_html_deleted_key += '<label for="key_name">ID for the key field (a name used to identify the question. eg colour)</label>';
    form_html_deleted_key += '<input id="key_name" name="key_name" /><p id="key_name_err" class="validation-msg"></p>';
    form_html_deleted_key += '</div>';
    form_html_deleted_key += '<div id="select_key" style="display:none;"> Please make this field the key <select id="new_key" name="new_key">" + possibleFields + "</select></div>';
    form_html_deleted_key += '</form>';

    var add_key_message = 'Each EpiCollect+ form must have a unique \'key\' question - i.e. one where the value entered by a user will be unique each time a form is filled in.';
    add_key_message += ' <br/>Do you have a question that will be unique to each form entry?';

    var deleted_key_message = 'You have deleted the key for this form, please choose a new key field to generate.';

    EpiCollect.prompt({
        title: 'Add a key field',
        closeable: false,
        content: (!keyDeleted ? add_key_message : deleted_key_message),
        form: (!keyDeleted ? form_html : form_html_deleted_key),
        buttons: {
            OK: function () {
                $('.validation-msg').text('').removeClass('err');

                //super hack, from some reason 'value' attributes get stripped of their values...
                $('#key_radios input#key_no').val('no');
                $('#key_radios input#key_yes').val('yes');

                var raw_vals = $('form', this).serializeArray();
                var vals = {};

                for (var v = 0; v < raw_vals.length; v++) {
                    vals[raw_vals[v].name] = raw_vals[v].value;
                }

                var key_id = '';
                var new_field = new EpiCollect.Field();
                new_field.isKey = true;
                new_field.title = false;


                if (vals.key === 'yes') {
                    key_id = vals.key_name;
                    new_field.id = vals.key_name;
                    new_field.text = vals.key_label;
                    new_field.type = vals.key_type === 'barcode' ? 'barcode' : 'input';
                    new_field.form = frm;
                    new_field.isinteger = (vals.key_type === 'numeric');
                    new_field.genkey = false;

                }
                else if (vals.key === 'no') {
                    key_id = default_name;
                    new_field.id = key_id;
                    new_field.text = 'Unique ID';
                    new_field.type = 'input';
                    new_field.form = currentForm;
                    new_field.isinteger = false;
                    new_field.genkey = true;
                    vals.key_type = 'text';
                    // set key as hidden
                    new_field.hidden = true;
                }
                else {
                    key_id = vals.new_key;
                    new_field.isKey = true;
                }

                currentForm.fields[key_id] = new_field;

                var fieldNameValid = project.validateFieldName(frm, new_field, undefined, true);

                if (vals.key !== 'yes' || (fieldNameValid === true && vals.key_label !== '' && vals.key_type !== '')) {
                    if (vals.key !== 'change') addControlToForm(new_field.id, new_field.text, vals.key_type);
                    currentForm.key = key_id;
                    setSelected($('#' + key_id));
                    $(this).dialog('close');
                }
                else {
                    if (fieldNameValid !== true) $('#key_name_err').html(fieldNameValid).addClass('err');
                    if (vals.key_label === '') $('#key_label_err').html('The field must have a a label').addClass('err');
                    if (vals.key_type === '') $('#key_type_err').html('You must select a key type').addClass('err');
                }
            }
        }
    });

    $('#key_radios input[type=radio]').on('change', function () {
        $('#key_details').toggle(this.id === 'key_yes');
        $('#select_key').toggle(this.id === 'key_change');
    });


}

function saveProject() {

    if (!updateSelected()) return;
    if (!updateForm()) return;

    var loader = new EpiCollect.LoadingOverlay();
    loader.setMessage('Saving...');
    loader.start();
    window.loader = loader;

    $.ajax("./updateStructure", {
        type: "POST",
        data: {data: project.toXML(), skipdesc: true},
        success: saveProjectCallback,
        error: saveProjectError
    });

    // remove the local temporary version
    localStorage.removeItem(project.name + '_xml');
    //$('.unsaved').removeClass('unsaved');
}

function saveProjectCallback(data, status, xhr) {
    var result = JSON.parse(data);
    window.loader.stop();

    if (result.result) {

        new EpiCollect.dialog({content: "Project Saved"});
        $('.unsaved').removeClass('unsaved');
    }
    else {
        EpiCollect.dialog({content: "Project not saved : " + result.message});
    }
}

function saveProjectError(xhr, status, err) {
    EpiCollect.dialog({content: "Project not saved : " + status});
}