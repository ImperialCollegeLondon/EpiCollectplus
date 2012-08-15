var URLChecker = function(){
	if(window["Worker"])
	{
		this.checker = new Worker('../js/checkurl_worker.js');
		
		var scope = this;
		document.addEventListener('__urlchecked', function(evt)
		{
			if(scope.oncheck) scope.oncheck(evt);
		});
		
		this.checker.addEventListener('message', function(e)
		{
			console.debug(e.data);
			var resp = JSON.parse(e.data);
		
			var evt = document.createEvent('Event');	
			evt.initEvent('__urlchecked', true, true);
			evt.url = resp.url;
			evt.exists = resp.exists;
			
			document.dispatchEvent(evt);
			
		}, false);
		
	
		this.startCheck = function(url)
		{
			this.checker.postMessage(url);
		};
	}
	else
	{
		this.checker = document.createElement('div');
		var ctx = this;
		
		this.startCheck = function(_url)
		{
			var url = _url;
			
			var xhr = new XMLHttpRequest();
			xhr.onreadystatechange = function(evt){
				if (xhr.readyState==4)
				{
					var exists = xhr.status == 200;
					var event = { exists : exists, url :  url };
					if(ctx.oncheck) ctx.oncheck(event);
				}
			};
			
			xhr.open('GET', url, true);
			xhr.send();
		};
	}
};