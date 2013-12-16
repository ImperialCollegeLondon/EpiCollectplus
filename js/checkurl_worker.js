self.addEventListener('message', function(evt)
{
	var url = evt.data;
	
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