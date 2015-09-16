(function ($) {
    $.fn.graphPanel = function (cnf, arg) {

        if (cnf == 'expand') {
            this.clearQueue();
            $('.ecplus-settingspanel', this).animate({left: '5%'});
            this[0].expanded = true;
        }
        else if (cnf == 'collapse') {
            if (this[0].expanded) {
                this.clearQueue();
                $('.ecplus-settingspanel', this).animate({left: '95%'});
                this[0].expanded = false;
            }
        }
        else if (cnf == 'toggle') {

            if (this[0].expanded) {
                this.graphPanel('collapse');
            }
            else {
                this.graphPanel('expand');
            }
        }
        else if (cnf == 'get') {
            return $('form', this).serializeArray();
        }
        else {
            this[0].expanded = false;

            this.append('<div class="ecplus-settingspanel"></div><div class="ecplus-pane"> </div><img class="minmax" src="' + SITE_ROOT + '/images/glyphicons/glyphicons_215_resize_full.png' + '"/>');


            if (cnf.form) {
                var frm = $('.ecplus-settingspanel', this).append('<form>' + cnf.form + '</form>');
                $('.toggle', frm).buttonset();
            }

            $('.ecplus-settingspanel', this).append('<a id="bar" class="button btn btn-default" href="#">Draw Bar</a>').button();
            $('.ecplus-settingspanel', this).append('<a id="pie" class="button btn btn-default" href="#">Draw Pie</a>').button();
            //$(".ecplus-settingspanel .button", this).button();

            $('a', this).click(function (evt) {

                var id = $(this).attr('id');
                var node = evt.target.parentNode.parentNode;
                var data = $(node).graphPanel('get');
                var l = data.length;
                var field = '';
                var graphType = id;

                for (var i = l; i--;) {
                    if (data[i].name == 'field') {
                        field = data[i].value;
                    }
                    //else if (data[i].name == 'chartType')
                    ////graphType = data[i].value;
                    //    graphType = id;
                }

                console.log($(node).attr('id'));

                drawGraph('#' + $(node).attr('id') + ' .ecplus-pane', field, graphType);
                $(node).graphPanel('collapse');
            });
        }
    };
})(jQuery);
