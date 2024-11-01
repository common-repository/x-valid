<?php
/* 
Doctype: XHTML Strict 1.0 
*/
$this->el['block'] = array("address", "blockquote", "div", "dl", "fieldset", "form", "h1", "h2", "h3", "h4", "h5", "h6", "hr", "noscript", "ol", "p", "pre", "table", "ul");
$this->el['inline'] = array("a", "abbr", "acronym", "b", "bdo", "big", "br", "button", "cite", "code", "dfn", "em", "i", "img", "input",  "kbd", "label", "map", "object", "q", "samp", "script", "select", "small", "span", "strong", "sub", "sup", "textarea", "tt", "var");
$this->el['empty'] = array("area", "br", "col", "hr", "img", "input", "param");
$this->el['siblings'] = array("a", "li", "p");
$this->el['comments'] = array("a", "abbr", "acronym", "b", "blockquote", "code", "em", "i", "strike", "strong");

// Elements that can only contain inline elements.
$this->el['p'] = array("inline");
$this->el['h1'] = $this->el['p'];
$this->el['h2'] = $this->el['p'];
$this->el['h3'] = $this->el['p'];
$this->el['h4'] = $this->el['p'];
$this->el['h5'] = $this->el['p'];
$this->el['h6'] = $this->el['p'];
$this->el['tt'] = $this->el['p'];
$this->el['big'] = $this->el['p'];
$this->el['small']= $this->el['p'];
$this->el['em'] = $this->el['p'];
$this->el['strong'] = $this->el['p'];
$this->el['dfn'] = $this->el['p'];
$this->el['code'] = $this->el['p'];
$this->el['samp'] = $this->el['p'];
$this->el['kbd'] = $this->el['p'];
$this->el['var'] = $this->el['p'];
$this->el['cite'] = $this->el['p'];
$this->el['abbr'] = $this->el['p'];
$this->el['acronym'] = $this->el['p'];
$this->el['sub'] = $this->el['p'];
$this->el['sup'] = $this->el['p'];
$this->el['q'] = $this->el['p'];
$this->el['span'] = $this->el['p'];
$this->el['bdo'] = $this->el['p'];
$this->el['dt'] = $this->el['p'];
$this->el['caption'] = $this->el['p'];
$this->el['address'] = $this->el['p'];
$this->el['legend'] = $this->el['p'];

// Elements that can only contain li tags (Lists)
$this->el['ul'] = array("li");
$this->el['ol'] = $this->el['ul'];

// Elements that can only contain tr tags (Tables)
$this->el['thead'] = array("tr");
$this->el['tbody'] = $this->el['thead'];

// Elements that can contain either block or inline tags.
$this->el['li'] = array("flow");
$this->el['dd'] = $this->el['li'];
$this->el['del'] = $this->el['li'];
$this->el['ins'] = $this->el['li'];
$this->el['script'] = $this->el['li'];
$this->el['option'] = $this->el['li'];
$this->el['textarea'] = $this->el['li'];
$this->el['div'] = $this->el['li'];
$this->el['noscript'] = $this->el['li'];
$this->el['th'] = $this->el['li'];
$this->el['td'] = $this->el['li'];

// Elements that have specific child content rules.
$this->el['i'] = array("-i", "inline");
$this->el['b'] = array("-b", "inline");
$this->el['a'] = array("-a", "inline");
$this->el['object'] = array("flow", "param");
$this->el['map'] = array("area", "block");
$this->el['select'] = array("optgroup", "option");#
$this->el['optgroup'] = array("option");
$this->el['label'] = array("-label", "flow");
$this->el['button'] = array("-a", "-button", "-fieldset", "-form", "-input", "-label", "-select", "-textarea", "flow");
$this->el['dl'] = array("dd", "dt");
$this->el['pre'] = array("-big", "-img", "-object", "-small", "-sub", "-sup", "inline");
$this->el['blockquote'] = array("block", "script");
$this->el['form'] = array("-form", "block", "script");
$this->el['table'] = array("caption", "col", "colgroup", "tbody", "thead", "tr");
$this->el['colgroup'] = array("col");
$this->el['tr'] = array("td", "th");
$this->el['fieldset'] = array("flow", "inline", "legend");	

$this->el['style'] = array();

// Attributes blocks.
$this->att['core'] = array("class", "id", "style", "title");
$this->att['lang'] = array("dir", "lang", "xml:lang");
$this->att['keyb'] = array("accesskey", "tabindex");
$this->att['window'] = array("onload", "onunload");
$this->att['formevts'] = array("onchange", "onsubmit", "onreset", "onselect", "onblur", "onfocus");
$this->att['events'] = array("onkeydown", "onkeypress", "onkeyup", "onclick", "ondblclick", "onmousedown", "onmouseover", "onmousemove", "onmouseout", "onmouseup");

// Elements that contain a standard set of attributes only.
$this->att['abbr'] = array("core", "lang", "events");
$this->att['acronym'] = $this->att['abbr'];
$this->att['address'] = $this->att['abbr'];		
$this->att['b'] = $this->att['abbr'];
$this->att['big'] = $this->att['abbr'];
$this->att['blockquote'] = $this->att['abbr'];
$this->att['caption'] = $this->att['abbr'];
$this->att['cite'] = $this->att['abbr'];
$this->att['code'] = $this->att['abbr'];
$this->att['dd'] = $this->att['abbr'];
$this->att['dfn'] = $this->att['abbr'];
$this->att['dir'] = $this->att['abbr'];
$this->att['dl'] = $this->att['abbr'];
$this->att['dt'] = $this->att['abbr'];
$this->att['em'] = $this->att['abbr'];
$this->att['fieldset'] = $this->att['abbr'];
$this->att['h1'] = $this->att['abbr'];
$this->att['h2'] = $this->att['abbr'];
$this->att['h3'] = $this->att['abbr'];
$this->att['h4'] = $this->att['abbr'];
$this->att['h5'] = $this->att['abbr'];
$this->att['h6'] = $this->att['abbr'];
$this->att['hr'] = $this->att['abbr'];
$this->att['i'] = $this->att['abbr'];
$this->att['kbd'] = $this->att['abbr'];
$this->att['li'] = $this->att['abbr'];
$this->att['map'] = $this->att['abbr'];
$this->att['noscript'] = $this->att['abbr'];
$this->att['ol'] = $this->att['abbr'];
$this->att['p'] = $this->att['abbr'];
$this->att['pre'] = $this->att['abbr'];
$this->att['samp'] = $this->att['abbr'];
$this->att['small'] = $this->att['abbr'];
$this->att['span'] = $this->att['abbr'];
$this->att['strong'] = $this->att['abbr'];
$this->att['sub'] = $this->att['abbr'];
$this->att['sup'] = $this->att['abbr'];
$this->att['tt'] = $this->att['abbr'];
$this->att['ul'] = $this->att['abbr'];
$this->att['var'] = $this->att['abbr'];

// Elements with specific attribute requirements.
$this->att['a'] = array("standard", "events", "charset", "coords", "href", "hreflang", "name", "rel", "rev", "shape", "*target", "type");
$this->att['area'] = array("standard", "events", "alt", "coords", "href", "nohref", "shape", "*target");
$this->att['button'] = array("standard", "events", "@disabled", "name", "type", "value");
$this->att['div'] = array("core", "lang", "events", "align");
$this->att['input'] = array("standard", "events", "formevts", "accept", "alt", "@checked", "@disabled", "@ismap", "maxlength", "name", "@readonly", "size", "src", "type", "usemap", "value");
$this->att['label'] = array("standard", "events", "onblur", "onfocus");
$this->att['legend'] = array("standard", "events");
$this->att['bdo'] = array("core", "lang");
$this->att['br'] = array("core");
$this->att['col'] = array("core", "lang", "events", "align", "char", "charoff", "span", "valign", "width");
$this->att['del'] = array("core", "lang", "events", "cite");
$this->att['form'] = array("core", "lang", "events", "accept-charset", "accept", "+action", "enctype", "method", "name", "onreset", "onsubmit", "*target");
$this->att['img'] = array("core", "lang", "events", "+alt", "height", "@ismap", "longdesc", "name", "+src", "usemap", "width");
$this->att['ins'] = array("core", "lang", "cite", "datetime");
$this->att['object'] = array("core", "lang", "events", "archive", "classid", "codebase", "codetype", "data", "@declare", "height", "name", "standby", "tabindex", "type", "usemap", "width");
$this->att['optgroup'] = array("core", "lang", "events", "@disabled", "+label");
$this->att['option'] = array("core", "lang", "events", "@disabled", "label", "@selected", "value");
$this->att['cite'] = array("core", "lang", "events", "q");
$this->att['select'] = array("core", "lang", "events", "@disabled", "@multiple", "name", "onblur", "onchange", "onfocus", "size", "tabindex");
$this->att['table'] = array("core", "lang", "events", "border", "cellpadding", "cellspacing", "frame", "rules", "summary", "width");
$this->att['tbody'] = array("core", "lang", "events", "align", "char", "charoff", "valign");
$this->att['td'] = array("core", "lang", "events", "abbr", "align", "axis", "char", "charoff", "headers", "rowspan", "scope", "valign");
$this->att['th'] = array("core", "lang", "events", "abbr", "align", "axis", "char", "charoff", "colspan", "headers", "rowspan", "scope", "valign");
$this->att['param'] = array("id", "+name", "type", "value", "valuetype");
$this->att['script'] = array("charset", "@defer", "src", "+type");
$this->att['script'] = array("media", "lang", "+type");
$this->att['textarea'] = array("standard", "events", "+cols", "@disabled", "name", "onblur", "onchange", "onfocus", "onselect", "@readonly", "+rows");
$this->att['thead'] = array("align", "char", "charoff", "events", "lang", "valign");
$this->att['colgroup'] = $this->att['col'];
$this->att['tr'] = $this->att['tbody'];

$this->commatt['a'] = array("href", "title", "rel");
$this->commatt['abbr'] = array("title");
$this->commatt['acronym'] = $this->commatt['abbr'];
$this->commatt['blockquote'] = array("cite");
?>