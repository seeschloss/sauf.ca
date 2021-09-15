// Render Multiple URLs to file

var RenderUrlToFile, system;

system = require("system");

/*
Render given urls
@param array of URLs to render
@param callbackPerUrl Function called after finishing each URL, including the last URL
@param callbackFinal Function called after finishing everything
*/
RenderUrlToFile = function(url, width, height, filename, filename_pdf, callback) {
    var page, retrieve, urlIndex, webpage;
    webpage = require("webpage");

    page = webpage.create();
    page.viewportSize = {
        width: width,
        height: height
    };
   // page.settings.userAgent = "Mozilla/5.0 (X11; Linux x86_64; rv:57.0) Gecko/20100101 Firefox/57.0";
    page.settings.userAgent = "Phantom.js bot";
    return page.open(url, function(status) {
        var file;
        if (status === "success") {
            return window.setTimeout((function() {
                page.render(filename);
                if (filename_pdf) {
                    page.render(filename_pdf);
                    console.log("Rendered PDF at '" + filename_pdf + "'");
                }
                page.close();
                return callback(status, url, filename);
            }), 200);
        } else {
            return callback(status, url, filename);
        }
    });
};

if (system.args.length < 3) {
    console.log("Usage: phantomjs render.js url filename [filename.pdf]");
	phantom.exit();
}

RenderUrlToFile(system.args[1], 1280, 960, system.args[2], system.args[3], (function(status, url, filename) {
    if (status !== "success") {
        console.log("Unable to render '" + url + "'");
    } else {
        console.log("Rendered '" + url + "' at '" + filename + "'");
    }

    return phantom.exit();
}));
