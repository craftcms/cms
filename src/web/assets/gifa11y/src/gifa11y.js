/*
 * Gifa11y
 * @author: Adam Chaboryk
 * @version: 1.0.7
 * @license: MIT
 */
class Gifa11y {
  'use strict';
  constructor(options) {
    let defaultConfig = {
      buttonBackground: 'indigo',
      buttonBackgroundHover: 'rebeccapurple',
      buttonIconColor: 'white',
      buttonFocusColor: '#00e7ffad',
      buttonIconSize: '1.5rem',
      buttonIconFontSize: '1rem',
      buttonPlayIconID: '',
      buttonPauseIconID: '',
      buttonPlayIconHTML: '',
      buttonPauseIconHTML: '',
      container: 'body',
      exclusions: '',
      gifa11yOff: '',
      inheritClasses: true,
      initiallyPaused: false,
      langPause: 'Pause animation:',
      langPlay: 'Play animation:',
      langPauseAllButton: 'Pause all animations',
      langPlayAllButton: 'Play all animations',
      langMissingAlt: 'Missing image description.',
      langAltWarning: '&#9888; Error! Please add alternative text to GIF.',
      missingAltWarning: true,
    };
    options = {...defaultConfig, ...options};
    let $gifs = [];

    this.initialize = () => {
      this.exclusions();
      const gifa11yOff = document.querySelectorAll(options.gifa11yOff);
      if (gifa11yOff.length === 0) {
        this.generateCSS();
        document.addEventListener(
          'DOMContentLoaded',
          (e) => {
            this.findGifs();
            this.generateStill();
            this.prepareButtons();
            this.toggleEverything();
          },
          false
        );
      }
    };

    this.exclusions = () => {
      const separator = ', ';
      //Don't run if page contains element.
      if (options.gifa11yOff.length > 0) {
        let offSelectors = options.gifa11yOff.split(',');
        options.gifa11yOff = '.gifa11y-off' + separator + offSelectors.join();
      } else {
        options.gifa11yOff = '.gifa11y-off';
      }

      //Exclusions.
      if (options.exclusions.length > 0) {
        let containerSelectors = options.exclusions.split(',');
        for (let i = 0; i < containerSelectors.length; i++) {
          containerSelectors[i] =
            containerSelectors[i] + ' *, ' + containerSelectors[i];
        }
        options.exclusions =
          '.gifa11y-ignore' + separator + containerSelectors.join();
      } else {
        options.exclusions = '.gifa11y-ignore';
      }
    };
    this.findGifs = () => {
      //Find GIFs within specified container.
      const maincontainer = document.querySelector(options.container),
        allGifs = maincontainer
          ? Array.from(
              maincontainer.querySelectorAll(
                'img[src$=".gif"]:not([src*="gifa11y-ignore"])'
              )
            )
          : [],
        excludeGifs = maincontainer
          ? Array.from(maincontainer.querySelectorAll(options.exclusions))
          : [],
        filteredGifs = allGifs.filter(($el) => !excludeGifs.includes($el));

      filteredGifs.forEach(($gif, index) => {
        $gifs[index] = $gif;
      });
    };

    this.generateStill = () => {
      //Timing is important. Wait for each image to load before generating a still.
      $gifs.forEach(($el) => {
        if ($el.complete) {
          waitForImage($el);
        } else {
          $el.addEventListener('load', () => {
            waitForImage($el);
          });
        }
      });
      function waitForImage($el) {
        let ext;
        ext = $el.src.split('.');
        ext = ext[ext.length - 1].toLowerCase();
        ext = ext.substring(0, 4);
        if (ext === 'gif') {
          const canvas = document.createElement('canvas');

          //Calculate total border width... otherwise layout shifts.
          let borderLeft = parseFloat(
              getComputedStyle($el, null).getPropertyValue('border-left-width')
            ),
            borderRight = parseFloat(
              getComputedStyle($el, null).getPropertyValue('border-right-width')
            ),
            totalBorderWidth = borderLeft + borderRight,
            gifWidth = $el.getAttribute('width');

          //If width wasn't manually specified on GIF.
          if (gifWidth !== null) {
            canvas.width = gifWidth;

            //Prevent layout shifts when width is manually specified on image.
            canvas.setAttribute(
              'style',
              'width:' + gifWidth + 'px !important;'
            );
          } else {
            //If rendered or clientWidth of image is 0, use naturalWidth as fallback.
            if ($el.clientWidth == 0) {
              canvas.width = $el.naturalWidth + 0.5 + totalBorderWidth;
            } else {
              //Why 0.5? Apparently canvas calculates from half a pixel... otherwise layout shifts. Thanks to: https://stackoverflow.com/a/13879402
              canvas.width = $el.clientWidth + 0.5 + totalBorderWidth;
            }
          }

          // Calculate gif height keeping aspect ratio.
          const newHeight =
            ($el.naturalHeight / $el.naturalWidth) * canvas.width;
          canvas.height = newHeight + 0.5;

          canvas.setAttribute('role', 'img');

          //Grab all classes from the original image.
          if (options.inheritClasses === true) {
            let cssClasses = $el.getAttribute('class');
            if (cssClasses == null) {
              cssClasses = '';
            }
            canvas.setAttribute('class', 'gifa11y-canvas' + ' ' + cssClasses);
          } else {
            canvas.setAttribute('class', 'gifa11y-canvas');
          }

          //Set alt on canvas.
          let alt = $el.getAttribute('alt');
          if (alt == null || alt == '' || alt == ' ') {
            alt = options.langMissingAlt;
          }
          canvas.setAttribute('aria-label', alt);

          const filename = $el.src,
            mediaQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
          //If content author wants GIF to be paused initially (or prefers reduced motion).
          if (
            !mediaQuery ||
            mediaQuery.matches ||
            $el.classList.contains('gifa11y-paused') ||
            filename.indexOf('gifa11y-paused') > -1 ||
            options.initiallyPaused === true
          ) {
            $el.style.display = 'none';
            $el.setAttribute('data-gifa11y-state', 'paused');
          } else {
            canvas.style.display = 'none';
            $el.setAttribute('data-gifa11y-state', 'playing');
          }

          //Generate canvas and insert after GIF.
          const canvasContext = canvas.getContext('2d');
          canvasContext.drawImage($el, 0, 0, canvas.width, canvas.height);
          $el.after(canvas);
        }
      }
    };

    this.prepareButtons = () => {
      //Timing is also important here. Load buttons after image fully loads. Otherwise if user clicks button while it's still loading, the canvas still can't be generated.
      $gifs.forEach(($el) => {
        if ($el.complete) {
          waitForImage($el);
        } else {
          $el.addEventListener('load', () => {
            waitForImage($el);
          });
        }
      });
      function waitForImage($el) {
        const mediaQuery = window.matchMedia(
            '(prefers-reduced-motion: reduce)'
          ),
          findCanvas = $el.nextElementSibling;

        let initialState,
          currentState,
          pauseDisplay,
          playDisplay,
          filename = $el.src;
        if (
          !mediaQuery ||
          mediaQuery.matches ||
          $el.classList.contains('gifa11y-paused') ||
          filename.indexOf('gifa11y-paused') > -1 ||
          options.initiallyPaused === true
        ) {
          initialState = options.langPlay;
          playDisplay = 'block';
          pauseDisplay = 'none';
          currentState = 'paused';
        } else {
          initialState = options.langPause;
          playDisplay = 'none';
          pauseDisplay = 'block';
          currentState = 'playing';
        }

        //If alt is missing, indicate as such on button label and canvas element.
        let alt = $el.getAttribute('alt');
        if (alt == null || alt == '' || alt == ' ') {
          alt = options.langMissingAlt;

          if (options.missingAltWarning === true) {
            //And also give them a friendly reminder to add alt text.
            const warning = document.createElement('span');
            warning.classList.add('gifa11y-warning');
            warning.innerHTML = `${options.langAltWarning}`;
            findCanvas.after(warning);
          }
        }

        //Create button
        const pauseButton = document.createElement('button'),
          defaultPlayIcon = `<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="m11.596 8.697-6.363 3.692c-.54.313-1.233-.066-1.233-.697V4.308c0-.63.692-1.01 1.233-.696l6.363 3.692a.802.802 0 0 1 0 1.393z"/></svg>`,
          defaultPauseIcon = `<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M5.5 3.5A1.5 1.5 0 0 1 7 5v6a1.5 1.5 0 0 1-3 0V5a1.5 1.5 0 0 1 1.5-1.5zm5 0A1.5 1.5 0 0 1 12 5v6a1.5 1.5 0 0 1-3 0V5a1.5 1.5 0 0 1 1.5-1.5z"/></svg>`;

        pauseButton.classList.add('gifa11y-btn');
        pauseButton.setAttribute('aria-label', initialState + ' ' + alt);
        pauseButton.setAttribute('data-gifa11y-state', currentState);
        pauseButton.setAttribute('data-gifa11y-alt', alt);
        pauseButton.innerHTML = `<div class="gifa11y-pause-icon" aria-hidden="true" style="display: ${pauseDisplay}"></div><div class="gifa11y-play-icon" aria-hidden="true" style="display: ${playDisplay}"></div>`;
        const pauseIcon = pauseButton.querySelector('.gifa11y-pause-icon'),
          playIcon = pauseButton.querySelector('.gifa11y-play-icon');

        //Pause icon.
        if (options.buttonPauseIconID.length > 1) {
          //If icon is supplied via ID on page.
          const customPauseIcon = document.getElementById(
            options.buttonPauseIconID
          ).innerHTML;
          pauseIcon.innerHTML = customPauseIcon;
        } else if (options.buttonPauseIconHTML.length > 1) {
          //If icon is supplied via icon font or HTML.
          pauseIcon.innerHTML = options.buttonPauseIconHTML;
        } else {
          pauseIcon.innerHTML = defaultPauseIcon;
        }

        //Play icon.
        if (options.buttonPlayIconID.length > 1) {
          //If icon is supplied via ID on page.
          const customPlayIcon = document.getElementById(
            options.buttonPlayIconID
          ).innerHTML;
          playIcon.innerHTML = customPlayIcon;
        } else if (options.buttonPlayIconHTML.length > 1) {
          //If icon is supplied via icon font or HTML.
          playIcon.innerHTML = options.buttonPlayIconHTML;
        } else {
          playIcon.innerHTML = defaultPlayIcon;
        }

        //If gif is within a hyperlink, insert button before it.
        if ($el.closest('a[href]')) {
          $el
            .closest('a[href]')
            .insertAdjacentElement('beforebegin', pauseButton);
        } else {
          $el.insertAdjacentElement('beforebegin', pauseButton);
        }

        pauseButton.addEventListener(
          'click',
          (e) => {
            pauseButton.setAttribute(
              'data-gifa11y-state',
              pauseButton.getAttribute('data-gifa11y-state') === 'paused'
                ? 'playing'
                : 'paused'
            );

            const play = pauseButton.querySelector('.gifa11y-play-icon'),
              pause = pauseButton.querySelector('.gifa11y-pause-icon');

            if (pauseButton.getAttribute('data-gifa11y-state') === 'paused') {
              $el.style.display = 'none';
              findCanvas.style.display = 'block';
              play.style.display = 'block';
              pause.style.display = 'none';
              pauseButton.setAttribute(
                'aria-label',
                options.langPlay + ' ' + alt
              );
            } else {
              $el.style.display = 'block';
              findCanvas.style.display = 'none';
              play.style.display = 'none';
              pause.style.display = 'block';
              pauseButton.setAttribute(
                'aria-label',
                options.langPause + ' ' + alt
              );
            }
            e.preventDefault();
          },
          false
        );
      }
    };

    this.toggleEverything = () => {
      //Wait for all gifs to completely load before you can toggle all on or off.
      const everythingBtn = document.getElementById('gifa11y-all'),
        mediaQuery = window.matchMedia('(prefers-reduced-motion: reduce)'),
        html = document.querySelector('html');

      //Only fire if page contains toggle all on/off button.
      if (everythingBtn !== null) {
        //Set initial page state based on media query and props.
        if (
          !mediaQuery ||
          mediaQuery.matches ||
          options.initiallyPaused === true
        ) {
          html.setAttribute('data-gifa11y-all', 'paused');
          everythingBtn.innerText = options.langPlayAllButton;
        } else {
          html.setAttribute('data-gifa11y-all', 'playing');
          everythingBtn.innerText = options.langPauseAllButton;
        }

        //Disable button initially to prevent people from clicking it too soon. Otherwise canvas won't generate. Remove 'disabled' attribute once all images have fully loaded.
        everythingBtn.setAttribute('disabled', true);
        Promise.all(
          Array.from($gifs)
            .filter(($el) => !$el.complete)
            .map(
              ($el) =>
                new Promise((resolve) => {
                  $el.onload = $el.onerror = resolve;
                })
            )
        ).then(() => {
          toggleAll();
          everythingBtn.removeAttribute('disabled');
        });
      }
      function toggleAll() {
        everythingBtn.addEventListener('click', () => {
          const html = document.querySelector('html');
          html.setAttribute(
            'data-gifa11y-all',
            html.getAttribute('data-gifa11y-all') === 'paused'
              ? 'playing'
              : 'paused'
          );
          const pageState = html.getAttribute('data-gifa11y-all'),
            allCanvas = document.querySelectorAll('canvas.gifa11y-canvas'),
            allBtns = document.querySelectorAll('button.gifa11y-btn');

          let playDisplay, pauseDisplay, currentState, ariaLabel;
          if (pageState === 'paused') {
            playDisplay = 'block';
            pauseDisplay = 'none';
            currentState = 'paused';
            ariaLabel = options.langPlay;
            everythingBtn.innerText = options.langPlayAllButton;
          } else {
            playDisplay = 'none';
            pauseDisplay = 'block';
            currentState = 'playing';
            ariaLabel = options.langPause;
            everythingBtn.innerText = options.langPauseAllButton;
          }

          $gifs.forEach(($el) => {
            $el.style.display = pauseDisplay;
          });
          allCanvas.forEach(($el) => {
            $el.style.display = playDisplay;
          });
          allBtns.forEach(($el) => {
            let alt = $el.getAttribute('data-gifa11y-alt'),
              play = $el.querySelector('.gifa11y-play-icon'),
              pause = $el.querySelector('.gifa11y-pause-icon');
            play.style.display = playDisplay;
            pause.style.display = pauseDisplay;
            $el.setAttribute('data-gifa11y-state', currentState);
            $el.setAttribute('aria-label', ariaLabel + ' ' + alt);
          });
        });
      }
    };

    this.generateCSS = () => {
      const stylesheet = document.createElement('style');
      stylesheet.innerHTML = `
				button.gifa11y-btn,
				span.gifa11y-warning {
					all: unset;
					box-sizing: border-box !important;
				}
				button.gifa11y-btn {
					background: ${options.buttonBackground} !important;
					color: ${options.buttonIconColor} !important;
					border-radius: 50% !important;
					box-shadow: 0 0 16px 0 #0000004f !important;
					border: 2px solid white !important;
					cursor: pointer !important;
					display: block !important;
					line-height: normal !important;
					min-height: 36px !important;
					min-width: 36px !important;
					text-align: center !important;
					margin: 12px !important;
					padding: 4px !important;
					position: absolute !important;
					transition: all .2s ease-in-out !important;
					z-index: 500 !important;
				}
				button.gifa11y-btn:hover, button.gifa11y-btn:focus {
					background: ${options.buttonBackgroundHover} !important;
				}
				button.gifa11y-btn:focus {
					box-shadow: 0 0 0 5px ${options.buttonFocusColor} !important;
					outline: 3px solid transparent;
				}
				div.gifa11y-play-icon i,
				div.gifa11y-pause-icon > i {
					font-size: ${options.buttonIconFontSize} !important;
					padding: 4px !important;
					vertical-align: middle !important;
					min-width: calc(${options.buttonIconFontSize} * 1.4) !important;
    			min-height: calc(${options.buttonIconFontSize} * 1.4) !important;
				}
				div.gifa11y-pause-icon > svg,
				div.gifa11y-play-icon > svg {
					flex-shrink: 0 !important;
					position: relative !important;
					vertical-align: middle !important;
					height: ${options.buttonIconSize} !important;
					width: ${options.buttonIconSize} !important;
					-webkit-transform: translate(0px,0px) !important;
				}
				span.gifa11y-warning {
					background: darkred !important;
					color: white !important;
					padding: 5px !important;
					font-size: 1.1rem !important;
					display: block !important;
					font-family: Arial !important;
					max-width: 450px !important;
				}
				/* Increase target size of button. */
				button.gifa11y-btn:before {
					content: "" !important;
					inset: -8.5px !important;
					min-height: 50px !important;
					min-width: 50px !important;
					position: absolute !important;
				}
				canvas.gifa11y-canvas {
					object-fit: contain !important;
					max-width: 100%;
				}
				`;
      document.getElementsByTagName('head')[0].appendChild(stylesheet);
    };
    this.initialize();
  }
}
