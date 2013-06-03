/**
 * REST API Tests
 */
var siteRoot = location.pathname.replace('/tests/javascript/qunit.html', '');

if(window['console'])console.debug(siteRoot);

QUnit.config.altertitle = false;

var prj_list = {};

module("Graphics Tests", {});
	
/**
  * Start graphics tests
  */
  
test("Get beta", function()
{
    expect(5);
    ok(gfx.getBeta(0, 1, 2, 2*Math.PI) == Math.PI);    
    ok(gfx.getBeta(0, 1, 2, Math.PI) == Math.PI/2);    
    ok(gfx.getBeta(0, 2, 2, Math.PI) == Math.PI);    
    ok(gfx.getBeta(Math.PI, 1, 2, 2*Math.PI) == Math.PI * 2);
    ok(gfx.getBeta(1, 1, 2, 2*Math.PI) == Math.PI + 1);
});
    
    
// asyncTest("EpiCollect+ is running and serving", function()
// {
	// expect(1);
	
	// $.ajax({
		// url : siteRoot + '/', 
		// success : function(res, stat, x){
			
			// div = document.createElement('div');
			// $(div).html(res);
			// $(div).css('display', 'none');
			// $(document.body).append(div);
			
			// ok($('.ecplus-projectlist h1', div).text() == 'Most popular projects on this server', 'Project List not loaded');
			// start();
		// },
		// error : function()
		// {
			// ok(false, "could not load page");
		// },	
		// accepts : 'text/html'
	// });
// });

	// asyncTest("Can get project list", function(){
		
		// expect(2);
		
		// $.ajax({
			// url : siteRoot + '/projects', 
			// //success : function(res, stat, x){response = res; status = stat; xhr = x;},
			// accepts : 'text/html',
			// complete : function(x, statusText)
			// {
				// ok(x.status == 200);
				
				// prj_list = JSON.parse(x.responseText);
				// ok(typeof prj_list == 'object');
				// start();
			// }
			
		// });
	// });


// module("per project tests", {});
	
	
// asyncTest("get project homes", function(){
	// var c = 0;
	
	// expect(prj_list.length);
	
	// for (var p = 0; p < prj_list.length; p++)
	// {
		// var p_name = prj_list[p].name; 
		// $.ajax({
			// url : siteRoot + '/' + p_name,
			// accepts : 'text/html',
			// complete : function(x, statusText)
			// {
				// ok(x.status == 200);
				// c++;
				// if(c == prj_list.length){ start(); }
			// }
		// });
	// }
// });

// asyncTest("get project xmls", function(){
	// var c = 0;
	
	// expect(prj_list.length);
	
	// for (var p = 0; p < prj_list.length; p++)
	// {
		// var p_name = prj_list[p].name; 
		// $.ajax({
			// url : siteRoot + '/' + p_name + '.xml',
			// accepts : 'text/xml',
			// complete : function(x, statusText)
			// {
				// ok(x.status == 200);
				// c++;
				// if(c == prj_list.length){ start(); }
			// }
		// });
	// }
// });

// asyncTest("get project form homes", function(){
	// var c = 0;
	// var fs = 0;
	// var fc = 0;
	
	// for (var p = 0; p < prj_list.length; p++)
	// {
		// var prj = prj_list[p];
		// var p_name = prj.name; 
		
		// for(var f in prj.forms)
		// {
			// console.debug(f);
			// fs ++;
			// $.ajax({
				// url : siteRoot + '/' + p_name + '/' + f,
				// accepts : 'text/html',
				// complete : function(x, statusText)
				// {
					// ok(x.status == 200);
					// fc++;
					// if(fc == fs){ start(); }
				// }
			// });
		// }
	// }
// });


  
