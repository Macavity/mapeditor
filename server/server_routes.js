

fs = Npm.require('fs');
/*
Router.map(function() {
    this.route('serverFile', {
        where: 'server',
        path: /^\/static\/(.*)$/,
        action: function() {
            var filePath = process.env.PWD + '/.static/' + this.params[1];
            var data = fs.readFileSync(filePath);
            this.response.writeHead(200, {
                'Content-Type': 'image'
            });
            this.response.write(data);
            this.response.end();
        }
    });
});*/
WebApp.connectHandlers.use(function(req, res, next) {
    var re = /^\/static\/(.*)$/.exec(req.url);
    if (re !== null) {   // Only handle URLs that start with /static/*
        var filePath = process.env.PWD + '/.static/' + re[1];
        var data = fs.readFileSync(filePath);
        res.writeHead(200, {
            'Content-Type': 'image'
        });
        res.write(data);
        res.end();
    } else {  // Other urls will have default behaviors
        next();
    }
});