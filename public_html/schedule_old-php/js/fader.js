function Color(r, g, b) {
	this.red = r;
	this.green = g;
	this.blue = b;
}

Color.RED = new Color(255, 0, 0);
Color.GREEN = new Color(0, 255, 0);
Color.BLUE = new Color(0, 0, 255);
Color.BLACK = new Color(0, 0, 0);
Color.WHITE = new Color(255, 255, 255);

/*
 * Construct a fader object with the target element (the actual element, not the id),
 * and the initial color and final color. Use the Color object defined in this package
 * to represent the colors. When theFader.start() is called, the fader wil transition.
 * The fade takes numSteps steps, each of delay ms.
 */
function Fader(elt, initialColor, finalColor) {
	this.element = elt;
	this.initialColor = initialColor;
	this.finalColor = finalColor;
	this.delay = 100;
  this.step = 0;
  this.numSteps = 15;
};

Fader.prototype.start = function () {
	// Note use of Javascript closures to pass a copy of 'this' into the
	// function called by setInterval. (SetInterval runs in the window scope,
	// so a literal "this" would point to window.)
	var self = this;
	var intervalID = setInterval(function () { self.stepFade(intervalID); }, this.delay);
};

Fader.prototype.doLast = function () {
	// Redefine to provide functionality
};

Fader.prototype.stepFade = function (intervalID) {
	if (this.step > this.numSteps) {
		// alert("Stop: " + intervalID);
		clearInterval(intervalID);
		this.doLast();
		return;
	}
	var k = this.step;
	var n = this.numSteps;
	
	var r = this.interpolate(k, n, this.initialColor.red, this.finalColor.red);
	var g = this.interpolate(k, n, this.initialColor.green, this.finalColor.green);
	var b = this.interpolate(k, n, this.initialColor.blue, this.finalColor.blue);
	
	this.element.style.backgroundColor = this.getRGBString(r,g,b);
	
	++this.step;
}
			
Fader.prototype.interpolate = function (k, n, c1, c2) {
	return c1 + k*(c2 - c1)/n;
};

Fader.prototype.getRGBString = function (r,g,b) {
	return "rgb(" + parseInt(r) + "," + parseInt(g) + "," + parseInt(b) + ")";
};