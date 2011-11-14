(function($) {

blx.Transition = Base.extend({

	constructor: function(elem, targets, options) {
		this.$elem = $(elem);
		this.targets = targets;
		this.options = $.extend({}, blx.Transition.defaults, options);

		this.totalFrames = Math.floor(this.options.duration / blx.Transition.interval);
		this.currentFrame = 0;

		this.currents = {};
		for (var prop in this.targets) {
			this.currents[prop] = parseInt(this.$elem.css(prop));
		}

		if (! this.inBatch) {
			this.play();
		}
	},

	play: function() {
		blx.Transition.playTransition(this);
	},

	stop: function() {
		blx.Transition.stopTransition(this);
	},

	nextFrame: function() {
		if (this.currentFrame == this.totalFrames) {
			this.stop();
			this.options.onFinish();
			return;
		}

		var remainingFrames = this.totalFrames - this.currentFrame;

		for (var prop in this.targets) {
			var remainingDistance = this.targets[prop] - this.currents[prop];
			this.currents[prop] += (remainingDistance/remainingFrames);
		}

		this.$elem.css(this.currents);

		this.currentFrame++;
	}
},
{
	defaults: {
		inBatch: false,
		duration: 400,
		onFinish: function(){}
	},

	interval: 25,
	liveTransitions: [],
	playing: false,

	playTransition: function(transition) {
		this.liveTransitions.push(transition);
		this.play();
	},

	stopTransition: function(transition) {
		var index = this.liveTransitions.indexOf(transition);
		if (index != -1) {
			this.liveTransitions.splice(index, 1);
		}

		if (! this.liveTransitions.length) {
			this.stop();
		}
	},

	play: function() {
		if (this.playing) return;
		this.playing = true;

		this._interval = setInterval($.proxy(this, 'nextFrame'), this.interval);
	},

	stop: function() {
		if (! this.playing) return;
		this.playing = false;

		clearInterval(this._interval);
	},

	nextFrame: function() {
		for (var i in this.liveTransitions) {
			this.liveTransitions[i].nextFrame();
		}
	}

});


blx.BatchTransition = Base.extend({

	constructor: function(transitions, options) {
		this.transitions = transitions;
		this.options = $.extend({}, blx.BatchTransition.defaults, options);
		this.playing = true;

		this.remainingTransitions = this.transitions.length;

		for (var t in this.transitions) {
			this.transitions[t].options.onFinish = $.proxy(this, '_onTransitionFinish');
			this.transitions[t].play();
		}
	},

	stop: function() {
		for (var t in this.transitions) {
			this.transitions[t].stop();
			this.playing = false;
		}
	},

	_onTransitionFinish: function() {
		this.remainingTransitions--;

		if (! this.remainingTransitions) {
			this.options.onFinish();
			this.playing = false;
		}
	}

},
{
	defaults: {
		onFinish: function(){}
	}
});


})(jQuery);
