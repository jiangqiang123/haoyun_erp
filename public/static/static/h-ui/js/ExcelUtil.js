//http://blog.csdn.net/aa122273328/article/details/50388673
/**
 * Copy and edit by hongxl on 2016/10/10.
 * 前端Excel导出，只需要将表格ID传入table2Excel(tableid)，即可实现打印。
 */

var ExcelUtils = (function () {
        var idTmr;
        /**
         * 获取浏览器类型
         * @returns {*}
         */
        function getExplorer() {
            var explorer = window.navigator.userAgent;
            //ie
            if (explorer.indexOf("MSIE") >= 0 ||
                explorer.indexOf("rv:11.0") >= 0) {//rv:11.0 IE11的标识符
                return 'ie';
            }
            //firefox
            else if (explorer.indexOf("Firefox") >= 0) {
                return 'Firefox';
            }
            //Chrome
            else if (explorer.indexOf("Chrome") >= 0) {
                return 'Chrome';
            }
            //Opera
            else if (explorer.indexOf("Opera") >= 0) {
                return 'Opera';
            }
            //Safari
            else if (explorer.indexOf("Safari") >= 0) {
                return 'Safari';
            }
        }

        function table2Excel(tableid) {
            if (getExplorer() == 'ie') {
                table2Excel4IE(tableid);
            }
            else {
                table2Excel4NotIE(tableid);
            }
        }

        /**
     * 回收内存
     * @constructor
     */
        function Cleanup() {
            window.clearInterval(idTmr);
            CollectGarbage();
        }
        /**
         * 将Table的数据转换并导出成Excel仅限非IE
         */
        var table2Excel4NotIE = (function () {
            var uri = 'data:application/vnd.ms-excel;base64,',
            //模板其实是一个网页，如果导出的表格需要样式，则在这里修改
                template =
                    '<html>' +
                    '<head>' +
                    '<meta charset="UTF-8">' +
                    '<style>th,td,table{border: 1px solid #DDDDDD;}</style>' +
                    '</head>' +
                    '<body>' +
                    '<table>{table}</table>' +
                    '</body>' +
                    '</html>',
                base64 = function (s) {
                    return window.btoa(unescape(encodeURIComponent(s)))
                },
                format = function (s, c) {
                    return s.replace(/{(\w+)}/g,
                        function (m, p) {
                            return c[p];
                        })
                };
            return function (table, name) {//name会乱码，这边先去掉
                if (!table.nodeType) {
                    table = document.getElementById(table);
                }
                var ctx = {worksheet: name || 'Worksheet', table: table.innerHTML};
                window.location.href = uri + base64(format(template, ctx));
            }
        })();

        /**
         *针对IE用的导出方法
         */
        function table2Excel4IE(tableid) {
            var curTbl = document.getElementById(tableid);
            try {
                var oXL = new ActiveXObject("Excel.Application");
                //创建AX对象excel
                var oWB = oXL.Workbooks.Add();
                //获取workbook对象
                var xlsheet = oWB.Worksheets(1);
                //激活当前sheet
                var sel = document.body.createTextRange();
                sel.moveToElementText(curTbl);
                //把表格中的内容移到TextRange中
                sel.select;
                //全选TextRange中内容
                sel.execCommand("Copy");
                //复制TextRange中内容
                xlsheet.Paste();
                //粘贴到活动的EXCEL中
                oXL.Visible = true;
                //设置excel可见属性
            } catch (e) {
                alert("该浏览器不支持导出Excel，请更换专业版或Chrome浏览器！");
                console.log("该IE浏览器安全设置问题，导致" + e + "。建议使用Chrome浏览器导出Excel。");
                return false;
            }
            try {
                var fname = oXL.Application.GetSaveAsFilename("Excel.xls", "Excel Spreadsheets (*.xls), *.xls");
            } catch (e) {
                print("Nested catch caught " + e);
            } finally {
                oWB.SaveAs(fname);
                oWB.Close(savechanges = false);
                //xls.visible = false;
                oXL.Quit();
                oXL = null;
                //结束excel进程，退出完成
                //window.setInterval("Cleanup();",1);
                idTmr = window.setInterval("Cleanup();", 1);
            }
        };
        return {"exportExcel": table2Excel}
    })();