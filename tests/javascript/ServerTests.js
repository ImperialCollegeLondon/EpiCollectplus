var siteRoot = '/epicollectplus';

describe("EpiCollect+ Web page load tests", function(){
	var response, status, xhr, div;
	it("Loads the index page", function(){
		response = false;
		
		runs(function(){
			$.ajax({
				url : siteRoot + '/', 
				success : function(res, stat, x){response = res; status = stat; xhr = x;},
				accepts : 'text/html'
			})
		});
		
		waitsFor(function(){
			return !!response;
		},'index page should be returned', 5000);
		
		runs(function(){
			div = document.createElement('div');
			$(div).html(response);
			$(div).css('display', 'none');
			$(document.body).append(div);
			expect(xhr.status).toBe(200);

			expect($('.ecplus-projectlist h1', div).text()).toBe('Most popular projects on this server');
		});
	});
	
	it("Open login page", function(){
		response = false;
		
		runs(function(){
			$.ajax({
				url : siteRoot + '/login.php?provider=None', 
				success : function(res, stat, x){response = res; status = stat; xhr = x;},
				accepts : 'text/html'
			})
		});
		
		waitsFor(function(){
			return !!response;
		},'index page should be returned', 5000);
		
		runs(function(){
			div = document.createElement('div');
			$(div).html(response);
			$(div).css('display', 'none');
			$(document.body).append(div);
			expect(xhr.status).toBe(200);
		});
	});
	
	
	it("Open Local login page", function(){
		response = false;
		
		runs(function(){
			$.ajax({
				url : siteRoot + '/login.php?provider=LOCAL', 
				success : function(res, stat, x){response = res; status = stat; xhr = x;},
				accepts : 'text/html'
			})
		});
		
		waitsFor(function(){
			return !!response;
		},'index page should be returned', 5000);
		
		runs(function(){
			$(div).html(response);
			$(div).css('display', 'none');
			$(document.body).append(div);
			expect(xhr.status).toBe(200);
			expect($('input[type!=hidden]', div).length).toBe(3);
		});
	});
	
//	it("Open Open ID login page", function(){
//		response = false;
//		
//		runs(function(){
//			$.ajax({
//				url : siteRoot + '/login.php?provider=OPENID', 
//				//success : function(res, stat, x){response = res; status = stat; xhr = x;},
//				accepts : 'text/html',
//				complete : function(x, statusText)
//				{
//					response = x.responseText;
//					status = statusText
//					xhr = x;
//				}
//			})
//		});
//		
//		waitsFor(function(){
//			return !!response;
//		},'OpenID page should be returned', 10000);
//		
//		runs(function(){
//			$(div).html(response);
//			$(div).css('display', 'none');
//			$(document.body).append(div);
//			expect(xhr.status).toBe(200);
//		});
//	});
});

describe("Generic EpiCollect+ API Tests", function(){
	it("Get Project List", function(){
		response = false;
		status = false
		runs(function(){
			$.ajax({
				url : siteRoot + '/projects', 
				//success : function(res, stat, x){response = res; status = stat; xhr = x;},
				accepts : 'text/html',
				complete : function(x, statusText)
				{
					response = x.responseText;
					status = statusText
					xhr = x;
				}
				
			})
		});
		
		waitsFor(function(){
			return !!response;
		},'Project list should be returned', 5000);
		
		runs(function(){
			expect(xhr.status).toBe(200);	
			var data;
			expect(function(){data = JSON.parse(response)}).not.toThrow();
			expect(typeof data.length).toBe(typeof 1);
		});
	});
	
});

describe("Project-Specific EpiCollect+ API Tests", function(){
		var projectList = [];
		var firstProject = '';
		var response = false;
		var status = false;
		
		beforeEach(function(){	
			runs(function(){
				$.ajax({
					url : siteRoot + '/projects', 
					//success : function(res, stat, x){response = res; status = stat; xhr = x;},
					accepts : 'text/html',
					complete : function(x, statusText)
					{
						response = x.responseText;
						status = statusText
						xhr = x;
					}
					
				})
			});
			
			waitsFor(function(){
				return !!response;
			},'Project list should be returned', 5000);
			
			runs(function(){
				var data;
				projectList = JSON.parse(response);
			});
		});

	
		it("Get Project XML", function(){
			response = false;
			status = false;
			
			runs(function(){
				
				$.ajax({
					url : siteRoot + '/' + projectList[0].name + '.xml', 
					accepts : 'text/xml',
					complete : function(x, statusText)
					{
						response = x.responseText;
						status = statusText
						xhr = x;
					}
					
				})
			});
			
			waitsFor(function(){
				return !!response;
			},' getting project xml ', 10000);
			
			runs(function(){
				expect(xhr.status).toBe(200);
				expect(typeof xhr.responseXML).not.toBe(undefined);
				
			});
		});
		
		it("Get Project XML", function(){
			response = false;
			status = false;
			
			runs(function(){
				
				$.ajax({
					url : siteRoot + '/' + projectList[0].name + '	', 
					accepts : 'text/xml',
					complete : function(x, statusText)
					{
						response = x.responseText;
						status = statusText
						xhr = x;
					}
					
				})
			});
			
			waitsFor(function(){
				return !!response;
			},' getting project xml ', 10000);
			
			runs(function(){
				expect(xhr.status).toBe(200);
				expect(typeof xhr.responseXML).not.toBe(undefined);
				
			});
		});

});
