"use strict";
var XFThread = /** @class */ (function () {
    function XFThread(threadId, replyCount, url) {
        this.threadId = threadId;
        this.replyCount = replyCount;
        this.url = url;
    }
    return XFThread;
}());
///<reference path="models/XFThread.ts"/>
var XF2Connector = /** @class */ (function () {
    function XF2Connector(xfBaseUrl) {
        this.threadIds = [];
        this.xfThreadIdToElmMap = {};
        this.xfBaseUrl = '';
        this.xfThreads = {};
        this.xfBaseUrl = xfBaseUrl;
    }
    XF2Connector.prototype.setActiveThreads = function () {
        this.replyCountElms = document.querySelectorAll('aukn-thread-replies');
        if (this.replyCountElms.length > 0) {
            var i = 0;
            while (this.replyCountElms[i]) {
                var replyCountElm = this.replyCountElms[i];
                var threadId = parseInt(replyCountElm.getAttribute('data-thread-id'));
                var threadIdIndexed = false;
                for (var _i = 0, _a = this.threadIds; _i < _a.length; _i++) {
                    var cachedThreadId = _a[_i];
                    if (threadId === cachedThreadId) {
                        threadIdIndexed = true;
                        break;
                    }
                }
                if (!threadIdIndexed) {
                    this.threadIds.push(threadId);
                    this.xfThreadIdToElmMap[threadId] = [];
                }
                this.xfThreadIdToElmMap[threadId].push(replyCountElm);
                i++;
            }
        }
    };
    XF2Connector.prototype.fetchThreads = function () {
        var _this = this;
        if (this.threadIds.length > 0) {
            var threads = this.threadIds.join(',');
            var url = this.xfBaseUrl + '/index.php?api/threads/' + threads;
            var xhr_1 = new XMLHttpRequest();
            xhr_1.onreadystatechange = function () {
                if (xhr_1.readyState === XMLHttpRequest.DONE) {
                    if (xhr_1.status === 200) {
                        var response_1 = JSON.parse(xhr_1.responseText);
                        Object.keys(response_1.threads).forEach(function (threadId) {
                            _this.xfThreads[parseInt(threadId)] = new XFThread(parseInt(threadId), response_1.threads[threadId].replyCount, response_1.threads[threadId].url);
                        });
                        _this.displayThreads();
                    }
                }
            };
            xhr_1.open('GET', url, true);
            xhr_1.send();
        }
    };
    XF2Connector.prototype.displayThreads = function () {
        var _this = this;
        Object.keys(this.xfThreads).forEach(function (threadId) {
            var model = _this.xfThreads[parseInt(threadId)];
            for (var _i = 0, _a = _this.xfThreadIdToElmMap[parseInt(threadId)]; _i < _a.length; _i++) {
                var threadElm = _a[_i];
                var linkElm = document.createElement('a'), spanElm = document.createElement('span'), xfDefaultLinkLabel = '&bull; XF-COMMENT-TEXT', customLinkLabel = threadElm.getAttribute('data-link-label'), linkLabel = (customLinkLabel) ? customLinkLabel : xfDefaultLinkLabel;
                linkElm.href = model.url;
                linkElm.target = '_blank';
                var commentsLabel = (model.replyCount === 1)
                    ? model.replyCount + ' comment'
                    : model.replyCount + ' comments';
                linkLabel = linkLabel.replace('XF-COMMENT-TEXT', commentsLabel);
                linkElm.innerHTML = linkLabel;
                spanElm.appendChild(linkElm);
                threadElm.appendChild(spanElm);
                threadElm.className = 'loaded';
            }
        });
    };
    XF2Connector.prototype.init = function () {
        this.setActiveThreads();
        this.fetchThreads();
    };
    XF2Connector.load = function (xfBaseUrl) {
        var xf2 = new XF2Connector(xfBaseUrl);
        xf2.init();
    };
    return XF2Connector;
}());
