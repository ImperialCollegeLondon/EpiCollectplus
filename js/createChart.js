self.addEventListener("message", compileGraph, false);

function compileGraph(message)
{
	self.postMessage(typeof message.data);
}