(function (win, doc) {
	var STYLE_ID = 'kdjl-font12-bold-style';
	var CLASS_NAME = 'kdjl-font12-bold';
	var FRAME_FLAG = '_kdjlFont12Bound';
	var OBSERVER_FLAG = '_kdjlFont12Observer';

	function isSkippedElement(element) {
		var tag = element.tagName ? element.tagName.toUpperCase() : '';
		return tag === 'SCRIPT' || tag === 'STYLE' || tag === 'HEAD' ||
			tag === 'TITLE' || tag === 'META' || tag === 'LINK';
	}

	function isTextControl(element) {
		var tag = element.tagName ? element.tagName.toUpperCase() : '';
		return tag === 'INPUT' || tag === 'SELECT' || tag === 'OPTION' ||
			tag === 'TEXTAREA' || tag === 'BUTTON';
	}

	function hasDirectText(element) {
		var nodes = element.childNodes;
		var i;
		for (i = 0; i < nodes.length; i++) {
			if (nodes[i].nodeType === 3 && /\S/.test(nodes[i].nodeValue || '')) {
				return true;
			}
		}
		return false;
	}

	function getFontSize(view, element) {
		var style = null;
		if (view && view.getComputedStyle) {
			style = view.getComputedStyle(element, null);
		} else if (element.currentStyle) {
			style = element.currentStyle;
		}
		return style ? style.fontSize : '';
	}

	function addBoldClass(element) {
		var className = typeof element.className === 'string' ? element.className : '';
		if ((' ' + className + ' ').indexOf(' ' + CLASS_NAME + ' ') !== -1) return;
		element.className = className ? className + ' ' + CLASS_NAME : CLASS_NAME;
	}

	function markOne(view, element) {
		if (!element || element.nodeType !== 1 || isSkippedElement(element)) return;
		if (!isTextControl(element) && !hasDirectText(element)) return;
		if (getFontSize(view, element) === '12px') addBoldClass(element);
	}

	function markTree(view, root) {
		var elements;
		var i;
		if (!root || root.nodeType !== 1) return;
		markOne(view, root);
		elements = root.getElementsByTagName('*');
		for (i = 0; i < elements.length; i++) markOne(view, elements[i]);
	}

	function installStyle(targetDoc) {
		var style;
		var head;
		if (targetDoc.getElementById(STYLE_ID)) return;
		style = targetDoc.createElement('style');
		style.id = STYLE_ID;
		style.type = 'text/css';
		if (style.styleSheet) {
			style.styleSheet.cssText = '.' + CLASS_NAME + '{font-weight:bold !important;}';
		} else {
			style.appendChild(targetDoc.createTextNode('.' + CLASS_NAME + '{font-weight:bold !important;}'));
		}
		head = targetDoc.getElementsByTagName('head')[0] || targetDoc.documentElement;
		head.appendChild(style);
	}

	function bindFrame(frame) {
		function applyFrame() {
			try {
				applyDocument(frame.contentWindow.document, frame.contentWindow);
			} catch (ignore) {}
		}
		if (!frame || frame[FRAME_FLAG]) return;
		frame[FRAME_FLAG] = true;
		if (frame.addEventListener) {
			frame.addEventListener('load', applyFrame, false);
		} else if (frame.attachEvent) {
			frame.attachEvent('onload', applyFrame);
		}
		applyFrame();
	}

	function bindFrames(targetDoc, root) {
		var frames;
		var i;
		if (root && root.tagName && root.tagName.toUpperCase() === 'IFRAME') bindFrame(root);
		frames = (root || targetDoc).getElementsByTagName('iframe');
		for (i = 0; i < frames.length; i++) bindFrame(frames[i]);
	}

	function observeDocument(targetDoc, view) {
		var Observer = view.MutationObserver || view.WebKitMutationObserver;
		var observer;
		if (!Observer || targetDoc[OBSERVER_FLAG]) return;
		observer = new Observer(function (mutations) {
			var i;
			var j;
			var node;
			for (i = 0; i < mutations.length; i++) {
				if (mutations[i].type === 'characterData') {
					markOne(view, mutations[i].target.parentNode);
					continue;
				}
				for (j = 0; j < mutations[i].addedNodes.length; j++) {
					node = mutations[i].addedNodes[j];
					if (node.nodeType === 1) {
						markTree(view, node);
						bindFrames(targetDoc, node);
					} else if (node.nodeType === 3) {
						markOne(view, node.parentNode);
					}
				}
			}
		});
		observer.observe(targetDoc.documentElement, {
			childList: true,
			subtree: true,
			characterData: true
		});
		targetDoc[OBSERVER_FLAG] = observer;
	}

	function applyDocument(targetDoc, view) {
		if (!targetDoc || !targetDoc.documentElement || !targetDoc.body) return;
		installStyle(targetDoc);
		markTree(view, targetDoc.body);
		bindFrames(targetDoc, targetDoc.body);
		observeDocument(targetDoc, view);
	}

	function start() {
		applyDocument(doc, win);
	}

	win.kdjlApplyFont12Bold = function (frame) {
		if (frame) bindFrame(frame);
		else applyDocument(doc, win);
	};

	if (doc.readyState === 'complete' || doc.readyState === 'interactive') {
		win.setTimeout(start, 0);
	} else if (doc.addEventListener) {
		doc.addEventListener('DOMContentLoaded', start, false);
	} else if (win.attachEvent) {
		win.attachEvent('onload', start);
	}
})(window, document);
