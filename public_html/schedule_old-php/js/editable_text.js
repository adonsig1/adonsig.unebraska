/**
 * This JS file provides an AJAX component which makes
 * specified sections of text become editable when clicked.
 *
 * Usage:
 *  1. Import the file with a <script src="..."/> tag. Also load the 
 *     associated CSS file. 
 *  2. Specify active regions of text with <a id="..." href="url">...</a>
 *  3. Register the link regions by including a call of the form
 *        EditableTextElement.registerById(id, success, failure, confirm);
 *     called in the BODY.onLoad event.
 *     The parameters are:
 *        id: The id field of the link
 *        success [optional]: A JS function called when the AJAX service 
 *          signals a successful transaction. Received an XML document as
 *          its argument.
 *        success [optional]: A JS function called when the AJAX service 
 *          signals a successful transaction. Receives an XML document as
 *          its argument.
 *        failure [optional]: A JS function called when the AJAX service 
 *          signals an unsuccessful transaction. Receives an XML document as
 *          its argument.
 *        confirm [optional]: A JS function called before the call the the
 *          AJAX service is dispatched. Must return true, otherwise the AJAX
 *          call is not made. Recives the text of the entry as its argument 
 *        url: The url of the AJAX service. This may include optional query 
 *          parameters.
 *
 * Bulk Registration:
 *  Alternatively, you can bulk-register all links of a specified class.
 *  1. Specify regions of text with <a class="..." href="url">...</a>
 *  2. Register the link regions by including a call of the form
 *        EditableTextElement.registerByClass(className, success, failure, confirm);
 *     called in the BODY.onLoad event.
 *     
 * AJAX Service:
 *  When text has been edited and changes are committed, an AJAX call is
 *  made to the URL specified in the HREF attribute of the link. The method
 *  is a POST, and the following POST data is sent:
 *    any query parameters in the HREF
 *    the text of the text region is sent as "value"
 * The component expects to receive an XML document whose document root
 * is the element <root>. The attribute success="true" must be set if the
 * transaction was successful, otherwise set success="true" . The body 
 * of <root> may contain any valid XML. The body is not parsed by default, 
 * but may be used to user-defined success and failure handlers. 
 *
 * E.g.:
 *   <?xml version="1.0" ?>
 *   <root success="true>
 *     <fn>John</fn>
 *     <ln>Orr</ln>
 *   </root>
 *
 *	TO BE DONE:
 *		1. [DONE] Capture ENTER in the input fields so the form doesn't submit
 *		2. What should happen on a bona fide form submit? Should an ajax update
 *			 take place first?
 *		3. [DONE] Styling for the input and the controls
 *		4. Should multiple fields in text mode be allowed? Or should opening
 *		   one auto-close the others?
 *    5. [DONE] BUG: If the input text is set to empty then the clickable area vanishes. 
 *
 */

function EditableTextElement(elt) {
	var me = this; // For closure
	this.elt = elt;
	this.href = elt.href;
	this.text = (elt.firstChild == null) ? "" : elt.firstChild.nodeValue;
	
	if (this.text.length == 0) {
		this.elt.innerHTML = "<i>Click to edit</i>";
	}
	elt.href = "javascript:void(0)";
	elt.onclick = function() {
		me.makeInput();
	}
}

EditableTextElement.registerById = function (id, ajaxSuccessHandler, ajaxFailureHandler, ajaxConfirm) {
	var ete = new EditableTextElement(document.getElementById(id));
	if (ajaxSuccessHandler) ete.ajaxSuccessHandler = ajaxSuccessHandler;
	if (ajaxFailureHandler) ete.ajaxFailureHandler = ajaxFailureHandler;
	if (ajaxConfirm) ete.ajaxConfirm = ajaxConfirm;
};

EditableTextElement.registerByClass = function (clazz, ajaxSuccessHandler, ajaxFailureHandler, ajaxConfirm) {
	EditableTextElement.recursivelyRegisterByClass(clazz, document.documentElement, ajaxSuccessHandler, ajaxFailureHandler, ajaxConfirm);
};

EditableTextElement.recursivelyRegisterByClass = function (clazz, elt, ajaxSuccessHandler, ajaxFailureHandler, ajaxConfirm) {
	if (elt.className) {	
		var classNames = elt.className.split(" ");
		for (var i in classNames) {
			if (classNames[i] == clazz) {
				var ete = new EditableTextElement(elt);
				if (ajaxSuccessHandler) ete.ajaxSuccessHandler = ajaxSuccessHandler;
				if (ajaxFailureHandler) ete.ajaxFailureHandler = ajaxFailureHandler;
				if (ajaxConfirm) ete.ajaxConfirm = ajaxConfirm;
				
				break;
			}
		}
	}
	for (var k in elt.childNodes) {
		EditableTextElement.recursivelyRegisterByClass(clazz, elt.childNodes[k], ajaxSuccessHandler, ajaxFailureHandler, ajaxConfirm);
	}
};

EditableTextElement.prototype.makeInput = function () {
	var me = this;
	var parent = this.elt.parentNode;
	
	var input = document.createElement("input");
	input.type="text";
	input.value = this.text;
	input.style.width = 0.5*(5*Math.floor(this.text.length/5) + 5) + "em";
	input.onkeypress = function (event) {
		var key = (window.event) ? window.event.keyCode : event.keyCode;
		if (key == 13) {
			me.ok();
			return false;
		}
		return true;
	};
	input.onclick = function(event) {
		input.select();
	}
	
	var ok = document.createElement("a");
	ok.href = "javascript: void(0)";
	ok.onclick = function () { me.ok(); }; 
	ok.innerHTML = "<img src='images/tick.png' class='editable_text'>";
	
	var cancel = document.createElement("a");
	cancel.href = "javascript: void(0)";
	cancel.onclick = function () { me.cancel(); };
	cancel.innerHTML = "<img src='images/cross.png' class='editable_text'>";
	
	var newSpan = document.createElement("span");
	newSpan.className = "editable_text";
	newSpan.appendChild(input);
	newSpan.appendChild(ok);
	newSpan.appendChild(cancel);
	
	parent.replaceChild(newSpan, this.elt);
	this.elt = newSpan;
};

EditableTextElement.prototype.ok = function () {
	this.text = this.elt.firstChild.value;
	
	if (! this.ajaxConfirm(this.text)) {
		return;
	}

	this.dispatchAjax();
	this.makeLink();
};

EditableTextElement.prototype.cancel = function () {
	this.makeLink();
};

EditableTextElement.prototype.dispatchAjax = function () {
	var me = this;
	var request = EditableTextElement.getXMLHttpRequest();
	request.open("POST", this.href);
	request.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	request.onreadystatechange = function () {
		if (request.readyState == 4) {
			if (request.status != 200) {
	  		alert("Update failed. Please try again.");
	  	} else {
	  		me.handleAjaxResponse(request.responseXML);
	  	}
		}
	};
	request.send("value=" + this.elt.firstChild.value);
};

EditableTextElement.getXMLHttpRequest = function () {
	if (typeof(XMLHttpRequest) != "undefined") {
		return new XMLHttpRequest();
	}
	
	try { return new ActiveXObject("Msxml2.XMLHTTP.6.0");	} catch (e) { }
	try { return new ActiveXObject("Msxml2.XMLHTTP.3.0");	} catch (e) { }
	try { return new ActiveXObject("Msxml2.XMLHTTP");	} catch (e) { }
	try { return new ActiveXObject("Microsoft.XMLHTTP");	} catch (e) { }
	
	alert("XMLHttpRequest is not supported");
	throw new Error("XMLHttpRequest is not supported");
};

EditableTextElement.prototype.handleAjaxResponse = function (xmlDoc) {
	var root = xmlDoc.documentElement;
	if (root.getAttribute("success") == "true") {
		this.ajaxSuccessHandler(xmlDoc);
	} else {
		this.makeInput();
		this.ajaxFailureHandler(xmlDoc);
	}
}

EditableTextElement.prototype.ajaxSuccessHandler = function (doc) {
  // Redefine this hook to provide extra functionality.
  // Read 
};

EditableTextElement.prototype.ajaxFailureHandler = function (doc) {
	alert("Update failed. Please try again.");
};

EditableTextElement.prototype.ajaxConfirm = function(value) {
	// Override this function to get a popup confirmation dialog, etc.
	return true;
};

EditableTextElement.prototype.makeLink = function () {
	var me = this;
	
	var link = document.createElement("a");
	link.href = "javascript:void(0)";
	link.onclick = function() {
		me.makeInput();
	}
	link.innerHTML = (this.text.length == 0) ? '<i>Click to edit</i>' : this.text;
	
	var parent = this.elt.parentNode;
	parent.replaceChild(link, this.elt);
	this.elt = link;
};