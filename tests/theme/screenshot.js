const { chromium } = require('playwright');
(async()=>{const b=await chromium.launch();const p=await b.newPage({viewport:{width:1200,height:900},deviceScaleFactor:1});
await p.goto('http://127.0.0.1:8080/',{waitUntil:'networkidle'});await p.evaluate(()=>document.fonts.ready);
await p.screenshot({path:process.argv[2],clip:{x:0,y:0,width:1200,height:900}});await b.close();})();
