self.addEventListener('message', function(evt)
{
	var url = evt.data;
	
	/*if(url == 'http://www.google.co.uk')
	{
		self.postMessage(JSON.stringify({ exists : true, url :  "http://www.google.co.uk"}));
	}
	else
	{
		self.postMessage(JSON.stringify({ exists : false, url :  url }));
	}*/
	
	var xhr = new XMLHttpRequest();
	xhr.onreadystatechange = function(evt){
		if (xhr.readyState==4)
		{
			var exists = xhr.status == 200;
			self.postMessage(JSON.stringify({ exists : exists, url :  url }));
		}
	};
	
	xhr.open('GET', url, true);
	xhr.send();
}, false);