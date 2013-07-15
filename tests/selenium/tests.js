var webdriver = require('wd'), assert = require('assert');

var browser = webdriver.remote(
    "ondemand.saucelabs.com",
    80,
    "chrisipowell",
    "81eae74b-b720-48ed-b8ea-e366d0684fa0"
);

browser.on('status', function(info){
  console.log('\x1b[36m%s\x1b[0m', info);
});

browser.on('command', function(meth, path){
  console.log(' > \x1b[33m%s\x1b[0m: %s', meth, path);
});

var desired = {
  browserName: 'internet explorer'
  , version: '8.0'
  , platform: 'Windows XP'
  , tags: ["EpiCollect"]
  , name: "This is an example test"
};


browser.init(desired, function(){
    browser.get('http://test.mlst.net/epicollectplusbeta/Arduino/Sensor', function(){
	browser.elementById('tabs', function(){
	    browser.elementByClass('');
	});
    });
});