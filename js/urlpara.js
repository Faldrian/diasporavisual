var para_uri1 = window.location.search.match(/url=([^?]+)/);
var para_uri2 = window.location.hash.match(/url=([^;]+)/);
var para_uri = false;

if (para_uri1 && para_uri1.length > 1) { para_uri = para_uri1[1]; }
if (para_uri2 && para_uri2.length > 1) { para_uri = para_uri2[1]; }

if (para_uri) {
	document.getElementById("url").value = para_uri+'.json';
	startCrawl();
}
