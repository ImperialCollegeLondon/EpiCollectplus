$(function()
{
	$('#destination').sortable({
		revert : 50,
		start : function(evt, ui)
		{
			ui.placeholder.css("visibility", "");
			ui.placeholder.css("background-color", "#CCFFCC");
		},
		stop : function(evt, ui)
		{
			$("#destination .ecplus-form-element").removeClass("selected");
			$(ui.item).addClass("selected");
		}
	});
	
	$(".ecplus-form-element").draggable({
		connectToSortable: "#destination",
		helper: "clone",
		revert: "invalid",
		revertDuration : 100
	});
	
});