/**
 * REST API Tests
 */
var siteRoot = location.pathname.replace('/tests/javascript/qunit.html', '');

if(window['console'])console.debug(siteRoot);

QUnit.config.altertitle = false;

module("Basic Tests", {
		
});

asyncTest("EpiCollect+ is running and serving", function()
{
	expect(1);
	
	$.ajax({
		url : siteRoot + '/', 
		success : function(res, stat, x){
			
			div = document.createElement('div');
			$(div).html(res);
			$(div).css('display', 'none');
			$(document.body).append(div);
			
			ok($('.ecplus-projectlist h1', div).text() == 'Most popular projects on this server', 'Project List not loaded');
			start();
		},
		error : function()
		{
			ok(false, "could not load page");
		},	
		accepts : 'text/html'
	});
});

asyncTest("Can get project list", function(){
	
	expect(2);
	
	$.ajax({
		url : siteRoot + '/projects', 
		//success : function(res, stat, x){response = res; status = stat; xhr = x;},
		accepts : 'text/html',
		complete : function(x, statusText)
		{
			ok(x.status == 200);
			
			var list = JSON.parse(x.responseText);
			ok(typeof list == 'object');
			start();
		}
		
	});
});

module("per project tests", {
	
});