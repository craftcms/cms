fabric.Object.prototype.setBoundingBox = function (boundingBoxWidth, boundingBoxHeight) {
	this.boundingBoxWidth = boundingBoxWidth;
	this.boundingBoxHeight = boundingBoxHeight;
}

fabric.Object.prototype.getScaledWidth = function () {
	return this.width * this.scaleX;
}

fabric.Object.prototype.getScaledHeight = function () {
	return this.height * this.scaleY;
}

fabric.Object.prototype.getScaledLeftOffset = function () {
	return (this.boundingBoxWidth - this.getScaledWidth()) / 2;
}

fabric.Object.prototype.getScaledTopOffset = function () {
	return (this.boundingBoxWidth - this.getScaledHeight()) / 2;
}

fabric.Object.prototype.getCenteredCoordinates = function () {
	return {
		left: this.getScaledLeftOffset(),
		top: this.getScaledTopOffset()
	}
}
